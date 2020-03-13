<?php
//    Copyright (c) 2020 Norbert Rühl
//    
//    This software is provided 'as-is', without any express or implied warranty. In no event will the authors be held liable for any damages arising from the use of this software.
//    
//    Permission is granted to anyone to use this software for any purpose, including commercial applications, and to alter it and redistribute it freely, subject to the following restrictions:
//    
//        1. The origin of this software must not be misrepresented; you must not claim that you wrote the original software. If you use this software in a product, an acknowledgment in the product documentation would be appreciated but is not required.
//    
//        2. Altered source versions must be plainly marked as such, and must not be misrepresented as being the original software.
//    
//        3. This notice may not be removed or altered from any source distribution.
?>
<?php
require_once __DIR__ . '/Traits/SQLGateway.php';
require_once __DIR__ . '/Traits/LDAPGateway.php';
require_once __DIR__ . '/../Models/LDAPUser.php';

/**
 * Class LdapUserGateway
 * This Provides a Gateway to the Database Items of a LDAP user, mainly his associated internal id and his dn from the Ldap
 */
class LdapUserGateway
{
    use LDAPGateway;

    /**
     * The Gateway requires a sql and an ldap connection
     * LdapUserGateway constructor.
     */
    public function __construct()
    {
        $this->init_ldap();
    }

    public function fillUser(LDAPUser $LDAPUser): LDAPUser
    {
        //todo instead of two searches, maybe, one search and distinguish based on ldap type
        $this->fillLdapUserDataPosixAccount($LDAPUser);
        $this->fillLdapUserDataPublicKey($LDAPUser);
        return  $LDAPUser;
    }

    private function fillLdapUserDataPosixAccount(LDAPUser &$LDAPUser)
    {
        $search = ldap_read($this->ldap_db,$LDAPUser->getDN(),"objectClass=PosixAccount");
        $data = ldap_get_entries($this->ldap_db,$search);
        $LDAPUser->setCn($data[0]['cn'][0]);
        $LDAPUser->setUid($data[0]['uid'][0]);
        $LDAPUser->setGidNUmber($data[0]['gidnumber'][0]);
        $LDAPUser->setUidNUmber($data[0]['uidnumber'][0]);
        $LDAPUser->setHomeDirectory($data[0]['homedirectory'][0]);
        if(isset($data[0]['loginshell']))
        {
            $LDAPUser->setLoginShell($data[0]['loginshell'][0]);
        }
    }

    private function fillLdapUserDataPublicKey(LDAPUser &$LDAPUser)
    {
        $search = ldap_read($this->ldap_db,$LDAPUser->getDN(),"objectClass=ldapPublicKey");
        $data = ldap_get_entries($this->ldap_db,$search);
        if(isset($data[0]['sshpublickey']))
        {
            for($i=0;$i<$data[0]['sshpublickey']["count"];$i++)
            {
                $LDAPUser->addSSHPublicKey($data[0]['sshpublickey'][$i]);
            }
        }
    }

    /**
     * Authenticates a user against the LDAP DB, if no mapping exists locally, it is tried to be added
     * @param $username
     * @param $password
     * @return LDAPUser
     * @throws Exception
     */
    public function AuthenticateUser($username,$password): LDAPUser
    {
        $ldap_config = new LDAPConfig();
        $ldap_username = "cn=".ldap_escape($username,"",LDAP_ESCAPE_FILTER).",".$ldap_config->getBaseDN();
        $authenticated = @ldap_bind($this->ldap_db,$ldap_username,$password);
        if(!$authenticated)
        {
            throw new Exception("Could not Authenticate against LDAP");
        }
        $LDAPUser = new LDAPUser();
        $LDAPUser->setDN($ldap_username);
        //todo fill with data?
        //Check If User Exists
        return $LDAPUser;
    }

    public function ChangePassword(LDAPUser $LDAPUser,string $password): void
    {
        $salt = substr(bin2hex(openssl_random_pseudo_bytes(16)),0,16);
        $values["userPassword"] = "{CRYPT}".crypt($password,'$6$'.$salt);
        if(! ldap_modify($this->ldap_db,$LDAPUser->getDn(),$values))
        {
            throw new Exception("Could not Modify Password");
        }
    }

    public function ChangeEmail(LDAPUser $LDAPUser,string $email): void
    {
        //todo check for email is unique and can be changed
        $values["email"] = $email;
        if(! ldap_modify($this->ldap_db,$LDAPUser->getDn(),$values))
        {
            throw new Exception("Could not Modify Email");
        }
    }
}
