<?php
namespace   TKMF\LDAP;

/**
 * LDAPUserSearch
 * ==============
 * @package TKMF\LDAP
 * @author Gaëtan Simon <gaetan.simon@thyssen.fr>
 */
final class LDAPUserSearch extends LDAPSearch {

    public function __construct(LDAP $LDAP, $OU = null) {
        parent::__construct($LDAP, $OU);
        $this->where('sAMAccountType', $this::USER_OBJECT_FILTER);
        $this->addAttribute('sAMAccountName');
    }

    /**
     * @param string $samaccountname Username
     * @param array $attributes Attributes to get back
     * @return bool|array FALSE if the user doesn't exists
     */
    public function account($samaccountname, array $attributes = []) {
        if(is_array($samaccountname)) {
            throw new \LogicException("You cannot query severals users with this method.");
        }
        $this->where('sAMAccountName', trim(LDAPUtil::escape($samaccountname), '!'));
        $data = $this->fetch($attributes);
        return ($data !== false) ? $data->data()[0] : $data;
    }
    
}