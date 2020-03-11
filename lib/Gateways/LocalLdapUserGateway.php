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
 * Class LocalLdapUserGateway
 * This Gateway Provides a Connection to the locally stored data of the ldap user, e.g. matching of id and ldap_dn
 */
class LocalLdapUserGateway
{
    use SQLGateway;

    /**
     * The Gateway requires a sql and an ldap connection
     * LdapUserGateway constructor.
     */
    public function __construct()
    {
        $this->init_sql();
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
        return $res;
    }

    /**
     * Finds a Ldap User in the Local Table, he will be there if he has once used the service
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
}
