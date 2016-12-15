<?php
namespace TKMF\LDAP;

/**
 * LDAPException
 * =============
 * @package TKMF\LDAP
 * @author Gaëtan Simon <gaetan.simon@thyssen.fr>
 */
class LDAPException extends \Exception {

    /**
     * @var int
     */
    private $_extended_errno;

    /**
     * @param resource $ldap_resource
     * @param null|string $extended_error
     */
    public function __construct($ldap_resource, $extended_error = null) {
        parent::__construct(
            ldap_error($ldap_resource),
            ldap_errno($ldap_resource)
        );
        $extended_errno = explode(':', $extended_error, 2);
        $this->_extended_errno = $extended_errno[0];
    }

    /**
     * @return int
     */
    public function getExtendedErrno() {
        return $this->_extended_errno;
    }

}