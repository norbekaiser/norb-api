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

namespace norb_api\Config;

require_once __DIR__ . '/Traits/Username.php';
require_once __DIR__ . '/Traits/UnixSocket.php';
require_once __DIR__ . '/Traits/Port.php';
require_once __DIR__ . '/Traits/Password.php';
require_once __DIR__ . '/Traits/Hostname.php';
require_once __DIR__ . '/Traits/DatabaseName.php';
require_once __DIR__ . '/Config.php';

class SQLConfig extends Config
{
    use Hostname, Port, DatabaseName, UnixSocket, Username, Password ;

    public function __construct()
    {
        $this->Hostname ='localhost';
        $this->Port = 3306;
        $this->DatabaseName = '';
        $this->UnixSocket = '/var/run/mysqld/mysqld.sock';
        $this->Username = '';
        $this->Password = '';
        parent::__construct(__DIR__.'/../../config/database.ini');
    }

    public function parse_file($ini_data)
    {
        if(is_string($ini_data['Hostname']))
        {
            $this->Hostname = (string)$ini_data['Hostname'];
        }
        if(is_string($ini_data['Port']))
        {
            $this->Port = (int)$ini_data['Hostname'];
        }
        if(is_string($ini_data['Name']))
        {
            $this->DatabaseName = (string)$ini_data['Name'];
        }
        if(is_string($ini_data['Socket']))
        {
            $this->UnixSocket = (string)$ini_data['Socket'];
        }
        if(is_string($ini_data['Username']))
        {
            $this->Username = (string)$ini_data['Username'];
        }
        if(is_string($ini_data['Password']))
        {
            $this->Password = (string)$ini_data['Password'];
        }
    }
}
