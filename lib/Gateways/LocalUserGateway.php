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
require_once __DIR__ . '/../Models/LocalUser.php';

/**
 * Class LocalUserGateway
 * This Provides a Gateway to the Database Items of a Local user, consisting of a username
 * This Gateway also provides the Authentication Feature of a Local User, as it is against the Database
 */
class LocalUserGateway
{
    use SQLGateway;


    public function __construct()
    {
        $this->init_sql();

    }

    public function findUser(int $usr_id): LocalUser
    {
        $query = <<<'SQL'
            SELECT users_id.id as usr_id, users_id.member_since as member_since, users_local.username as username 
            FROM users_local 
            INNER JOIN users_id ON users_local.usr_id = users_id.id 
            WHERE usr_id=? LIMIT 1
        SQL;
        $stmt = $this->sql_db->prepare($query);
        $stmt->bind_param('i',$usr_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows != 1)
        {
            throw new Exception("LocalUser Could not be found");
        }
        $userData = $result->fetch_assoc();
        $LocalUser = new LocalUser();
        $LocalUser->setUsrId($userData['usr_id']);
        $LocalUser->setUsername($userData['username']);
        $LocalUser->setMemberSince($userData['member_since']);
        return $LocalUser;
    }

    public function insertLocalUser(string $username,string $password): LocalUser
    {
        $LocalUser = new LocalUser();
        $LocalUser->setUsername($username);
        $query = <<<'SQL'
            CALL addlocaluser(?,?,@userid)
        SQL;
        $stmt = $this->sql_db->prepare($query);
        $stmt->bind_param('ss',$LocalUser->getUsername(),$password);
        $stmt->execute();
        //retrieving the user id of the newly created user
        $query2 = <<<'SQL'
            SELECT @userid AS usr_id
        SQL;
        $stmt2 = $this->sql_db->query($query2);
        $usr_id = $stmt2->fetch_assoc();
        if(is_null($usr_id['usr_id']))
        {
            throw new Exception("LocalUser Could not be Created");
        }
        else
        {
            $LocalUser->setUsrId($usr_id['usr_id']);
        }
        return $LocalUser;
    }

    public function AuthLocalUser(string $username, string $password): LocalUser
    {
        $query= <<<'SQL'
            CALL authlocaluser(?,?)
        SQL;
        $stmt = $this->sql_db->prepare($query);
        $stmt->bind_param('ss',$username,$password);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows != 1){
            throw new Exception("Invalid Credentials");
        }
        $userData = $result->fetch_assoc();
        $LocalUser = new LocalUser();
        $LocalUser->setUsrId($userData['usr_id']);
        $LocalUser->setUsername($userData['username']);
        return $LocalUser;
    }


}

