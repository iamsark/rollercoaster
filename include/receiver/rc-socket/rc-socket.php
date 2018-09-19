<?php 

/**
 * 
 * @author  Sark
 *          Raw IMAP agent
 * 
 */

class RC_ImapSocket {
    
    public $error;
    public $ecode;
    public $result;
    public $resultcode;
    public $selected;
    public $data                = array();
    
    protected $fp;
    protected $host;
    protected $socket;
    protected $preferences       = array();   
    protected $cmd_tag;
    protected $cmd_num = 0;
    protected $resourceid;
    protected $logged            = false;
    protected $capability        = array();
    protected $capability_readed = false;
    protected $debug             = false;
    protected $debug_handler     = false;
    
    const ERROR_OK              = 0;
    const ERROR_NO              = -1;
    const ERROR_BAD             = -2;
    const ERROR_BYE             = -3;
    const ERROR_UNKNOWN         = -4;
    const ERROR_COMMAND         = -5;
    const ERROR_READONLY        = -6;
    
    const COMMAND_NORESPONSE    = 1;
    const COMMAND_CAPABILITY    = 2;
    const COMMAND_LASTLINE      = 4;
    const COMMAND_ANONYMIZED    = 8;
    
    const DEBUG_LINE_LENGTH     = 4098;
    
    public $mflags = array (
        'SEEN'     => '\\Seen',
        'DELETED'  => '\\Deleted',
        'ANSWERED' => '\\Answered',
        'DRAFT'    => '\\Draft',
        'FLAGGED'  => '\\Flagged',
        'FORWARDED' => '$Forwarded',
        'MDNSENT'  => '$MDNSent',
        '*'        => '\\*',
    );
    
    protected function writeLine($_string, $_endln = true) {
        
        if (!$this->socket) {
            return false;
        }
        
        if ($_endln) {
            $_string .= "\r\n";
        }
        
        $res = fwrite($this->socket, $_string);
        
        if ($res === false) {
            $this->closeSocket();
        }
        
        return $res;
        
    } 
    
    protected function writeLineC($_string, $_endln=true) {
        if (!$this->socket) {
            return false;
        }
        
        if ($_endln) {
            $_string .= "\r\n";
        }
        
        $res = 0;
        if ($parts = preg_split('/(\{[0-9]+\}\r\n)/m', $_string, -1, PREG_SPLIT_DELIM_CAPTURE)) {
            for ($i=0, $cnt=count($parts); $i<$cnt; $i++) {
                if (preg_match('/^\{([0-9]+)\}\r\n$/', $parts[$i+1], $matches)) {
                    // LITERAL+ support
                    if ($this->preferences['literal+']) {
                        $parts[$i+1] = sprintf("{%d+}\r\n", $matches[1]);
                    }
                    
                    $bytes = $this->writeLine($parts[$i].$parts[$i+1], false);
                    if ($bytes === false) {
                        return false;
                    }
                    
                    $res += $bytes;
                    
                    // don't wait if server supports LITERAL+ capability
                    if (!$this->preferences['literal+']) {
                        $line = $this->readLine(1000);
                        // handle error in command
                        if ($line[0] != '+') {
                            return false;
                        }
                    }
                    
                    $i++;
                }
                else {
                    $bytes = $this->writeLine($parts[$i], false);
                    if ($bytes === false) {
                        return false;
                    }
                    
                    $res += $bytes;
                }
            }
        }
        
        return $res;
    }
    
    protected function readLine($_size = 1024) {
        $line = '';
        
        if (!$_size) {
            $_size = 1024;
        }
        
        do {
            if ($this->eof()) {
                return $line ?: null;
            }
            
            $buffer = fgets($this->socket, $_size);
            
            if ($buffer === false) {
                $this->closeSocket();
                break;
            }
            
            $line .= $buffer;
        }
        while (substr($buffer, -1) != "\n");
        
        return $line;
    }
    
    protected function multiLineRead($_line, $_escape = false) {
        $_line = rtrim($_line);
        if (preg_match('/\{([0-9]+)\}$/', $_line, $m)) {
            $out   = '';
            $str   = substr($_line, 0, -strlen($m[0]));
            $bytes = $m[1];
            
            while (strlen($out) < $bytes) {
                $_line = $this->readBytes($bytes);
                if ($_line === null) {
                    break;
                }
                
                $out .= $_line;
            }
            
            $_line = $str . ($_escape ? $this->escape($out) : $out);
        }
        
        return $_line;
    }
    
    protected function readBytes($_bytes) {
        $data = '';
        $len  = 0;
        
        while ($len < $_bytes && !$this->eof()) {
            $d = fread($this->socket, $_bytes-$len);
            $data .= $d;
            $data_len = strlen($data);
            if ($len == $data_len) {
                break; // nothing was read -> exit to avoid apache lockups
            }
            $len = $data_len;
        }
        
        return $data;
    }
    
    protected function readReply(&$_untagged = null) {
        do {
            $line = trim($this->readLine(1024));
            // store untagged response lines
            if ($line[0] == '*') {
                $_untagged[] = $line;
            }
        }
        while ($line[0] == '*');
        
        if ($_untagged) {
            $_untagged = join("\n", $_untagged);
        }
        
        return $line;
    }
    
    protected function parseResult($_string, $_err_prefix = '') {
        if (preg_match('/^[a-z0-9*]+ (OK|NO|BAD|BYE)(.*)$/i', trim($_string), $matches)) {
            $res = strtoupper($matches[1]);
            $str = trim($matches[2]);
            
            if ($res == 'OK') {
                $this->ecode = self::ERROR_OK;
            }
            else if ($res == 'NO') {
                $this->ecode = self::ERROR_NO;
            }
            else if ($res == 'BAD') {
                $this->ecode = self::ERROR_BAD;
            }
            else if ($res == 'BYE') {
                $this->closeSocket();
                $this->ecode = self::ERROR_BYE;
            }
            
            if ($str) {
                $str = trim($str);
                // get response string and code (RFC5530)
                if (preg_match("/^\[([a-z-]+)\]/i", $str, $m)) {
                    $this->resultcode = strtoupper($m[1]);
                    $str = trim(substr($str, strlen($m[1]) + 2));
                }
                else {
                    $this->resultcode = null;
                    // parse response for [APPENDUID 1204196876 3456]
                    if (preg_match("/^\[APPENDUID [0-9]+ ([0-9]+)\]/i", $str, $m)) {
                        $this->data['APPENDUID'] = $m[1];
                    }
                    // parse response for [COPYUID 1204196876 3456:3457 123:124]
                    else if (preg_match("/^\[COPYUID [0-9]+ ([0-9,:]+) ([0-9,:]+)\]/i", $str, $m)) {
                        $this->data['COPYUID'] = array($m[1], $m[2]);
                    }
                }
                
                $this->result = $str;
                
                if ($this->ecode != self::ERROR_OK) {
                    $this->error = $_err_prefix ? $_err_prefix.$str : $str;
                }
            }
            
            return $this->ecode;
        }
        
        return self::ERROR_UNKNOWN;
    }
    
    protected function eof() {
        if (!is_resource($this->socket)) {
            return true;
        }
       
        $start = microtime(true);
        
        if (feof($this->socket) ||
            ($this->preference['timeout'] && (microtime(true) - $start > $this->preference['timeout']))) {
            $this->closeSocket();
            return true;
        }
            
        return false;
    }
    
    protected function closeSocket() {
        @fclose($this->socket);
        $this->socket = null;
    }
    
    protected function setError($_code, $_msg = '') {
        $this->ecode    = $_code;
        $this->error    = $_msg;
    }
    
    protected function isStartsWith($_string, $_match, $_error = false, $_nonempty = false) {
        if (!$this->fp) {
            return true;
        }
        
        if (strncmp($_string, $_match, strlen($_match)) == 0) {
            return true;
        }
        
        if ($_error && preg_match('/^\* (BYE|BAD) /i', $_string, $m)) {
            if (strtoupper($m[1]) == 'BYE') {
                $this->closeSocket();
            }
            return true;
        }
        
        if ($_nonempty && !strlen($_string)) {
            return true;
        }
        
        return false;
    }
    
    protected function hasCapability($_name) {
        if (empty($this->capability) || $_name == '') {
            return false;
        }
        
        if (in_array($_name, $this->capability)) {
            return true;
        }
        else if (strpos($_name, '=')) {
            return false;
        }
        
        $result = array();
        foreach ($this->capability as $cap) {
            $entry = explode('=', $cap);
            if ($entry[0] == $_name) {
                $result[] = $entry[1];
            }
        }
        
        return $result ?: false;
    }
    
    public function getCapability($_name) {
        $result = $this->hasCapability($_name);
        
        if (!empty($result)) {
            return $result;
        } else if ($this->capability_readed) {
            return false;
        }
        
        // get capabilities (only once) because initial
        // optional CAPABILITY response may differ
        $result = $this->execute('CAPABILITY');
        
        if ($result[0] == self::ERROR_OK) {
            $this->parseCapability($result[1]);
        }
        
        $this->capability_readed = true;
        
        return $this->hasCapability($_name);
    }
    
    public function clearCapability() {
        $this->capability        = array();
        $this->capability_readed = false;
    }
    
    protected function authenticate($user, $pass, $type = 'PLAIN') {
        if ($type == 'CRAM-MD5' || $type == 'DIGEST-MD5') {
            if ($type == 'DIGEST-MD5' && !class_exists('Auth_SASL')) {
                $this->setError(self::ERROR_BYE,
                    "The Auth_SASL package is required for DIGEST-MD5 authentication");
                return self::ERROR_BAD;
            }
            
            $this->putLine($this->nextTag() . " AUTHENTICATE $type");
            $line = trim($this->readReply());
            
            if ($line[0] == '+') {
                $challenge = substr($line, 2);
            }
            else {
                return $this->parseResult($line);
            }
            
            if ($type == 'CRAM-MD5') {
                // RFC2195: CRAM-MD5
                $ipad = '';
                $opad = '';
                $xor  = function($str1, $str2) {
                    $result = '';
                    $size   = strlen($str1);
                    for ($i=0; $i<$size; $i++) {
                        $result .= chr(ord($str1[$i]) ^ ord($str2[$i]));
                    }
                    return $result;
                };
                
                // initialize ipad, opad
                for ($i=0; $i<64; $i++) {
                    $ipad .= chr(0x36);
                    $opad .= chr(0x5C);
                }
                
                // pad $pass so it's 64 bytes
                $pass = str_pad($pass, 64, chr(0));
                
                // generate hash
                $hash  = md5($xor($pass, $opad) . pack("H*",
                    md5($xor($pass, $ipad) . base64_decode($challenge))));
                $reply = base64_encode($user . ' ' . $hash);
                
                // send result
                $this->putLine($reply, true, true);
            }
            else {
                // RFC2831: DIGEST-MD5
                // proxy authorization
                if (!empty($this->prefs['auth_cid'])) {
                    $authc = $this->prefs['auth_cid'];
                    $pass  = $this->prefs['auth_pw'];
                }
                else {
                    $authc = $user;
                    $user  = '';
                }
                
                $auth_sasl = new Auth_SASL;
                $auth_sasl = $auth_sasl->factory('digestmd5');
                $reply     = base64_encode($auth_sasl->getResponse($authc, $pass,
                    base64_decode($challenge), $this->host, 'imap', $user));
                
                // send result
                $this->putLine($reply, true, true);
                $line = trim($this->readReply());
                
                if ($line[0] != '+') {
                    return $this->parseResult($line);
                }
                
                // check response
                $challenge = substr($line, 2);
                $challenge = base64_decode($challenge);
                if (strpos($challenge, 'rspauth=') === false) {
                    $this->setError(self::ERROR_BAD,
                        "Unexpected response from server to DIGEST-MD5 response");
                    return self::ERROR_BAD;
                }
                
                $this->putLine('');
            }
            
            $line   = $this->readReply();
            $result = $this->parseResult($line);
        }
        else if ($type == 'GSSAPI') {
            if (!extension_loaded('krb5')) {
                $this->setError(self::ERROR_BYE,
                    "The krb5 extension is required for GSSAPI authentication");
                return self::ERROR_BAD;
            }
            
            if (empty($this->prefs['gssapi_cn'])) {
                $this->setError(self::ERROR_BYE,
                    "The gssapi_cn parameter is required for GSSAPI authentication");
                return self::ERROR_BAD;
            }
            
            if (empty($this->prefs['gssapi_context'])) {
                $this->setError(self::ERROR_BYE,
                    "The gssapi_context parameter is required for GSSAPI authentication");
                return self::ERROR_BAD;
            }
            
            putenv('KRB5CCNAME=' . $this->prefs['gssapi_cn']);
            
            try {
                $ccache = new KRB5CCache();
                $ccache->open($this->prefs['gssapi_cn']);
                $gssapicontext = new GSSAPIContext();
                $gssapicontext->acquireCredentials($ccache);
                
                $token   = '';
                $success = $gssapicontext->initSecContext($this->prefs['gssapi_context'], null, null, null, $token);
                $token   = base64_encode($token);
            }
            catch (Exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
                $this->setError(self::ERROR_BYE, "GSSAPI authentication failed");
                return self::ERROR_BAD;
            }
            
            $this->putLine($this->nextTag() . " AUTHENTICATE GSSAPI " . $token);
            $line = trim($this->readReply());
            
            if ($line[0] != '+') {
                return $this->parseResult($line);
            }
            
            try {
                $challenge = base64_decode(substr($line, 2));
                $gssapicontext->unwrap($challenge, $challenge);
                $gssapicontext->wrap($challenge, $challenge, true);
            }
            catch (Exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
                $this->setError(self::ERROR_BYE, "GSSAPI authentication failed");
                return self::ERROR_BAD;
            }
            
            $this->putLine(base64_encode($challenge));
            
            $line   = $this->readReply();
            $result = $this->parseResult($line);
        }
        else { // PLAIN
            // proxy authorization
            if (!empty($this->prefs['auth_cid'])) {
                $authc = $this->prefs['auth_cid'];
                $pass  = $this->prefs['auth_pw'];
            }
            else {
                $authc = $user;
                $user  = '';
            }
            
            $reply = base64_encode($user . chr(0) . $authc . chr(0) . $pass);
            
            // RFC 4959 (SASL-IR): save one round trip
            if ($this->getCapability('SASL-IR')) {
                list($result, $line) = $this->execute("AUTHENTICATE PLAIN", array($reply),
                    self::COMMAND_LASTLINE | self::COMMAND_CAPABILITY | self::COMMAND_ANONYMIZED);
            }
            else {
                $this->putLine($this->nextTag() . " AUTHENTICATE PLAIN");
                $line = trim($this->readReply());
                
                if ($line[0] != '+') {
                    return $this->parseResult($line);
                }
                
                // send result, get reply and process it
                $this->putLine($reply, true, true);
                $line   = $this->readReply();
                $result = $this->parseResult($line);
            }
        }
        
        if ($result == self::ERROR_OK) {
            // optional CAPABILITY response
            if ($line && preg_match('/\[CAPABILITY ([^]]+)\]/i', $line, $matches)) {
                $this->parseCapability($matches[1], true);
            }
            return $this->fp;
        }
        else {
            $this->setError($result, "AUTHENTICATE $type: $line");
        }
        
        return $result;
    }
}

?>