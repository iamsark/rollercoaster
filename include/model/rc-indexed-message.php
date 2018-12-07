<?php 

class RC_IndexedMessage {
   
    public $incomplete = false;
    
    protected $raw_data;
    protected $mailbox;
    protected $meta   = array();
    protected $params = array();
    protected $order  = 'ASC';
    
    const SEPARATOR_ELEMENT = ' ';
    
    
    /**
     * Object constructor.
     */
    public function __construct($_mailbox = null, $_data = null, $_order = null) {
        $this->mailbox = $_mailbox;
        $this->order   = $_order == 'DESC' ? 'DESC' : 'ASC';
        $this->init($_data);
    }
    
    /**
     * Initializes object with SORT command response
     *
     * @param string $_data IMAP response string
     */
    public function init($_data = null) {
        $this->meta = array();
        
        $_data = explode('*', (string)$_data);
        
        // ...skip unilateral untagged server responses
        for ($i=0, $len=count($_data); $i<$len; $i++) {
            $data_item = &$_data[$i];
            if (preg_match('/^ SORT/i', $data_item)) {
                // valid response, initialize raw_data for is_error()
                $this->raw_data = '';
                $data_item = substr($data_item, 5);
                break;
            }
            else if (preg_match('/^ (E?SEARCH)/i', $data_item, $m)) {
                // valid response, initialize raw_data for is_error()
                $this->raw_data = '';
                $data_item = substr($data_item, strlen($m[0]));
                
                if (strtoupper($m[1]) == 'ESEARCH') {
                    $data_item = trim($data_item);
                    // remove MODSEQ response
                    if (preg_match('/\(MODSEQ ([0-9]+)\)$/i', $data_item, $m)) {
                        $data_item = substr($data_item, 0, -strlen($m[0]));
                        $this->params['MODSEQ'] = $m[1];
                    }
                    // remove TAG response part
                    if (preg_match('/^\(TAG ["a-z0-9]+\)\s*/i', $data_item, $m)) {
                        $data_item = substr($data_item, strlen($m[0]));
                    }
                    // remove UID
                    $data_item = preg_replace('/^UID\s*/i', '', $data_item);
                    
                    // ESEARCH parameters
                    while (preg_match('/^([a-z]+) ([0-9:,]+)\s*/i', $data_item, $m)) {
                        $param = strtoupper($m[1]);
                        $value = $m[2];
                        
                        $this->params[$param] = $value;
                        $data_item = substr($data_item, strlen($m[0]));
                        
                        if (in_array($param, array('COUNT', 'MIN', 'MAX'))) {
                            $this->meta[strtolower($param)] = (int) $value;
                        }
                    }
                    
                    // @TODO: Implement compression using compressMessageSet() in __sleep() and __wakeup() ?
                    // @TODO: work with compressed result?!
                    if (isset($this->params['ALL'])) {
                        $data_item = implode(self::SEPARATOR_ELEMENT,
                            RC_Helper::uncompressMessageSet($this->params['ALL']));
                    }
                }
                
                break;
            }
            
            unset($_data[$i]);
        }
        
        $_data = array_filter($_data);
        
        if (empty($_data)) {
            return;
        }
        
        $_data = array_shift($_data);
        $_data = trim($_data);
        $_data = preg_replace('/[\r\n]/', '', $_data);
        $_data = preg_replace('/\s+/', ' ', $_data);
        
        $this->raw_data = $_data;
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
     * Returns number of elements in the result
     *
     * @return int Number of elements
     */
    public function count() {
        if ($this->meta['count'] !== null)
            return $this->meta['count'];
            
        if (empty($this->raw_data)) {
            $this->meta['count']  = 0;
            $this->meta['length'] = 0;
        }
        else {
            $this->meta['count'] = 1 + substr_count($this->raw_data, self::SEPARATOR_ELEMENT);
        }
        
        return $this->meta['count'];
    }
    
    /**
     * Returns number of elements in the result.
     * Alias for count() for compatibility with rcube_result_thread
     *
     * @return int Number of elements
     */
    public function count_messages() {
        return $this->count();
    }
    
    /**
     * Returns maximal message identifier in the result
     *
     * @return int Maximal message identifier
     */
    public function max() {
        if (!isset($this->meta['max'])) {
            $this->meta['max'] = (int) @max($this->get());
        }        
        return $this->meta['max'];
    }
    
    /**
     * Returns minimal message identifier in the result
     *
     * @return int Minimal message identifier
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
    public function slice($_offset, $_length) {
        $data = $this->get();
        $data = array_slice($data, $_offset, $_length);        
        $this->meta          = array();
        $this->meta['count'] = count($data);
        $this->raw_data      = implode(self::SEPARATOR_ELEMENT, $data);
    }
    
    /**
     * Filters data set. Removes elements not listed in $ids list.
     *
     * @param array $ids List of IDs to remove.
     */
    public function filter($ids = array()) {
        $data = $this->get();
        $data = array_intersect($data, $ids);        
        $this->meta          = array();
        $this->meta['count'] = count($data);
        $this->raw_data      = implode(self::SEPARATOR_ELEMENT, $data);
    }
    
    /**
     * Reverts order of elements in the result
     */
    public function revert() {
        $this->order = $this->order == 'ASC' ? 'DESC' : 'ASC';        
        if (empty($this->raw_data)) {
            return;
        }        
        $data = $this->get();
        $data = array_reverse($data);
        $this->raw_data = implode(self::SEPARATOR_ELEMENT, $data);        
        $this->meta['pos'] = array();
    }
    
    /**
     * Check if the given message ID exists in the object
     *
     * @param int  $msgid     Message ID
     * @param bool $get_index When enabled element's index will be returned.
     *                        Elements are indexed starting with 0
     *
     * @return mixed False if message ID doesn't exist, True if exists or
     *               index of the element if $get_index=true
     */
    public function exists($_msgid, $_get_index = false) {
        if (empty($this->raw_data)) {
            return false;
        }        
        $_msgid = (int) $_msgid;
        $begin = implode('|', array('^', preg_quote(self::SEPARATOR_ELEMENT, '/')));
        $end   = implode('|', array('$', preg_quote(self::SEPARATOR_ELEMENT, '/')));        
        if (preg_match("/($begin)$_msgid($end)/", $this->raw_data, $m, $_get_index ? PREG_OFFSET_CAPTURE : null)) {
            if ($_get_index) {
                $idx = 0;
                if ($m[0][1]) {
                    $idx = 1 + substr_count($this->raw_data, self::SEPARATOR_ELEMENT, 0, $m[0][1]);
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
     * Return all messages in the result.
     *
     * @return array List of message IDs
     */
    public function get() {
        if (empty($this->raw_data)) {
            return array();
        }        
        return explode(self::SEPARATOR_ELEMENT, $this->raw_data);
    }
    
    /**
     * Return all messages in the result.
     *
     * @return array List of message IDs
     */
    public function get_compressed() {
        if (empty($this->raw_data)) {
            return '';
        }        
        return RC_Helper::compressMessageSet($this->get());
    }
    
    /**
     * Return result element at specified index
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
            $pos = strpos($this->raw_data, self::SEPARATOR_ELEMENT);
            if ($pos === false)
                $result = (int) $this->raw_data;
                else
                    $result = (int) substr($this->raw_data, 0, $pos);
                    
                    return $result;
        }        
        // last element
        if ($_index === 'LAST' || $_index == $count-1) {
            $pos = strrpos($this->raw_data, self::SEPARATOR_ELEMENT);
            if ($pos === false)
                $result = (int) $this->raw_data;
                else
                    $result = (int) substr($this->raw_data, $pos);
                    
                    return $result;
        }        
        // do we know the position of the element or the neighbour of it?
        if (!empty($this->meta['pos'])) {
            if (isset($this->meta['pos'][$_index]))
                $pos = $this->meta['pos'][$_index];
                else if (isset($this->meta['pos'][$_index-1]))
                    $pos = strpos($this->raw_data, self::SEPARATOR_ELEMENT,
                        $this->meta['pos'][$_index-1] + 1);
                    else if (isset($this->meta['pos'][$_index+1]))
                        $pos = strrpos($this->raw_data, self::SEPARATOR_ELEMENT,
                            $this->meta['pos'][$_index+1] - $this->length() - 1);
                        
                        if (isset($pos) && preg_match('/([0-9]+)/', $this->raw_data, $m, null, $pos)) {
                            return (int) $m[1];
                        }
        }        
        // Finally use less effective method
        $data = explode(self::SEPARATOR_ELEMENT, $this->raw_data);        
        return $data[$_index];
    }
    
    /**
     * Returns response parameters, e.g. ESEARCH's MIN/MAX/COUNT/ALL/MODSEQ
     * or internal data e.g. MAILBOX, ORDER
     *
     * @param string $param  Parameter name
     *
     * @return array|string Response parameters or parameter value
     */
    public function get_parameters($_param=null) {
        $params = $this->params;
        $params['MAILBOX'] = $this->mailbox;
        $params['ORDER']   = $this->order;        
        if ($_param !== null) {
            return $params[$_param];
        }        
        return $params;
    }
    
    /**
     * Returns length of internal data representation
     *
     * @return int Data length
     */
    protected function length() {
        if (!isset($this->meta['length'])) {
            $this->meta['length'] = strlen($this->raw_data);
        }        
        return $this->meta['length'];
    }
    
}


?>