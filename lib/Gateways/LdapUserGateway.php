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
 * This Provides a Gateway to the LDAP
 */
class LdapUserGateway
{
    use LDAPGateway;

    /**
     * The Gateway requires a an ldap connection
     *
     */
    public function __construct()
    {
        $this->init_ldap();
    }


    public function findByDN(LDAPUser $LDAPUser): LDAPUser
    {
        $search = ldap_read($this->ldap_db,$LDAPUser->getDN(),"objectClass=*");
        $data = ldap_get_entries($this->ldap_db,$search);
        if($data["count"]==0){
            throw new Exception("No User with this DN could be found");
        }
        $LDAPUser->setDN($data[0]['dn']);
        return $LDAPUser;
    }
//
//    private function fillLdapUserDataPublicKey(LDAPUser &$LDAPUser)
//    {
//        $search = ldap_read($this->ldap_db,$LDAPUser->getDN(),"objectClass=ldapPublicKey");
//        $data = ldap_get_entries($this->ldap_db,$search);
//        if(isset($data[0]['sshpublickey']))
//        {
//            for($i=0;$i<$data[0]['sshpublickey']["count"];$i++)
//            {
//                $LDAPUser->addSSHPublicKey($data[0]['sshpublickey'][$i]);
//            }
//        }
//    }

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
