<?php 

class RC_ThreadedMessage {
    
    public $incomplete = false;
    
    protected $raw_data;
    protected $mailbox;
    protected $meta = array();
    protected $order = 'ASC';
    
    const SEPARATOR_ELEMENT = ' ';
    const SEPARATOR_ITEM    = '~';
    const SEPARATOR_LEVEL   = ':';
    
    
    /**
     * Object constructor.
     */
    public function __construct($_mailbox = null, $_data = null) {
        $this->mailbox = $_mailbox;
        $this->init($_data);
    }
    
    /**
     * Initializes object with IMAP command response
     *
     * @param string $_data IMAP response string
     */
    public function init($_data = null) {
        $this->meta = array();
        
        $_data = explode('*', (string)$_data);
        
        // ...skip unilateral untagged server responses
        for ($i=0, $len=count($_data); $i<$len; $i++) {
            if (preg_match('/^ THREAD/i', $_data[$i])) {
                // valid response, initialize raw_data for is_error()
                $this->raw_data = '';
                $_data[$i] = substr($_data[$i], 7);
                break;
            }
            
            unset($_data[$i]);
        }
        
        if (empty($_data)) {
            return;
        }
        
        $_data = array_shift($_data);
        $_data = trim($_data);
        $_data = preg_replace('/[\r\n]/', '', $_data);
        $_data = preg_replace('/\s+/', ' ', $_data);
        
        $this->raw_data = $this->parse_thread($_data);
    }
    
    /**
     * Checks the result from IMAP command
     *
     * @return bool True if the result is an error, False otherwise
     */
    public function is_error() {
        return $this->raw_data === null;
    }
    
    /**
     * Checks if the result is empty
     *
     * @return bool True if the result is empty, False otherwise
     */
    public function is_empty() {
        return empty($this->raw_data);
    }
    
    /**
     * Returns number of elements (threads) in the result
     *
     * @return int Number of elements
     */
    public function count() {
        if ($this->meta['count'] !== null)
            return $this->meta['count'];
            
        if (empty($this->raw_data)) {
            $this->meta['count'] = 0;
        }
        else {
            $this->meta['count'] = 1 + substr_count($this->raw_data, self::SEPARATOR_ELEMENT);
        }
        
        if (!$this->meta['count'])
            $this->meta['messages'] = 0;
            
        return $this->meta['count'];
    }
    
    /**
     * Returns number of all messages in the result
     *
     * @return int Number of elements
     */
    public function count_messages() {
        if ($this->meta['messages'] !== null)
            return $this->meta['messages'];
        
        if (empty($this->raw_data)) {
            $this->meta['messages'] = 0;
        }
        else {
            $this->meta['messages'] = 1
            + substr_count($this->raw_data, self::SEPARATOR_ELEMENT)
            + substr_count($this->raw_data, self::SEPARATOR_ITEM);
        }
        
        if ($this->meta['messages'] == 0 || $this->meta['messages'] == 1)
            $this->meta['count'] = $this->meta['messages'];
            
        return $this->meta['messages'];
    }
    
    /**
     * Returns maximum message identifier in the result
     *
     * @return int Maximum message identifier
     */
    public function max() {
        if (!isset($this->meta['max'])) {
            $this->meta['max'] = (int) @max($this->get());
        }
        return $this->meta['max'];
    }
    
    /**
     * Returns minimum message identifier in the result
     *
     * @return int Minimum message identifier
     */
    public function min() {
        if (!isset($this->meta['min'])) {
            $this->meta['min'] = (int) @min($this->get());
        }
        return $this->meta['min'];
    }
    
    /**
     * Slices data set.
     *
     * @param $offset (as for PHP's array_slice())
     * @param $length Number of elements (as for PHP's array_slice())
     */
    public function slice($offset, $length) {
        $data = explode(self::SEPARATOR_ELEMENT, $this->raw_data);
        $data = array_slice($data, $offset, $length);
        
        $this->meta          = array();
        $this->meta['count'] = count($data);
        $this->raw_data      = implode(self::SEPARATOR_ELEMENT, $data);
    }
    
    /**
     * Filters data set. Removes threads not listed in $roots list.
     *
     * @param array $roots List of IDs of thread roots.
     */
    public function filter($_roots) {
        $datalen = strlen($this->raw_data);
        $_roots   = array_flip($_roots);
        $result  = '';
        $start   = 0;
        
        $this->meta          = array();
        $this->meta['count'] = 0;
        
        while (($pos = @strpos($this->raw_data, self::SEPARATOR_ELEMENT, $start))
            || ($start < $datalen && ($pos = $datalen))) {
            $len   = $pos - $start;
            $elem  = substr($this->raw_data, $start, $len);
            $start = $pos + 1;
            
            // extract root message ID
            if ($npos = strpos($elem, self::SEPARATOR_ITEM)) {
                $root = (int) substr($elem, 0, $npos);
            }
            else {
                $root = $elem;
            }
            
            if (isset($_roots[$root])) {
                $this->meta['count']++;
                $result .= self::SEPARATOR_ELEMENT . $elem;
            }
        }
            
        $this->raw_data = ltrim($result, self::SEPARATOR_ELEMENT);
    }
    
    /**
     * Reverts order of elements in the result
     */
    public function revert() {
        $this->order = $this->order == 'ASC' ? 'DESC' : 'ASC';
        
        if (empty($this->raw_data)) {
            return;
        }
        
        $data = explode(self::SEPARATOR_ELEMENT, $this->raw_data);
        $data = array_reverse($data);
        $this->raw_data = implode(self::SEPARATOR_ELEMENT, $data);
        
        $this->meta['pos'] = array();
    }
    
    /**
     * Check if the given message ID exists in the object
     *
     * @param int $msgid Message ID
     * @param bool $get_index When enabled element's index will be returned.
     *                        Elements are indexed starting with 0
     *
     * @return boolean True on success, False if message ID doesn't exist
     */
    public function exists($_msgid, $_get_index = false) {
        $_msgid = (int) $_msgid;
        $begin = implode('|', array(
            '^',
            preg_quote(self::SEPARATOR_ELEMENT, '/'),
            preg_quote(self::SEPARATOR_LEVEL, '/'),
        ));
        $end = implode('|', array(
            '$',
            preg_quote(self::SEPARATOR_ELEMENT, '/'),
            preg_quote(self::SEPARATOR_ITEM, '/'),
        ));
        
        if (preg_match("/($begin)$_msgid($end)/", $this->raw_data, $m,
            $_get_index ? PREG_OFFSET_CAPTURE : null)) {
                if ($_get_index) {
                $idx = 0;
                if ($m[0][1]) {
                    $idx = substr_count($this->raw_data, self::SEPARATOR_ELEMENT, 0, $m[0][1]+1)
                    + substr_count($this->raw_data, self::SEPARATOR_ITEM, 0, $m[0][1]+1);
                }
                // cache position of this element, so we can use it in get_element()
                $this->meta['pos'][$idx] = (int)$m[0][1];
                
                return $idx;
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Return IDs of all messages in the result. Threaded data will be flattened.
     *
     * @return array List of message identifiers
     */
    public function get() {
        if (empty($this->raw_data)) {
            return array();
        }
        
        $regexp = '/(' . preg_quote(self::SEPARATOR_ELEMENT, '/')
        . '|' . preg_quote(self::SEPARATOR_ITEM, '/') . '[0-9]+' . preg_quote(self::SEPARATOR_LEVEL, '/')
        .')/';
        
        return preg_split($regexp, $this->raw_data);
    }
    
    /**
     * Return all messages in the result.
     *
     * @return array List of message identifiers
     */
    public function get_compressed() {
        if (empty($this->raw_data)) {
            return '';
        }
        
        return RC_Helper::compressMessageSet($this->get());
    }
    
    /**
     * Return result element at specified index (all messages, not roots)
     *
     * @param int|string  $index  Element's index or "FIRST" or "LAST"
     *
     * @return int Element value
     */
    public function get_element($_index) {
        $count = $this->count();
        
        if (!$count) {
            return null;
        }
        
        // first element
        if ($_index === 0 || $_index === '0' || $_index === 'FIRST') {
            preg_match('/^([0-9]+)/', $this->raw_data, $m);
            $result = (int) $m[1];
            return $result;
        }
        
        // last element
        if ($_index === 'LAST' || $_index == $count-1) {
            preg_match('/([0-9]+)$/', $this->raw_data, $m);
            $result = (int) $m[1];
            return $result;
        }
        
        // do we know the position of the element or the neighbour of it?
        if (!empty($this->meta['pos'])) {
            $element = preg_quote(self::SEPARATOR_ELEMENT, '/');
            $item    = preg_quote(self::SEPARATOR_ITEM, '/') . '[0-9]+' . preg_quote(self::SEPARATOR_LEVEL, '/') .'?';
            $regexp  = '(' . $element . '|' . $item . ')';
            
            if (isset($this->meta['pos'][$_index])) {
                if (preg_match('/([0-9]+)/', $this->raw_data, $m, null, $this->meta['pos'][$_index]))
                    $result = $m[1];
            }
            else if (isset($this->meta['pos'][$_index-1])) {
                // get chunk of data after previous element
                $data = substr($this->raw_data, $this->meta['pos'][$_index-1]+1, 50);
                $data = preg_replace('/^[0-9]+/', '', $data); // remove UID at $_index position
                $data = preg_replace("/^$regexp/", '', $data); // remove separator
                if (preg_match('/^([0-9]+)/', $data, $m))
                    $result = $m[1];
            }
            else if (isset($this->meta['pos'][$_index+1])) {
                // get chunk of data before next element
                $pos  = max(0, $this->meta['pos'][$_index+1] - 50);
                $len  = min(50, $this->meta['pos'][$_index+1]);
                $data = substr($this->raw_data, $pos, $len);
                $data = preg_replace("/$regexp\$/", '', $data); // remove separator
                
                if (preg_match('/([0-9]+)$/', $data, $m))
                    $result = $m[1];
            }
            
            if (isset($result)) {
                return (int) $result;
            }
        }
        
        // Finally use less effective method
        $data = $this->get();
        
        return $data[$_index];
    }
    
    /**
     * Returns response parameters e.g. MAILBOX, ORDER
     *
     * @param string $param Parameter name
     *
     * @return array|string Response parameters or parameter value
     */
    public function get_parameters($_param=null) {
        $params = array();
        $params['MAILBOX'] = $this->mailbox;
        $params['ORDER']   = $this->order;
        
        if ($_param !== null) {
            return $params[$_param];
        }
        
        return $params;
    }
    
    /**
     * THREAD=REFS sorting implementation (based on provided index)
     *
     * @param RC_IndexedMessage $_index  Sorted message identifiers
     */
    public function sort($_index) {
        $this->sort_order = $_index->get_parameters('ORDER');
        
        if (empty($this->raw_data)) {
            return;
        }
        
        // when sorting search result it's good to make the index smaller
        if ($_index->count() != $this->count_messages()) {
            $_index->filter($this->get());
        }
        
        $result  = array_fill_keys($_index->get(), null);
        $datalen = strlen($this->raw_data);
        $start   = 0;
        
        // Here we're parsing raw_data twice, we want only one big array
        // in memory at a time
        
        // Assign roots
        while (($pos = @strpos($this->raw_data, self::SEPARATOR_ELEMENT, $start))
            || ($start < $datalen && ($pos = $datalen))) {
            $len   = $pos - $start;
            $elem  = substr($this->raw_data, $start, $len);
            $start = $pos + 1;
            
            $items = explode(self::SEPARATOR_ITEM, $elem);
            $root  = (int) array_shift($items);
            
            if ($root) {
                $result[$root] = $root;
                foreach ($items as $item) {
                    list($lv, $id) = explode(self::SEPARATOR_LEVEL, $item);
                    $result[$id] = $root;
                }
            }
        }
        
        // get only unique roots
        $result = array_filter($result); // make sure there are no nulls
        $result = array_unique($result);
        
        // Re-sort raw data
        $result = array_fill_keys($result, null);
        $start = 0;
            
        while (($pos = @strpos($this->raw_data, self::SEPARATOR_ELEMENT, $start))
            || ($start < $datalen && ($pos = $datalen))) {
            $len   = $pos - $start;
            $elem  = substr($this->raw_data, $start, $len);
            $start = $pos + 1;
            
            $npos = strpos($elem, self::SEPARATOR_ITEM);
            $root = (int) ($npos ? substr($elem, 0, $npos) : $elem);
            
            $result[$root] = $elem;
        }
        
        $this->raw_data = implode(self::SEPARATOR_ELEMENT, $result);
    }
    
    /**
     * Returns data as tree
     *
     * @return array Data tree
     */
    public function get_tree() {
        $datalen = strlen($this->raw_data);
        $result  = array();
        $start   = 0;
        
        while (($pos = @strpos($this->raw_data, self::SEPARATOR_ELEMENT, $start))
            || ($start < $datalen && ($pos = $datalen))) {
            $len   = $pos - $start;
            $elem  = substr($this->raw_data, $start, $len);
            $items = explode(self::SEPARATOR_ITEM, $elem);
            $result[array_shift($items)] = $this->build_thread($items);
            $start = $pos + 1;
        }
        
        return $result;
    }
    
    /**
     * Returns thread depth and children data
     *
     * @return array Thread data
     */
    public function get_thread_data() {
        $data     = $this->get_tree();
        $depth    = array();
        $children = array();
        
        $this->build_thread_data($data, $depth, $children);
        
        return array($depth, $children);
    }
    
    /**
     * Creates 'depth' and 'children' arrays from stored thread 'tree' data.
     */
    protected function build_thread_data($_data, &$_depth, &$_children, $_level = 0) {
        foreach ((array)$_data as $key => $val) {
            $empty          = empty($val) || !is_array($val);
            $_children[$key] = !$empty;
            $_depth[$key]    = $_level;
            if (!$empty) {
                $this->build_thread_data($val, $_depth, $_children, $_level + 1);
            }
        }
    }
    
    /**
     * Converts part of the raw thread into an array
     */
    protected function build_thread($_items, $_level = 1, &$_pos = 0) {
        $result = array();
        
        for ($len=count($_items); $_pos < $len; $_pos++) {
            list($lv, $id) = explode(self::SEPARATOR_LEVEL, $_items[$_pos]);
            if ($_level == $lv) {
                $_pos++;
                $result[$id] = $this->build_thread($_items, $_level+1, $_pos);
            }
            else {
                $_pos--;
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * IMAP THREAD response parser
     */
    protected function parse_thread($_str, $_begin = 0, $_end = 0, $_depth = 0) {
        // Don't be tempted to change $_str to pass by reference to speed this up - it will slow it down by about
        // 7 times instead :-) See comments on http://uk2.php.net/references and this article:
        // http://derickrethans.nl/files/phparch-php-variables-article.pdf
        $node = '';
        if (!$_end) {
            $_end = strlen($_str);
        }
        
        // Let's try to store data in max. compacted stracture as a string,
        // arrays handling is much more expensive
        // For the following structure: THREAD (2)(3 6 (4 23)(44 7 96))
        // -- 2
        // -- 3
        //     \-- 6
        //         |-- 4
        //         |    \-- 23
        //         |
        //         \-- 44
        //               \-- 7
        //                    \-- 96
        //
        // The output will be: 2,3^1:6^2:4^3:23^2:44^3:7^4:96
        
        if ($_str[$_begin] != '(') {
            // find next bracket
            $stop      = $_begin + strcspn($_str, '()', $_begin, $_end - $_begin);
            $messages  = explode(' ', trim(substr($_str, $_begin, $stop - $_begin)));
            
            if (empty($messages)) {
                return $node;
            }
            
            foreach ($messages as $msg) {
                if ($msg) {
                    $node .= ($_depth ? self::SEPARATOR_ITEM.$_depth.self::SEPARATOR_LEVEL : '').$msg;
                    $this->meta['messages']++;
                    $_depth++;
                }
            }
            
            if ($stop < $_end) {
                $node .= $this->parse_thread($_str, $stop, $_end, $_depth);
            }
        }
        else {
            $off = $_begin;
            while ($off < $_end) {
                $start = $off;
                $off++;
                $n = 1;
                while ($n > 0) {
                    $p = strpos($_str, ')', $off);
                    if ($p === false) {
                        // error, wrong structure, mismatched brackets in IMAP THREAD response
                        // @TODO: write error to the log or maybe set $this->raw_data = null;
                        return $node;
                    }
                    $p1 = strpos($_str, '(', $off);
                    if ($p1 !== false && $p1 < $p) {
                        $off = $p1 + 1;
                        $n++;
                    }
                    else {
                        $off = $p + 1;
                        $n--;
                    }
                }
                
                $thread = $this->parse_thread($_str, $start + 1, $off - 1, $_depth);
                if ($thread) {
                    if (!$_depth) {
                        if ($node) {
                            $node .= self::SEPARATOR_ELEMENT;
                        }
                    }
                    $node .= $thread;
                }
            }
        }
        
        return $node;
    }
    
}

?>