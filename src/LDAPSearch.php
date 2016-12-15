<?php
namespace TKMF\LDAP;

/**
 * LDAPSearch
 * ==========
 * @package TKMF\LDAP
 * @author Gaëtan Simon <gaetan.simon@thyssen.fr>
 */
class LDAPSearch extends LDAPComponent
{

    /**
     * @var int */
    const ACCOUNT_ENABLED_FILTER = 512;
    /**
     * @var int */
    const ACCOUNT_DISABLED_FILTER = 514;

    /**
     * @var int */
    const USER_OBJECT_FILTER = 0x30000000;
    /**
     * @var int */
    const COMPUTER_OBJECT_FILTER = 0x30000001;
    /**
     * @var int */
    const GROUP_OBJECT_FILTER = 0x10000000;

    /**
     * @var int */
    const AND_CLAUSE_FILTER = 0x26;
    /**
     * @var int */
    const NOT_CLAUSE_FILTER = 0x21;
    /**
     * @var int */
    const OR_CLAUSE_FILTER = 0x7C;

    /**
     * @var array */
    private static $clausesPos = [self::AND_CLAUSE_FILTER, self::OR_CLAUSE_FILTER, self::NOT_CLAUSE_FILTER];

    /**
     * @var array */
    private $_filter = [];
    /**
     * @var string */
    private $_filterstr;

    /**
     * @var array */
    private $_fetchAttributes = ['dn'];

    /**
     * @var null|string */
    private $_OU = null;

    /**
     * @param LDAP $LDAP
     * @param null|string|array $OU <p>
     *  Organizational Unit where performing query.
     *  Canonical format (e.g My/Organization/Unit)
     * </p>
     */
    public function __construct(LDAP $LDAP, $OU = null) {
        parent::__construct($LDAP);
        $this->_setOU($OU);
    }

    /**
     * Add a filter to the query.
     *
     * @param string $attribut
     * @param mixed $value
     * @return $this
     */
    public function where($attribut, $value) {
        $this->_addFilter($attribut, $value);
        $this->addAttribute($attribut);
        return $this;
    }

    /**
     * Add a LIKE filter to the query.
     *
     * @param string $attribut
     * @param mixed $value
     * @return $this
     */
    public function like($attribut, $value) {
        $this->_addFilter($attribut, '*' . LDAPUtil::escape($value) . '*');
        $this->addAttribute($attribut);
        return $this;
    }

    /**
     * Execute the query with a list of attributes to get back.
     *
     * @param array $attributes
     * @return bool|LDAPResult
     * @throws LDAPException
     */
    public function fetch(array $attributes = []) {
        // if(empty($attributes)) $attributes[] = '*';
        $this->_fetchAttributes = array_merge($this->_fetchAttributes, $attributes);
        return $this->execute();
    }

    /**
     * @return bool|LDAPResult
     * @throws LDAPException
     */
    public function execute() {
        // var_dump($this->_filter);
        $context = trim(implode(',', [$this->_OU, $this->ldap()->getDC()]), ',');
        $Search = @ldap_search($this->ldap(true), $context, $this->_getFilter(), $this->_fetchAttributes, 0, 0, 2);
        if(!$Search) {
            throw new LDAPException($this->ldap(true));
        }
        $Result = new LDAPResult($this->ldap(), $Search, $this->_fetchAttributes);
        return (count($Result) > 0) ? $Result : false;
    }

    /**
     * @param string $attribute Attribute to fetch
     */
    protected function addAttribute($attribute) {
        $this->_fetchAttributes[] = $attribute;
    }

    /**
     * Append a new filter to the query search.
     *
     * @param string $attribut
     * @param string|array $value
     * @param int $clause
     */
    private function _addFilter($attribut, $value, $clause = self::AND_CLAUSE_FILTER) {
        /**
         * @param &$value
         * @param $clause
         * @return int
         */
        $NOT = function(&$value, $clause) {
            if(ord(substr($value, 0,1)) === self::NOT_CLAUSE_FILTER)  {
                $value = substr($value, 1);
                return self::NOT_CLAUSE_FILTER;
            }
            return $clause;
        };
        if(is_array($value)) {
            $clause = (count($value) > 1) ? self::OR_CLAUSE_FILTER : self::AND_CLAUSE_FILTER;
            foreach($value as $v) {
                $clause = $NOT($v, $clause);
                $this->_addFilter($attribut, $v, $clause);
            }
            return;
        } else {
            $clause = $NOT($value, $clause);
        }
        $this->_filter[$clause][$attribut][] = $value;
    }

    /**
     * Creates the filter to send to the query.
     * @return string
     */
    private function _getFilter() {
        $filter = [];
        $string = [];
        ksort($this->_filter);
        $clauses = array_flip(self::$clausesPos);
        foreach($this->_filter as $clause => $attributes) {
            $i = $clauses[$clause];
            foreach($attributes as $attribut => $values) {
                foreach($values as $value) {
                    $tmp = ltrim($attribut . '=' . $value, '=');
                    $filter[chr($clause)][] = "({$tmp})";
                }
            }
            $clause = chr($clause);
            $filter[$clause] = array_unique($filter[$clause]);
            $string[$i] = '(' . $clause . implode('', $filter[$clause]);
        }
        ksort($string);
        $string =  implode('', $string) . str_repeat(')', ((count($string) > 1) + 1));
        //var_dump($string);
        return $string;
    }

    /**
     * @param null|string|array $OU <p>
     *  Organizational Unit where performing query.
     *  Canonical format (e.g My/Organization/Unit)
     * </p>
     */
    private function _setOU($OU) {
        if($OU === null)
            return;
        elseif(is_array($OU)) {
            if(count($OU) === 1) {
                $this->_setOU($OU[0]);
                return;
            }
            $OU2str = [];
            foreach($OU as $k => $v) {
                $OU2str[] = LDAPUtil::str2dnpart('OU', '/', $v);
            }
            $this->_addFilter('', $OU2str, self::OR_CLAUSE_FILTER);
        } else {
            $this->_OU = LDAPUtil::str2dnpart('OU', '/', $OU);
        }
    }

}