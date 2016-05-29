<?php
namespace TKMF\LDAP;

/**
 * LDAP
 * ====
 * @package TKMF\LDAP
 * @author Gaëtan Simon <gaetan.simon@thyssen.fr>
 */
class LDAP {

    /**
     * @var array */
    private $_options = [
        'debug' => false,
        'timeout' => 1
    ];

    /**
     * @var resource */
    private $_Sock = null;
    /**
     * @var string */
    private $_bindUser = null;
    /**
     * @var string */
    private $_bindPwd  = null;

    /**
     * Server to connect to.
     * @var string
     */
    private $_host;
    /**
     * Domain to connect to.
     * @var string
     */
    private $_domain;
    /**
     * Domain Controller (DC) to use.
     * @var string
     */
    private $_DC;

    /**
     * @param string $ldapserver <p>
     *  Host / domain controller (DC) to connect to...
     *  e.g: localhost/default.controller (connection to the DC default.controller on localhost).
     * </p>
     * @param null|string $bind_user
     * @param null|string $bind_pwd
     * @param array $options <p>
     *  (bool) debug Enable the LDAP Log
     * </p>
     */
    public function __construct($ldapserver, $bind_user = null, $bind_pwd = null, array $options = []) {
        $this->_setLdapServer($ldapserver);
        $this->_setBindUser($bind_user);
        $this->_setBindPwd($bind_pwd);
        $this->_setOptions($options);
    }

    public function __destruct() {
        if(is_resource($this->_Sock)) {
            ldap_unbind($this->_Sock);
            ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 0);
            $this->_Sock = null;
        }
    }

    public function __invoke() {
        try {
            return $this->_connect();
        } catch(LDAPException $e) {
            ob_start();
                header('Content-Type: text/plain; charset=UTF-8');
                echo "[Active Directory] {$e->getMessage()}";
            exit(ob_get_clean());
        }
    }

    /**
     * @param null|string|array $OU <p>
     *  Organizational Unit where performing query.
     *  Canonical format (e.g My/Organization/Unit)
     * </p>
     * @return LDAPSearch
     */
    public function search($OU = null) {
        $Search = new LDAPSearch($this, $OU);
        return $Search;
    }

    /**
     * @param string $fqdn
     * @param string $attribut
     * @param string|int|array $value
     * @return bool
     * @throws LDAPException
     */
    public function modify($fqdn, $attribut, $value) {
        $data = [$attribut => $value];
        $modify = ldap_mod_replace($this->_Sock, $fqdn, $data);
        if(!$modify)
            throw new LDAPException($this->_Sock);
        return $modify;
    }

    /**
     * Performs a query on the User Object.
     *
     * @param null|string|array $OU <p>
     *  Organizational Unit where performing query.
     *  Canonical format (e.g My/Organization/Unit)
     * </p>
     * @return LDAPUserSearch
     */
    public function searchUsers($OU = null) {
        $Search = new LDAPUserSearch($this, $OU);
        return $Search;
    }

    /**
     * Performs a query on the Group Object.
     * @return LDAPGroupSearch
     */
    public function searchGroups() {
        $Search = new LDAPGroupSearch($this);
        return $Search;
    }

    /**
     * @return string */
    public function getHost() { return $this->_host; }

    /**
     * @return string */
    public function getDomain() { return $this->_domain; }

    /**
     * @return string */
    public function getDC() { return $this->_DC; }

    /**
     * @return string */
    public function getUser() { return $this->_bindUser; }

    /**
     * Establishing the connection to the LDAP server.
     * @return resource
     * @throws LDAPException
     */
    private function _connect() {
        if(!is_resource($this->_Sock)) {
            $this->_Sock = ldap_connect("ldap://{$this->getHost()}");
            ldap_set_option($this->_Sock, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($this->_Sock, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($this->_Sock, LDAP_OPT_NETWORK_TIMEOUT, $this->_options['timeout']);

            ldap_start_tls($this->_Sock);

            $bind = @ldap_bind($this->_Sock, $this->_bindUser, $this->_bindPwd);
            if(!$bind) {
                throw new LDAPException($this->_Sock);
            }
        }
        return $this->_Sock;
    }

    /**
     * @param string $ldapserver <p>
     *  Host / domain controller (DC) to connect to...
     *  e.g: localhost/default.controller (connection to the DC default.controller on localhost).
     * </p>
     */
    private function _setLdapServer($ldapserver) {
        list($host, $DC) = explode('/', $ldapserver, 2);
        $this->_host = $host;
        $this->_domain = $DC;
        $this->_DC   = LDAPUtil::str2dnpart('DC', '.', $DC);
    }

    /**
     * @param null|string $samaccountname <p>
     *  User's account to bind.
     * </p>
     */
    private function _setBindUser($samaccountname) {
        if(!isset($samaccountname)) return;
        $this->_bindUser = $samaccountname . '@' . $this->getDomain();
    }

    /**
     * @param null|string $password <p>
     *  User's password.
     * </p>
     */
    private function _setBindPwd($password) {
        if(!isset($password)) return;
        $this->_bindPwd = $password;
    }

    /**
     * @param array $options */
    private function _setOptions(array $options) {
        $this->_options = array_merge($this->_options, $options);
        // Enable the LDAP Log
        ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, (($this->_options['debug'] === true) ? 7 : 0));
    }

}