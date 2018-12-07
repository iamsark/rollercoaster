<?php 

/**
 * 
 * @author  Sark
 *          Raw IMAP agent
 * 
 */

include_once dirname(dirname(dirname( __FILE__ ))). '/lib/auth/rc-sasl.php';
include_once dirname(dirname(dirname( __FILE__ ))). '/model/rc-indexed-message.php';


class RC_ImapSocket {
    
    public $errormsg;
    public $errorcode;    
    public $response;
    public $responsecode;    
    public $selected;
    public $data                    = array();
    
    protected $host;
    protected $user;
    protected $socket;
    protected $preferences          = array();
    
    protected $cmd_tag;
    protected $cmd_num = 0;
    protected $resourceid;
    protected $logged               = false;
    protected $capability           = array();
    protected $capability_readed    = false;
    protected $extensions_enabled   = false;
    
    const ERROR_OK                  = 0;
    const ERROR_NO                  = -1;
    const ERROR_BAD                 = -2;
    const ERROR_BYE                 = -3;
    const ERROR_UNKNOWN             = -4;
    const ERROR_COMMAND             = -5;
    const ERROR_READONLY            = -6;
    
    const COMMAND_NORESPONSE        = 1;
    const COMMAND_CAPABILITY        = 2;
    const COMMAND_LASTLINE          = 4;
    const COMMAND_ANONYMIZED        = 8;
    
    const DEBUG_LINE_LENGTH         = 4098;
    
    public $flags = array (
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
                $this->errorcode = self::ERROR_OK;
            }
            else if ($res == 'NO') {
                $this->errorcode = self::ERROR_NO;
            }
            else if ($res == 'BAD') {
                $this->errorcode = self::ERROR_BAD;
            }
            else if ($res == 'BYE') {
                $this->closeSocket();
                $this->errorcode = self::ERROR_BYE;
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
                
                if ($this->errorcode != self::ERROR_OK) {
                    $this->errormsg = $_err_prefix ? $_err_prefix.$str : $str;
                }
            }
            
            return $this->errorcode;
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
        $this->errorcode    = $_code;
        $this->errormsg     = $_msg;
    }
    
    protected function isStartsWith($_string, $_match, $_error = false, $_nonempty = false) {
        if (!$this->socket) {
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
    
    protected function authenticate($_user, $_pass, $_type = 'PLAIN') {
        if ($_type == 'CRAM-MD5' || $_type == 'DIGEST-MD5') {
            if ($_type == 'DIGEST-MD5' && !class_exists('Auth_SASL')) {
                $this->setError(self::ERROR_BYE,
                    "The Auth_SASL package is required for DIGEST-MD5 authentication");
                return self::ERROR_BAD;
            }            
            $this->putLine($this->nextTag() . " AUTHENTICATE $_type");
            $line = trim($this->readReply());
            
            if ($line[0] == '+') {
                $challenge = substr($line, 2);
            }
            else {
                return $this->parseResult($line);
            }       
            
            if ($_type == 'CRAM-MD5') {
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
                $_pass = str_pad($_pass, 64, chr(0));
                
                // generate hash
                $hash  = md5($xor($_pass, $opad) . pack("H*",
                    md5($xor($_pass, $ipad) . base64_decode($challenge))));
                $reply = base64_encode($_user . ' ' . $hash);
                
                // send result
                $this->putLine($reply, true, true);
            }
            else {
                // RFC2831: DIGEST-MD5
                // proxy authorization
                if (!empty($this->preferences['auth_cid'])) {
                    $authc = $this->preferences['auth_cid'];
                    $_pass  = $this->preferences['auth_pw'];
                }
                else {
                    $authc = $_user;
                    $_user  = '';
                }
                
                $auth_sasl = new RC_Auth();
                $auth_sasl = $auth_sasl->factory('digestmd5');
                $reply     = base64_encode($auth_sasl->getResponse($authc, $_pass,
                    base64_decode($challenge), $this->host, 'imap', $_user));
                
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
        } else { // PLAIN
            // proxy authorization
            if (!empty($this->preferences['auth_cid'])) {
                $authc = $this->preferences['auth_cid'];
                $_pass  = $this->preferences['auth_pw'];
            }
            else {
                $authc = $_user;
                $_user  = '';
            }
            
            $reply = base64_encode($_user . chr(0) . $authc . chr(0) . $_pass);
            
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
            return $this->socket;
        }
        else {
            $this->setError($result, "AUTHENTICATE $_type: $line");
        }
        
        return $result;
    }
    
    protected function login($_user, $_password) {
        list($code, $response) = $this->execute('LOGIN', array(
            $this->escape($_user), $this->escape($_password)), self::COMMAND_CAPABILITY | self::COMMAND_ANONYMIZED);
        
        // re-set capabilities list if untagged CAPABILITY response provided
        if (preg_match('/\* CAPABILITY (.+)/i', $response, $matches)) {
            $this->parseCapability($matches[1], true);
        }
        
        if ($code == self::ERROR_OK) {
            return $this->socket;
        }
        
        return $code;
    }
    
    public function getHierarchyDelimiter() {
        if ($this->preferences['delimiter']) {
            return $this->preferences['delimiter'];
        }
        
        // try (LIST "" ""), should return delimiter (RFC2060 Sec 6.3.8)
        list($code, $response) = $this->execute('LIST',
            array($this->escape(''), $this->escape('')));
        
        if ($code == self::ERROR_OK) {
            $args = $this->tokenizeResponse($response, 4);
            $delimiter = $args[3];
            
            if (strlen($delimiter) > 0) {
                return ($this->preferences['delimiter'] = $delimiter);
            }
        }
    }
    
    public function getNamespace() {
        if (array_key_exists('namespace', $this->preferences)) {
            return $this->preferences['namespace'];
        }
        
        if (!$this->getCapability('NAMESPACE')) {
            return self::ERROR_BAD;
        }
        
        list($code, $response) = $this->execute('NAMESPACE');
        
        if ($code == self::ERROR_OK && preg_match('/^\* NAMESPACE /', $response)) {
            $response = substr($response, 11);
            $data     = $this->tokenizeResponse($response);
        }
        
        if (!is_array($data)) {
            return $code;
        }
        
        $this->preferences['namespace'] = array(
            'personal' => $data[0],
            'other'    => $data[1],
            'shared'   => $data[2],
        );
        
        return $this->preferences['namespace'];
    }
    
    public function connect($host, $user, $password, $options = array()) {
        // configure
        $this->set_prefs($options);
        
        $this->host     = $host;
        $this->user     = $user;
        $this->logged   = false;
        $this->selected = null;
        
        // check input
        if (empty($host)) {
            $this->setError(self::ERROR_BAD, "Empty host");
            return false;
        }
        
        if (empty($user)) {
            $this->setError(self::ERROR_NO, "Empty user");
            return false;
        }
        
        if (empty($password) && empty($options['gssapi_cn'])) {
            $this->setError(self::ERROR_NO, "Empty password");
            return false;
        }
        
        // Connect
        if (!$this->_connect($host)) {
            return false;
        }
        
        // Send ID info
        if (!empty($this->preferences['ident']) && $this->getCapability('ID')) {
            $this->data['ID'] = $this->id($this->preferences['ident']);
        }
        
        $auth_method  = $this->preferences['auth_type'];
        $auth_methods = array();
        $result       = null;
        
        // check for supported auth methods
        if ($auth_method == 'CHECK') {
            if ($auth_caps = $this->getCapability('AUTH')) {
                $auth_methods = $auth_caps;
            }
            
            // RFC 2595 (LOGINDISABLED) LOGIN disabled when connection is not secure
            $login_disabled = $this->getCapability('LOGINDISABLED');
            if (($key = array_search('LOGIN', $auth_methods)) !== false) {
                if ($login_disabled) {
                    unset($auth_methods[$key]);
                }
            }
            else if (!$login_disabled) {
                $auth_methods[] = 'LOGIN';
            }
            
            // Use best (for security) supported authentication method
            $all_methods = array('DIGEST-MD5', 'CRAM-MD5', 'CRAM_MD5', 'PLAIN', 'LOGIN');
            
            if (!empty($this->preferences['gssapi_cn'])) {
                array_unshift($all_methods, 'GSSAPI');
            }
            
            foreach ($all_methods as $auth_method) {
                if (in_array($auth_method, $auth_methods)) {
                    break;
                }
            }
        }
        else {
            // Prevent from sending credentials in plain text when connection is not secure
            if ($auth_method == 'LOGIN' && $this->getCapability('LOGINDISABLED')) {
                $this->setError(self::ERROR_BAD, "Login disabled by IMAP server");
                $this->closeConnection();
                return false;
            }
            // replace AUTH with CRAM-MD5 for backward compat.
            if ($auth_method == 'AUTH') {
                $auth_method = 'CRAM-MD5';
            }
        }
        
        // pre-login capabilities can be not complete
        $this->capability_readed = false;
        
        // Authenticate
        switch ($auth_method) {
            case 'CRAM_MD5':
                $auth_method = 'CRAM-MD5';
            case 'CRAM-MD5':
            case 'DIGEST-MD5':
            case 'PLAIN':            
                $result = $this->authenticate($user, $password, $auth_method);
                break;
            case 'LOGIN':
                $result = $this->login($user, $password);
                break;
            default:
                $this->setError(self::ERROR_BAD, "Configuration error. Unknown auth method: $auth_method");
        }
        
        // Connected and authenticated
        if (is_resource($result)) {
            if ($this->preferences['force_caps']) {
                $this->clearCapability();
            }
            $this->logged = true;
            
            return true;
        }
        
        $this->closeConnection();
        
        return false;
    }
    
    protected function _connect($_host)
    {
        // initialize connection
        $this->errormsg    = '';
        $this->errorcode = self::ERROR_OK;
        
        if (!$this->preferences['port']) {
            $this->preferences['port'] = 143;
        }
        
        // check for SSL
        if ($this->preferences['ssl_mode'] && $this->preferences['ssl_mode'] != 'tls') {
            $_host = $this->preferences['ssl_mode'] . '://' . $_host;
        }
        
        if ($this->preferences['timeout'] <= 0) {
            $this->preferences['timeout'] = max(0, intval(ini_get('default_socket_timeout')));
        }
        
        if (!empty($this->preferences['socket_options'])) {
            $context  = stream_context_create($this->preferences['socket_options']);
            $this->socket = stream_socket_client($_host . ':' . $this->preferences['port'], $errno, $errstr,
                $this->preferences['timeout'], STREAM_CLIENT_CONNECT, $context);
        }
        else {
            $this->socket = @fsockopen($_host, $this->preferences['port'], $errno, $errstr, $this->preferences['timeout']);
        }
        
        if (!$this->socket) {
            $this->setError(self::ERROR_BAD, sprintf("Could not connect to %s:%d: %s",
                $_host, $this->preferences['port'], $errstr ?: "Unknown reason"));
            
            return false;
        }
        
        if ($this->preferences['timeout'] > 0) {
            stream_set_timeout($this->socket, $this->preferences['timeout']);
        }
        
        $line = trim(fgets($this->socket, 8192));
        
        if ($this->debug) {
            // set connection identifier for debug output
            preg_match('/#([0-9]+)/', (string) $this->socket, $m);
            $this->resourceid = strtoupper(substr(md5($m[1].$this->user.microtime()), 0, 4));
            
            if ($line) {
                $this->debug('S: '. $line);
            }
        }
        
        // Connected to wrong port or connection error?
        if (!preg_match('/^\* (OK|PREAUTH)/i', $line)) {
            if ($line)
                $error = sprintf("Wrong startup greeting (%s:%d): %s", $_host, $this->preferences['port'], $line);
                else
                    $error = sprintf("Empty startup greeting (%s:%d)", $_host, $this->preferences['port']);
                    
                    $this->setError(self::ERROR_BAD, $error);
                    $this->closeConnection();
                    return false;
        }
        
        $this->data['GREETING'] = trim(preg_replace('/\[[^\]]+\]\s*/', '', $line));
        
        // RFC3501 [7.1] optional CAPABILITY response
        if (preg_match('/\[CAPABILITY ([^]]+)\]/i', $line, $matches)) {
            $this->parseCapability($matches[1], true);
        }
        
        // TLS connection
        if ($this->preferences['ssl_mode'] == 'tls' && $this->getCapability('STARTTLS')) {
            $res = $this->execute('STARTTLS');
            
            if ($res[0] != self::ERROR_OK) {
                $this->closeConnection();
                return false;
            }
            
            if (isset($this->preferences['socket_options']['ssl']['crypto_method'])) {
                $crypto_method = $this->preferences['socket_options']['ssl']['crypto_method'];
            }
            else {
                // There is no flag to enable all TLS methods. Net_SMTP
                // handles enabling TLS similarly.
                $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT
                | @STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT
                | @STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            
            if (!stream_socket_enable_crypto($this->socket, true, $crypto_method)) {
                $this->setError(self::ERROR_BAD, "Unable to negotiate TLS");
                $this->closeConnection();
                return false;
            }
            
            // Now we're secure, capabilities need to be reread
            $this->clearCapability();
        }
        
        return true;
    }
    
    protected function set_prefs($_prefs) {
        // set preferences
        if (is_array($_prefs)) {
            $this->preferences = $_prefs;
        }
        
        // set auth method
        if (!empty($this->preferences['auth_type'])) {
            $this->preferences['auth_type'] = strtoupper($this->preferences['auth_type']);
        }
        else {
            $this->preferences['auth_type'] = 'CHECK';
        }
        
        // disabled capabilities
        if (!empty($this->preferences['disabled_caps'])) {
            $this->preferences['disabled_caps'] = array_map('strtoupper', (array)$this->preferences['disabled_caps']);
        }
        
        // additional message flags
        if (!empty($this->preferences['message_flags'])) {
            $this->flags = array_merge($this->flags, $this->preferences['message_flags']);
            unset($this->preferences['message_flags']);
        }
    }
    
    public function connected() {
        return $this->socket && $this->logged;
    }
    
    public function closeConnection() {
        if ($this->logged && $this->putLine($this->nextTag() . ' LOGOUT')) {
            $this->readReply();
        }
        
        $this->closeSocket();
    }
    
    public function select($_mailbox, $_qresync_data = null) {
        if (!strlen($_mailbox)) {
            return false;
        }        
        if ($this->selected === $_mailbox) {
            return true;
        }
        $params = array($this->escape($_mailbox));
        
        // QRESYNC data items
        //    0. the last known UIDVALIDITY,
        //    1. the last known modification sequence,
        //    2. the optional set of known UIDs, and
        //    3. an optional parenthesized list of known sequence ranges and their
        //       corresponding UIDs.
        if (!empty($_qresync_data)) {
            if (!empty($_qresync_data[2])) {
                $_qresync_data[2] = self::compressMessageSet($_qresync_data[2]);
            }
            
            $params[] = array('QRESYNC', $_qresync_data);
        }
        
        list($code, $response) = $this->execute('SELECT', $params);
        
        if ($code == self::ERROR_OK) {
            $this->clear_mailbox_cache();
            
            $response = explode("\r\n", $response);
            foreach ($response as $line) {
                if (preg_match('/^\* OK \[/i', $line)) {
                    $pos   = strcspn($line, ' ]', 6);
                    $token = strtoupper(substr($line, 6, $pos));
                    $pos   += 7;
                    
                    switch ($token) {
                        case 'UIDNEXT':
                        case 'UIDVALIDITY':
                        case 'UNSEEN':
                            if ($len = strspn($line, '0123456789', $pos)) {
                                $this->data[$token] = (int) substr($line, $pos, $len);
                            }
                            break;
                            
                        case 'HIGHESTMODSEQ':
                            if ($len = strspn($line, '0123456789', $pos)) {
                                $this->data[$token] = (string) substr($line, $pos, $len);
                            }
                            break;
                            
                        case 'NOMODSEQ':
                            $this->data[$token] = true;
                            break;
                            
                        case 'PERMANENTFLAGS':
                            $start = strpos($line, '(', $pos);
                            $end   = strrpos($line, ')');
                            if ($start && $end) {
                                $flags = substr($line, $start + 1, $end - $start - 1);
                                $this->data[$token] = explode(' ', $flags);
                            }
                            break;
                    }
                }
                else if (preg_match('/^\* ([0-9]+) (EXISTS|RECENT|FETCH)/i', $line, $match)) {
                    $token = strtoupper($match[2]);
                    switch ($token) {
                        case 'EXISTS':
                        case 'RECENT':
                            $this->data[$token] = (int) $match[1];
                            break;
                            
                        case 'FETCH':
                            // QRESYNC FETCH response (RFC5162)
                            $line       = substr($line, strlen($match[0]));
                            $fetch_data = $this->tokenizeResponse($line, 1);
                            $data       = array('id' => $match[1]);
                            
                            for ($i=0, $size=count($fetch_data); $i<$size; $i+=2) {
                                $data[strtolower($fetch_data[$i])] = $fetch_data[$i+1];
                            }
                            
                            $this->data['QRESYNC'][$data['uid']] = $data;
                            break;
                    }
                }
                // QRESYNC VANISHED response (RFC5162)
                else if (preg_match('/^\* VANISHED [()EARLIER]*/i', $line, $match)) {
                    $line   = substr($line, strlen($match[0]));
                    $v_data = $this->tokenizeResponse($line, 1);
                    
                    $this->data['VANISHED'] = $v_data;
                }
            }
            
            $this->data['READ-WRITE'] = $this->resultcode != 'READ-ONLY';
            $this->selected = $_mailbox;
            
            return true;
        }
        
        return false;
    }
    
    public function status($_mailbox, $_items = array()) {
        if (!strlen($_mailbox)) {
            return false;
        }
        
        if (!in_array('MESSAGES', $_items)) {
            $_items[] = 'MESSAGES';
        }
        if (!in_array('UNSEEN', $_items)) {
            $_items[] = 'UNSEEN';
        }
        
        list($code, $response) = $this->execute('STATUS', array($this->escape($_mailbox),
            '(' . implode(' ', $_items) . ')'));
        
        if ($code == self::ERROR_OK && preg_match('/^\* STATUS /i', $response)) {
            $result   = array();
            $response = substr($response, 9); // remove prefix "* STATUS "
            
            list($mbox, $_items) = $this->tokenizeResponse($response, 2);
            
            // Fix for #1487859. Some buggy server returns not quoted
            // folder name with spaces. Let's try to handle this situation
            if (!is_array($_items) && ($pos = strpos($response, '(')) !== false) {
                $response = substr($response, $pos);
                $_items    = $this->tokenizeResponse($response, 1);
            }
            
            if (!is_array($_items)) {
                return $result;
            }
            
            for ($i=0, $len=count($_items); $i<$len; $i += 2) {
                $result[$_items[$i]] = $_items[$i+1];
            }
            
            $this->data['STATUS:'.$_mailbox] = $result;
            
            return $result;
        }
        
        return false;
    }
    
    public function expunge($_mailbox, $_messages = null) {
        if (!$this->select($_mailbox)) {
            return false;
        }
        
        if (!$this->data['READ-WRITE']) {
            $this->setError(self::ERROR_READONLY, "Mailbox is read-only");
            return false;
        }
        
        // Clear internal status cache
        $this->clear_status_cache($_mailbox);
        
        if (!empty($_messages) && $_messages != '*' && $this->hasCapability('UIDPLUS')) {
            $_messages = self::compressMessageSet($_messages);
            $result   = $this->execute('UID EXPUNGE', array($_messages), self::COMMAND_NORESPONSE);
        }
        else {
            $result = $this->execute('EXPUNGE', null, self::COMMAND_NORESPONSE);
        }
        
        if ($result == self::ERROR_OK) {
            $this->selected = null; // state has changed, need to reselect
            return true;
        }
        
        return false;
    }
    
    public function close() {
        $result = $this->execute('CLOSE', null, self::COMMAND_NORESPONSE);        
        if ($result == self::ERROR_OK) {
            $this->selected = null;
            return true;
        }        
        return false;
    }
        
    public function subscribe($_mailbox) {
        $result = $this->execute('SUBSCRIBE', array($this->escape($_mailbox)),
            self::COMMAND_NORESPONSE);        
        return $result == self::ERROR_OK;
    }
    
    public function unsubscribe($_mailbox) {
        $result = $this->execute('UNSUBSCRIBE', array($this->escape($_mailbox)),
            self::COMMAND_NORESPONSE);        
        return $result == self::ERROR_OK;
    }
    
    public function createFolder($_mailbox, $_types = null) {
        $args = array($this->escape($_mailbox));        
        // RFC 6154: CREATE-SPECIAL-USE
        if (!empty($_types) && $this->getCapability('CREATE-SPECIAL-USE')) {
            $args[] = '(USE (' . implode(' ', $_types) . '))';
        }        
        $result = $this->execute('CREATE', $args, self::COMMAND_NORESPONSE);        
        return $result == self::ERROR_OK;
    }
    
    public function renameFolder($_from, $_to) {
        $result = $this->execute('RENAME', array($this->escape($_from), $this->escape($_to)),
            self::COMMAND_NORESPONSE);        
        return $result == self::ERROR_OK;
    }
    
    public function deleteFolder($_mailbox) {
        $result = $this->execute('DELETE', array($this->escape($_mailbox)),
            self::COMMAND_NORESPONSE);        
        return $result == self::ERROR_OK;
    }
    
    public function clearFolder($_mailbox) {
        if ($this->countMessages($_mailbox) > 0) {
            $res = $this->flag($_mailbox, '1:*', 'DELETED');
        }        
        if ($res) {
            if ($this->selected === $_mailbox) {
                $res = $this->close();
            }
            else {
                $res = $this->expunge($_mailbox);
            }
        }        
        return $res;
    }
    
    public function listMailboxes($_ref, $_mailbox, $_return_opts = array(), $_select_opts = array()) {
        return $this->_listMailboxes($_ref, $_mailbox, false, $_return_opts, $_select_opts);
    }
    
    public function listSubscribed($_ref, $_mailbox, $_return_opts = array()) {
        return $this->_listMailboxes($_ref, $_mailbox, true, $_return_opts, null);
    }
    
    protected function _listMailboxes($_ref, $_mailbox, $_subscribed=false, $_return_opts=array(), $_select_opts=array()) {
        if (!strlen($_mailbox)) {
            $_mailbox = '*';
        }
        
        $args = array();
        $rets = array();
        
        if (!empty($_select_opts) && $this->getCapability('LIST-EXTENDED')) {
            $_select_opts = (array) $_select_opts;
            
            $args[] = '(' . implode(' ', $_select_opts) . ')';
        }
        
        $args[] = $this->escape($_ref);
        $args[] = $this->escape($_mailbox);
        
        if (!empty($_return_opts) && $this->getCapability('LIST-EXTENDED')) {
            $ext_opts    = array('SUBSCRIBED', 'CHILDREN');
            $rets        = array_intersect($_return_opts, $ext_opts);
            $_return_opts = array_diff($_return_opts, $rets);
        }
        
        if (!empty($_return_opts) && $this->getCapability('LIST-STATUS')) {
            $lstatus     = true;
            $status_opts = array('MESSAGES', 'RECENT', 'UIDNEXT', 'UIDVALIDITY', 'UNSEEN');
            $opts        = array_diff($_return_opts, $status_opts);
            $status_opts = array_diff($_return_opts, $opts);
            
            if (!empty($status_opts)) {
                $rets[] = 'STATUS (' . implode(' ', $status_opts) . ')';
            }
            
            if (!empty($opts)) {
                $rets = array_merge($rets, $opts);
            }
        }
        
        if (!empty($rets)) {
            $args[] = 'RETURN (' . implode(' ', $rets) . ')';
        }
        
        list($code, $response) = $this->execute($_subscribed ? 'LSUB' : 'LIST', $args);
        
        if ($code == self::ERROR_OK) {
            $folders  = array();
            $last     = 0;
            $pos      = 0;
            $response .= "\r\n";
            
            while ($pos = strpos($response, "\r\n", $pos+1)) {
                // literal string, not real end-of-command-line
                if ($response[$pos-1] == '}') {
                    continue;
                }
                
                $line = substr($response, $last, $pos - $last);
                $last = $pos + 2;
                
                if (!preg_match('/^\* (LIST|LSUB|STATUS|MYRIGHTS) /i', $line, $m)) {
                    continue;
                }
                
                $cmd  = strtoupper($m[1]);
                $line = substr($line, strlen($m[0]));
                
                // * LIST (<options>) <delimiter> <mailbox>
                if ($cmd == 'LIST' || $cmd == 'LSUB') {
                    list($opts, $delim, $_mailbox) = $this->tokenizeResponse($line, 3);
                    
                    // Remove redundant separator at the end of folder name, UW-IMAP bug? (#1488879)
                    if ($delim) {
                        $_mailbox = rtrim($_mailbox, $delim);
                    }
                    
                    // Add to result array
                    if (!$lstatus) {
                        $folders[] = $_mailbox;
                    }
                    else {
                        $folders[$_mailbox] = array();
                    }
                    
                    // store folder options
                    if ($cmd == 'LIST') {
                        // Add to options array
                        if (empty($this->data['LIST'][$_mailbox])) {
                            $this->data['LIST'][$_mailbox] = $opts;
                        }
                        else if (!empty($opts)) {
                            $this->data['LIST'][$_mailbox] = array_unique(array_merge(
                                $this->data['LIST'][$_mailbox], $opts));
                        }
                    }
                }
                else if ($lstatus) {
                    // * STATUS <mailbox> (<result>)
                    if ($cmd == 'STATUS') {
                        list($_mailbox, $status) = $this->tokenizeResponse($line, 2);
                        
                        for ($i=0, $len=count($status); $i<$len; $i += 2) {
                            list($name, $value) = $this->tokenizeResponse($status, 2);
                            $folders[$_mailbox][$name] = $value;
                        }
                    }
                    // * MYRIGHTS <mailbox> <acl>
                    else if ($cmd == 'MYRIGHTS') {
                        list($_mailbox, $acl)  = $this->tokenizeResponse($line, 2);
                        $folders[$_mailbox]['MYRIGHTS'] = $acl;
                    }
                }
            }
            
            return $folders;
        }
        
        return false;
    }
    
    public function countMessages($_mailbox) {
        if ($this->selected === $_mailbox && isset($this->data['EXISTS'])) {
            return $this->data['EXISTS'];
        }
        
        // Check internal cache
        $cache = $this->data['STATUS:'.$_mailbox];
        if (!empty($cache) && isset($cache['MESSAGES'])) {
            return (int) $cache['MESSAGES'];
        }
        
        // Try STATUS (should be faster than SELECT)
        $counts = $this->status($_mailbox);
        if (is_array($counts)) {
            return (int) $counts['MESSAGES'];
        }
        
        return false;
    }
    
    public function countRecent($_mailbox) {
        if ($this->selected === $_mailbox && isset($this->data['RECENT'])) {
            return $this->data['RECENT'];
        }
        
        // Check internal cache
        $cache = $this->data['STATUS:'.$_mailbox];
        if (!empty($cache) && isset($cache['RECENT'])) {
            return (int) $cache['RECENT'];
        }
        
        // Try STATUS (should be faster than SELECT)
        $counts = $this->status($_mailbox, array('RECENT'));
        if (is_array($counts)) {
            return (int) $counts['RECENT'];
        }
        
        return false;
    }
    
    public function countUnseen($_mailbox) {
        // Check internal cache
        $cache = $this->data['STATUS:'.$_mailbox];
        if (!empty($cache) && isset($cache['UNSEEN'])) {
            return (int) $cache['UNSEEN'];
        }
        
        // Try STATUS (should be faster than SELECT+SEARCH)
        $counts = $this->status($_mailbox);
        if (is_array($counts)) {
            return (int) $counts['UNSEEN'];
        }
        
        // Invoke SEARCH as a fallback
        $index = $this->search($_mailbox, 'ALL UNSEEN', false, array('COUNT'));
        if (!$index->is_error()) {
            return $index->count();
        }
        
        return false;
    }
    
    public function id($_items = array()) {
        if (is_array($_items) && !empty($_items)) {
            foreach ($_items as $key => $value) {
                $args[] = $this->escape($key, true);
                $args[] = $this->escape($value, true);
            }
        }
        
        list($code, $response) = $this->execute('ID', array(
            !empty($args) ? '(' . implode(' ', (array) $args) . ')' : $this->escape(null)
        ));
        
        if ($code == self::ERROR_OK && preg_match('/^\* ID /i', $response)) {
            $response = substr($response, 5); // remove prefix "* ID "
            $_items    = $this->tokenizeResponse($response, 1);
            $result   = null;
            
            for ($i=0, $len=count($_items); $i<$len; $i += 2) {
                $result[$_items[$i]] = $_items[$i+1];
            }
            
            return $result;
        }
        
        return false;
    }
    
    public function enable($_extension) {
        if (empty($_extension)) {
            return false;
        }
        
        if (!$this->hasCapability('ENABLE')) {
            return false;
        }
        
        if (!is_array($_extension)) {
            $_extension = array($_extension);
        }
        
        if (!empty($this->extensions_enabled)) {
            // check if all extensions are already enabled
            $diff = array_diff($_extension, $this->extensions_enabled);
            
            if (empty($diff)) {
                return $_extension;
            }
            
            // Make sure the mailbox isn't selected, before enabling extension(s)
            if ($this->selected !== null) {
                $this->close();
            }
        }
        
        list($code, $response) = $this->execute('ENABLE', $_extension);
        
        if ($code == self::ERROR_OK && preg_match('/^\* ENABLED /i', $response)) {
            $response = substr($response, 10); // remove prefix "* ENABLED "
            $result   = (array) $this->tokenizeResponse($response);
            
            $this->extensions_enabled = array_unique(array_merge((array)$this->extensions_enabled, $result));
            
            return $this->extensions_enabled;
        }
        
        return false;
    }
    
    public function sort($_mailbox, $_field = 'ARRIVAL', $_criteria = '', $_return_uid = false, $_encoding = 'US-ASCII') {
        $old_sel   = $this->selected;
        $supported = array('ARRIVAL', 'CC', 'DATE', 'FROM', 'SIZE', 'SUBJECT', 'TO');
        $_field     = strtoupper($_field);
        
        if ($_field == 'INTERNALDATE') {
            $_field = 'ARRIVAL';
        }
        
        if (!in_array($_field, $supported)) {
            return new RC_IndexedMessage($_mailbox);
        }
        
        if (!$this->select($_mailbox)) {
            return new RC_IndexedMessage($_mailbox);
        }
        
        // return empty result when folder is empty and we're just after SELECT
        if ($old_sel != $_mailbox && !$this->data['EXISTS']) {
            return new RC_IndexedMessage($_mailbox, '* SORT');
        }
        
        // RFC 5957: SORT=DISPLAY
        if (($_field == 'FROM' || $_field == 'TO') && $this->getCapability('SORT=DISPLAY')) {
            $_field = 'DISPLAY' . $_field;
        }
        
        $_encoding = $_encoding ? trim($_encoding) : 'US-ASCII';
        $_criteria = $_criteria ? 'ALL ' . trim($_criteria) : 'ALL';
        
        list($code, $response) = $this->execute($_return_uid ? 'UID SORT' : 'SORT',
            array("($_field)", $_encoding, $_criteria));
        
        if ($code != self::ERROR_OK) {
            $response = null;
        }
        
        return new RC_IndexedMessage($_mailbox, $response);
    }
    
    public function thread($_mailbox, $_algorithm = 'REFERENCES', $_criteria = '', $_return_uid = false, $_encoding = 'US-ASCII') {
        $old_sel = $this->selected;
        
        if (!$this->select($_mailbox)) {
            return new RC_ThreadedMessage($_mailbox);
        }
        
        // return empty result when folder is empty and we're just after SELECT
        if ($old_sel != $_mailbox && !$this->data['EXISTS']) {
            return new RC_ThreadedMessage($_mailbox, '* THREAD');
        }
        
        $_encoding  = $_encoding ? trim($_encoding) : 'US-ASCII';
        $_algorithm = $_algorithm ? trim($_algorithm) : 'REFERENCES';
        $_criteria  = $_criteria ? 'ALL '.trim($_criteria) : 'ALL';
        
        list($code, $response) = $this->execute($_return_uid ? 'UID THREAD' : 'THREAD',
            array($_algorithm, $_encoding, $_criteria));
        
        if ($code != self::ERROR_OK) {
            $response = null;
        }
        
        return new RC_ThreadedMessage($_mailbox, $response);
    }
    
    public function search($_mailbox, $_criteria, $_return_uid = false, $_items = array()) {
        $old_sel = $this->selected;
        
        if (!$this->select($_mailbox)) {
            return new RC_IndexedMessage($_mailbox);
        }
        
        // return empty result when folder is empty and we're just after SELECT
        if ($old_sel != $_mailbox && !$this->data['EXISTS']) {
            return new RC_IndexedMessage($_mailbox, '* SEARCH');
        }
        
        // If ESEARCH is supported always use ALL
        // but not when items are specified or using simple id2uid search
        if (empty($_items) && preg_match('/[^0-9]/', $_criteria)) {
            $_items = array('ALL');
        }
        
        $esearch  = empty($_items) ? false : $this->getCapability('ESEARCH');
        $_criteria = trim($_criteria);
        $params   = '';
        
        // RFC4731: ESEARCH
        if (!empty($_items) && $esearch) {
            $params .= 'RETURN (' . implode(' ', $_items) . ')';
        }
        
        if (!empty($_criteria)) {
            $params .= ($params ? ' ' : '') . $_criteria;
        }
        else {
            $params .= 'ALL';
        }
        
        list($code, $response) = $this->execute($_return_uid ? 'UID SEARCH' : 'SEARCH',
            array($params));
        
        if ($code != self::ERROR_OK) {
            $response = null;
        }
        
        return new RC_IndexedMessage($_mailbox, $response);
    }
    
    public function index($_mailbox, $_message_set, $_index_field='', $_skip_deleted=true, $_uidfetch=false, $_return_uid=false) {
        $msg_index = $this->fetchHeaderIndex($_mailbox, $_message_set,
            $_index_field, $_skip_deleted, $_uidfetch, $_return_uid);
        
        if (!empty($msg_index)) {
            asort($msg_index); // ASC
            $msg_index = array_keys($msg_index);
            $msg_index = '* SEARCH ' . implode(' ', $msg_index);
        }
        else {
            $msg_index = is_array($msg_index) ? '* SEARCH' : null;
        }
        
        return new RC_IndexedMessage($_mailbox, $msg_index);
    }
    
    public function fetchHeaderIndex($_mailbox, $_message_set, $_index_field = '', $_skip_deleted = true, $_uidfetch = false, $_return_uid = false) {
        if (is_array($_message_set)) {
            if (!($_message_set = $this->compressMessageSet($_message_set))) {
                return false;
            }
        }
        else {
            list($from_idx, $to_idx) = explode(':', $_message_set);
            if (empty($_message_set) ||
                (isset($to_idx) && $to_idx != '*' && (int)$from_idx > (int)$to_idx)
                ) {
                    return false;
                }
        }
        
        $_index_field = empty($_index_field) ? 'DATE' : strtoupper($_index_field);
        $fields_a = array();
        $fields_a['DATE']         = 1;
        $fields_a['INTERNALDATE'] = 4;
        $fields_a['ARRIVAL']      = 4;
        $fields_a['FROM']         = 1;
        $fields_a['REPLY-TO']     = 1;
        $fields_a['SENDER']       = 1;
        $fields_a['TO']           = 1;
        $fields_a['CC']           = 1;
        $fields_a['SUBJECT']      = 1;
        $fields_a['UID']          = 2;
        $fields_a['SIZE']         = 2;
        $fields_a['SEEN']         = 3;
        $fields_a['RECENT']       = 3;
        $fields_a['DELETED']      = 3;
        
        if (!($mode = $fields_a[$_index_field])) {
            return false;
        }
        
        //  Select the mailbox
        if (!$this->select($_mailbox)) {
            return false;
        }
        
        // build FETCH command string
        $key    = $this->nextTag();
        $cmd    = $_uidfetch ? 'UID FETCH' : 'FETCH';
        $fields = array();
        
        if ($_return_uid) {
            $fields[] = 'UID';
        }
        if ($_skip_deleted) {
            $fields[] = 'FLAGS';
        }
        
        if ($mode == 1) {
            if ($_index_field == 'DATE') {
                $fields[] = 'INTERNALDATE';
            }
            $fields[] = "BODY.PEEK[HEADER.FIELDS ($_index_field)]";
        }
        else if ($mode == 2) {
            if ($_index_field == 'SIZE') {
                $fields[] = 'RFC822.SIZE';
            }
            else if (!$_return_uid || $_index_field != 'UID') {
                $fields[] = $_index_field;
            }
        }
        else if ($mode == 3 && !$_skip_deleted) {
            $fields[] = 'FLAGS';
        }
        else if ($mode == 4) {
            $fields[] = 'INTERNALDATE';
        }
        
        $request = "$key $cmd $_message_set (" . implode(' ', $fields) . ")";
        
        if (!$this->putLine($request)) {
            $this->setError(self::ERROR_COMMAND, "Failed to send $cmd command");
            return false;
        }
        
        $result = array();
        
        do {
            $line = rtrim($this->readLine(200));
            $line = $this->multLine($line);
            
            if (preg_match('/^\* ([0-9]+) FETCH/', $line, $m)) {
                $id     = $m[1];
                $flags  = null;
                
                if ($_return_uid) {
                    if (preg_match('/UID ([0-9]+)/', $line, $matches)) {
                        $id = (int) $matches[1];
                    }
                    else {
                        continue;
                    }
                }
                if ($_skip_deleted && preg_match('/FLAGS \(([^)]+)\)/', $line, $matches)) {
                    $flags = explode(' ', strtoupper($matches[1]));
                    if (in_array('\\DELETED', $flags)) {
                        continue;
                    }
                }
                
                if ($mode == 1 && $_index_field == 'DATE') {
                    if (preg_match('/BODY\[HEADER\.FIELDS \("*DATE"*\)\] (.*)/', $line, $matches)) {
                        $value = preg_replace(array('/^"*[a-z]+:/i'), '', $matches[1]);
                        $value = trim($value);
                        $result[$id] = RC_Helper::strtotime($value);
                    }
                    // non-existent/empty Date: header, use INTERNALDATE
                    if (empty($result[$id])) {
                        if (preg_match('/INTERNALDATE "([^"]+)"/', $line, $matches)) {
                            $result[$id] = RC_Helper::strtotime($matches[1]);
                        }
                        else {
                            $result[$id] = 0;
                        }
                    }
                }
                else if ($mode == 1) {
                    if (preg_match('/BODY\[HEADER\.FIELDS \("?(FROM|REPLY-TO|SENDER|TO|SUBJECT)"?\)\] (.*)/', $line, $matches)) {
                        $value = preg_replace(array('/^"*[a-z]+:/i', '/\s+$/sm'), array('', ''), $matches[2]);
                        $result[$id] = trim($value);
                    }
                    else {
                        $result[$id] = '';
                    }
                }
                else if ($mode == 2) {
                    if (preg_match('/' . $_index_field . ' ([0-9]+)/', $line, $matches)) {
                        $result[$id] = trim($matches[1]);
                    }
                    else {
                        $result[$id] = 0;
                    }
                }
                else if ($mode == 3) {
                    if (!$flags && preg_match('/FLAGS \(([^)]+)\)/', $line, $matches)) {
                        $flags = explode(' ', $matches[1]);
                    }
                    $result[$id] = in_array("\\".$_index_field, (array) $flags) ? 1 : 0;
                }
                else if ($mode == 4) {
                    if (preg_match('/INTERNALDATE "([^"]+)"/', $line, $matches)) {
                        $result[$id] = RC_Helper::strtotime($matches[1]);
                    }
                    else {
                        $result[$id] = 0;
                    }
                }
            }
        }
        while (!$this->startsWith($line, $key, true, true));
        
        return $result;
    }
    
    public function UID2ID($_mailbox, $_uid) {
        if ($_uid > 0) {
            $index = $this->search($_mailbox, "UID $_uid");
            
            if ($index->count() == 1) {
                $arr = $index->get();
                return (int) $arr[0];
            }
        }
    }
    
    public function ID2UID($_mailbox, $_id) {
        if (empty($_id) || $_id < 0) {
            return null;
        }
        
        if (!$this->select($_mailbox)) {
            return null;
        }
        
        if ($uid = $this->data['UID-MAP'][$_id]) {
            return $uid;
        }
        
        if (isset($this->data['EXISTS']) && $_id > $this->data['EXISTS']) {
            return null;
        }
        
        $index = $this->search($_mailbox, $_id, true);
        
        if ($index->count() == 1) {
            $arr = $index->get();
            return $this->data['UID-MAP'][$_id] = (int) $arr[0];
        }
    }
    
    public function flag($_mailbox, $_messages, $_flag) {
        return $this->modFlag($_mailbox, $_messages, $_flag, '+');
    }
    
    public function unflag($_mailbox, $_messages, $_flag) {
        return $this->modFlag($_mailbox, $_messages, $_flag, '-');
    }
    
    protected function modFlag($_mailbox, $_messages, $_flag, $_mod = '+') {
        if (!$_flag) {
            return false;
        }
        
        if (!$this->select($_mailbox)) {
            return false;
        }
        
        if (!$this->data['READ-WRITE']) {
            $this->setError(self::ERROR_READONLY, "Mailbox is read-only");
            return false;
        }
        
        if ($this->flags[strtoupper($_flag)]) {
            $_flag = $this->flags[strtoupper($_flag)];
        }
        
        // if PERMANENTFLAGS is not specified all flags are allowed
        if (!empty($this->data['PERMANENTFLAGS'])
            && !in_array($_flag, (array) $this->data['PERMANENTFLAGS'])
            && !in_array('\\*', (array) $this->data['PERMANENTFLAGS'])) {
            return false;
        }
            
        // Clear internal status cache
        if ($_flag == 'SEEN') {
            unset($this->data['STATUS:'.$_mailbox]['UNSEEN']);
        }
        
        if ($_mod != '+' && $_mod != '-') {
            $_mod = '+';
        }
        
        $result = $this->execute('UID STORE', array(
            $this->compressMessageSet($_messages), $_mod . 'FLAGS.SILENT', "($_flag)"),
            self::COMMAND_NORESPONSE);
        
        return $result == self::ERROR_OK;
    }
    
    public function copy($_messages, $_from, $_to) {
        // Clear last COPYUID data
        unset($this->data['COPYUID']);
        
        if (!$this->select($_from)) {
            return false;
        }
        
        // Clear internal status cache
        unset($this->data['STATUS:'.$_to]);
        
        $result = $this->execute('UID COPY', array(
            $this->compressMessageSet($_messages), $this->escape($_to)),
            self::COMMAND_NORESPONSE);
        
        return $result == self::ERROR_OK;
    }
    
    public function move($_messages, $_from, $_to) {
        if (!$this->select($_from)) {
            return false;
        }
        
        if (!$this->data['READ-WRITE']) {
            $this->setError(self::ERROR_READONLY, "Mailbox is read-only");
            return false;
        }
        
        // use MOVE command (RFC 6851)
        if ($this->hasCapability('MOVE')) {
            // Clear last COPYUID data
            unset($this->data['COPYUID']);
            
            // Clear internal status cache
            unset($this->data['STATUS:'.$_to]);
            $this->clear_status_cache($_from);
            
            $result = $this->execute('UID MOVE', array(
                $this->compressMessageSet($_messages), $this->escape($_to)),
                self::COMMAND_NORESPONSE);
            
            return $result == self::ERROR_OK;
        }
        
        // use COPY + STORE +FLAGS.SILENT \Deleted + EXPUNGE
        $result = $this->copy($_messages, $_from, $_to);
        
        if ($result) {
            // Clear internal status cache
            unset($this->data['STATUS:'.$_from]);
            
            $result = $this->flag($_from, $_messages, 'DELETED');
            
            if ($_messages == '*') {
                // CLOSE+SELECT should be faster than EXPUNGE
                $this->close();
            }
            else {
                $this->expunge($_from, $_messages);
            }
        }
        
        return $result;
    }
    
    public function fetch($mailbox, $message_set, $is_uid = false, $query_items = array(), $mod_seq = null, $vanished = false) {
        if (!$this->select($mailbox)) {
            return false;
        }
        
        $message_set = $this->compressMessageSet($message_set);
        $result      = array();
        
        $key      = $this->nextTag();
        $cmd      = ($is_uid ? 'UID ' : '') . 'FETCH';
        $request  = "$key $cmd $message_set (" . implode(' ', $query_items) . ")";
        
        if ($mod_seq !== null && $this->hasCapability('CONDSTORE')) {
            $request .= " (CHANGEDSINCE $mod_seq" . ($vanished ? " VANISHED" : '') .")";
        }
        
        if (!$this->putLine($request)) {
            $this->setError(self::ERROR_COMMAND, "Failed to send $cmd command");
            return false;
        }
        
        do {
            $line = $this->readLine(4096);
            
            if (!$line) {
                break;
            }
            
            // Sample reply line:
            // * 321 FETCH (UID 2417 RFC822.SIZE 2730 FLAGS (\Seen)
            // INTERNALDATE "16-Nov-2008 21:08:46 +0100" BODYSTRUCTURE (...)
            // BODY[HEADER.FIELDS ...
            
            if (preg_match('/^\* ([0-9]+) FETCH/', $line, $m)) {
                $id = intval($m[1]);
                
                $result[$id]            = new rcube_message_header;
                $result[$id]->id        = $id;
                $result[$id]->subject   = '';
                $result[$id]->messageID = 'mid:' . $id;
                
                $headers = null;
                $lines   = array();
                $line    = substr($line, strlen($m[0]) + 2);
                $ln      = 0;
                
                // get complete entry
                while (preg_match('/\{([0-9]+)\}\r\n$/', $line, $m)) {
                    $bytes = $m[1];
                    $out   = '';
                    
                    while (strlen($out) < $bytes) {
                        $out = $this->readBytes($bytes);
                        if ($out === null) {
                            break;
                        }
                        $line .= $out;
                    }
                    
                    $str = $this->readLine(4096);
                    if ($str === false) {
                        break;
                    }
                    
                    $line .= $str;
                }
                
                // Tokenize response and assign to object properties
                while (list($name, $value) = $this->tokenizeResponse($line, 2)) {
                    if ($name == 'UID') {
                        $result[$id]->uid = intval($value);
                    }
                    else if ($name == 'RFC822.SIZE') {
                        $result[$id]->size = intval($value);
                    }
                    else if ($name == 'RFC822.TEXT') {
                        $result[$id]->body = $value;
                    }
                    else if ($name == 'INTERNALDATE') {
                        $result[$id]->internaldate = $value;
                        $result[$id]->date         = $value;
                        $result[$id]->timestamp    = RC_Helper::strtotime($value);
                    }
                    else if ($name == 'FLAGS') {
                        if (!empty($value)) {
                            foreach ((array)$value as $flag) {
                                $flag = str_replace(array('$', "\\"), '', $flag);
                                $flag = strtoupper($flag);
                                
                                $result[$id]->flags[$flag] = true;
                            }
                        }
                    }
                    else if ($name == 'MODSEQ') {
                        $result[$id]->modseq = $value[0];
                    }
                    else if ($name == 'ENVELOPE') {
                        $result[$id]->envelope = $value;
                    }
                    else if ($name == 'BODYSTRUCTURE' || ($name == 'BODY' && count($value) > 2)) {
                        if (!is_array($value[0]) && (strtolower($value[0]) == 'message' && strtolower($value[1]) == 'rfc822')) {
                            $value = array($value);
                        }
                        $result[$id]->bodystructure = $value;
                    }
                    else if ($name == 'RFC822') {
                        $result[$id]->body = $value;
                    }
                    else if (stripos($name, 'BODY[') === 0) {
                        $name = str_replace(']', '', substr($name, 5));
                        
                        if ($name == 'HEADER.FIELDS') {
                            // skip ']' after headers list
                            $this->tokenizeResponse($line, 1);
                            $headers = $this->tokenizeResponse($line, 1);
                        }
                        else if (strlen($name)) {
                            $result[$id]->bodypart[$name] = $value;
                        }
                        else {
                            $result[$id]->body = $value;
                        }
                    }
                }
                
                // create array with header field:data
                if (!empty($headers)) {
                    $headers = explode("\n", trim($headers));
                    foreach ($headers as $resln) {
                        if (ord($resln[0]) <= 32) {
                            $lines[$ln] .= (empty($lines[$ln]) ? '' : "\n") . trim($resln);
                        }
                        else {
                            $lines[++$ln] = trim($resln);
                        }
                    }
                    
                    foreach ($lines as $str) {
                        list($field, $string) = explode(':', $str, 2);
                        
                        $field  = strtolower($field);
                        $string = preg_replace('/\n[\t\s]*/', ' ', trim($string));
                        
                        switch ($field) {
                            case 'date';
                            $result[$id]->date = $string;
                            $result[$id]->timestamp = RC_Helper::strtotime($string);
                            break;
                            case 'to':
                                $result[$id]->to = preg_replace('/undisclosed-recipients:[;,]*/', '', $string);
                                break;
                            case 'from':
                            case 'subject':
                            case 'cc':
                            case 'bcc':
                            case 'references':
                                $result[$id]->{$field} = $string;
                                break;
                            case 'reply-to':
                                $result[$id]->replyto = $string;
                                break;
                            case 'content-transfer-encoding':
                                $result[$id]->encoding = $string;
                                break;
                            case 'content-type':
                                $ctype_parts = preg_split('/[; ]+/', $string);
                                $result[$id]->ctype = strtolower(array_shift($ctype_parts));
                                if (preg_match('/charset\s*=\s*"?([a-z0-9\-\.\_]+)"?/i', $string, $regs)) {
                                    $result[$id]->charset = $regs[1];
                                }
                                break;
                            case 'in-reply-to':
                                $result[$id]->in_reply_to = str_replace(array("\n", '<', '>'), '', $string);
                                break;
                            case 'return-receipt-to':
                            case 'disposition-notification-to':
                            case 'x-confirm-reading-to':
                                $result[$id]->mdn_to = $string;
                                break;
                            case 'message-id':
                                $result[$id]->messageID = $string;
                                break;
                            case 'x-priority':
                                if (preg_match('/^(\d+)/', $string, $matches)) {
                                    $result[$id]->priority = intval($matches[1]);
                                }
                                break;
                            default:
                                if (strlen($field) < 3) {
                                    break;
                                }
                                if ($result[$id]->others[$field]) {
                                    $string = array_merge((array)$result[$id]->others[$field], (array)$string);
                                }
                                $result[$id]->others[$field] = $string;
                        }
                    }
                }
            }
            // VANISHED response (QRESYNC RFC5162)
            // Sample: * VANISHED (EARLIER) 300:310,405,411
            else if (preg_match('/^\* VANISHED [()EARLIER]*/i', $line, $match)) {
                $line   = substr($line, strlen($match[0]));
                $v_data = $this->tokenizeResponse($line, 1);
                
                $this->data['VANISHED'] = $v_data;
            }
        }
        while (!$this->startsWith($line, $key, true));
        
        return $result;
    }
    
}

?>