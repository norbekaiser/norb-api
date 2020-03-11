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
    use SQLGateway, LDAPGateway;

    /**
     * The Gateway requires a sql and an ldap connection
     * LdapUserGateway constructor.
     */
    public function __construct()
    {
        $this->init_sql();
        $this->init_ldap();
    }

    /**
     * Helper Function used to map mysqli results to the result
     * @param mysqli_result $result
     * @return LDAPUser
     */
    private function result_to_LDAPUser(mysqli_result $result) :LDAPUser
    {
        $userData = $result->fetch_assoc();
        $LDAPUser = new LDAPUser();
        $LDAPUser->setUsrId($userData['usr_id']);
        $LDAPUser->setMemberSince($userData['member_since']);
        $LDAPUser->setDN($userData['dn']);
        return $LDAPUser;
    }

    public function findUserID(int $usr_id): LDAPUser
    {
        $query = <<<'SQL'
            SELECT users_id.id as usr_id,
                   users_id.member_since as member_since,
                   users_ldap.dn as dn
            FROM users_ldap 
            INNER JOIN users_id ON users_ldap.usr_id = users_id.id 
            WHERE usr_id=? LIMIT 1
        SQL;
        $stmt = $this->sql_db->prepare($query);
        $stmt->bind_param('i',$usr_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows != 1)
        {
            throw new Exception("LDAPUser Could not be found");
        }
        $res =  $this->result_to_LDAPUser($result);
        $this->fillLdapUserData($res);
        return $res;
    }

    /**
     * Finds a Ldap User in the Local Table, he will be there if he hase once used the service
     * @param string $dn
     * @return LDAPUser
     * @throws Exception
     */
    public function findUserDN(string $dn): LDAPUser
    {
        $query = <<<'SQL'
            SELECT users_id.id as usr_id,
                   users_id.member_since as member_since,
                   users_ldap.dn as dn
            FROM users_ldap 
            INNER JOIN users_id ON users_ldap.usr_id = users_id.id 
            WHERE dn=? LIMIT 1
        SQL;
        $stmt = $this->sql_db->prepare($query);
        $stmt->bind_param('s',$dn);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows != 1)
        {
            throw new Exception("LDAPUser Could not be found");
        }
        $res =  $this->result_to_LDAPUser($result);
        $this->fillLdapUserData($res);
        return $res;
    }

    /**
     * Inserts a DN into the local table
     * @param string $dn
     * @return LDAPUser
     * @throws Exception
     */
    public function InsertUserDN(string $dn): LDAPUser
    {
        $LDAPUser = new LDAPUser();
        $LDAPUser->setDN($dn);
        $query = <<<'SQL'
            CALL addldapuser(?,@userid)
        SQL;
        $stmt = $this->sql_db->prepare($query);
        $stmt->bind_param('s',$dn);
        $stmt->execute();
        //retrieving the user id of the newly created user
        $query2 = <<<'SQL'
            SELECT @userid AS usr_id
        SQL;
        $stmt2 = $this->sql_db->query($query2);
        $usr_id = $stmt2->fetch_assoc();
        if(is_null($usr_id['usr_id']))
        {
            throw new Exception("LDAPUser Could not be Created");
        }
        else
        {
            $LDAPUser->setUsrId($usr_id['usr_id']);
        }
        return $LDAPUser;
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
        //Check If User Exists
        try{
            $ldap_user = $this->findUserDN($ldap_username);
        }
        catch (Exception $e)
        {
            //If the User Does not exist, try to add him, if this fails, it might be disabled
            $this->InsertUserDN($ldap_username);
            //Once the User has been added, try to find him
            $ldap_user = $this->findUserDN($ldap_username);
        }
        return $ldap_user;
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

    private function fillLdapUserData(LDAPUser &$LDAPUser)
    {

        $this->fillLdapUserDataPosixAccount($LDAPUser);
        //todo add support for more schemes

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
}
