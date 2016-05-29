<?php
namespace TKMF\LDAP;

/**
 * LDAPException
 * =============
 * @package TKMF\LDAP
 * @author Gaëtan Simon <gaetan.simon@thyssen.fr>
 */
class LDAPException extends \Exception {

    public function __construct($ldap_resource) {
        parent::__construct(
            ldap_error($ldap_resource),
            ldap_errno($ldap_resource)
        );
    }

}