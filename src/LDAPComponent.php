<?php
namespace TKMF\LDAP;

/**
 * LDAPComponent
 * =============
 * @package TKMF\LDAP
 * @author Gaëtan Simon <gaetan.simon@thyssen.fr>
 */
abstract class LDAPComponent {

    /**
     * @var LDAP */
    private $_LDAP = null;

    /**
     * @param LDAP $LDAP */
    public function __construct(LDAP $LDAP) {
        $this->_LDAP = $LDAP;
    }

    /**
     * @param bool $resource_only <p>
     *  If TRUE the "ldap identifier" will be returned.
     * </p>
     * @return ldap identifier|LDAP
     */
    public function ldap($resource_only = false) {
        return  ($resource_only) ? call_user_func($this->_LDAP) : $this->_LDAP;
    }

}