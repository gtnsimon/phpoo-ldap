<?php
namespace   TKMF\LDAP;

/**
 * LDAPGroupSearch
 * ===============
 * @package TKMF\LDAP
 * @author Gaëtan Simon <gaetan.simon@thyssen.fr>
 */
class LDAPGroupSearch extends LDAPSearch {

    public function __construct(LDAP $LDAP) {
        parent::__construct($LDAP);
        $this->where('sAMAccountType', $this::GROUP_OBJECT_FILTER);
    }

}