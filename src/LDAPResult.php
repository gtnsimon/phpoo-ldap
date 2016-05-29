<?php
namespace   TKMF\LDAP;
use         Countable;

/**
 * LDAPResult
 * ==========
 * @package TKMF\LDAP
 * @author Gaëtan Simon <gaetan.simon@thyssen.fr>
 */
class LDAPResult extends LDAPComponent implements Countable {

    /**
     * @var ldap result */
    private $_ldap_result = null;
    /**
     * Number of entries.
     * @var int
     */
    private $_count = -1;

    /**
     * @var array */
    private $_searchAttributes = [];

    /**
     * @var array */
    private $_data = [];

    public function __construct(LDAP $LDAP, $ldap_result, array $attributes) {
        parent::__construct($LDAP);
        $this->_searchAttributes = array_map('strtolower', array_unique($attributes));
        $this->_setData($ldap_result);
    }

    public function data() { return $this->_data; }

    private function _setData($ldap_result) {
        if(!is_resource($ldap_result) || get_resource_type($ldap_result) !== "ldap result") {
            throw new \InvalidArgumentException("You must specify an \"ldap result\" resource");
        }
        if($this->_count < 0) {
            $count = ldap_count_entries($this->ldap(true), $ldap_result);
            $this->_count = ($count !== false) ? $count : 0;
        }
        if($this->_count > 0) {
            $entries = ldap_get_entries($this->ldap(true), $ldap_result);
            unset($entries['count']);
            foreach($entries as $k => $entry) {
                $this->_data[$k] = $this->_clear($entry);
            }
            ldap_free_result($ldap_result);
        }
    }

    private function _clear(array $entry) {
        $j = $entry['count'];
        if($j > 0) {
            for($i = 0; $i < $j; $i++) {
                $key = $entry[$i];
                $arr = $entry[$key];
                if($arr['count'] === 1) {
                    $entry[$key] = $arr[0];
                } else { unset($entry[$key]['count']); }
                unset($entry[$i], $entry['count']);
            }
            foreach($this->_searchAttributes as $attr) {
                if($attr !== '*' && !array_key_exists($attr, $entry))
                    $entry[$attr] = null;
            }
        } else {
            unset($entry['count']);
        }
        return $entry;
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count() {
        return $this->_count;
    }

}