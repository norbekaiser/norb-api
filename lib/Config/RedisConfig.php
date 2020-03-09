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
require_once __DIR__ . '/Config.php';

class RedisConfig extends Config
{
    private $hostname;
    private $port;
    private $password;
    private $database_id;

    public function __construct()
    {
        $this->hostname = 'localhost';
        $this->port = 6379;
        $this->password ='';
        $this->database_id = 0;
        parent::__construct(__DIR__ . '/../../config/redis.ini');
    }

    protected function parse_file($ini_data)
    {
        if(is_string($ini_data['Hostname']))
        {
            $this->hostname = (string) $ini_data['Hostname'];
        }

        if(is_numeric($ini_data['Port']))
        {
            $this->port = (int) $ini_data['Port'];
        }

        if(is_string($ini_data['Password']))
        {
            $this->password = (string) $ini_data['Password'];
        }

        if(is_numeric($ini_data['DatabaseID']))
        {
            $this->database_id = (int) $ini_data['DatabaseID'];
        }
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getDatabaseId(): int
    {
        return $this->database_id;
    }
}
