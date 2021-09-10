<?php
//    Copyright (c) 2021 Norbert Rühl
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

namespace norb_api\Controllers;

require_once __DIR__ . '/AbstractHeaderController.php';
require_once __DIR__ . '/../Gateways/UserGateway.php';
require_once __DIR__ . '/../Gateways/SessionGateway.php';
require_once __DIR__ . '/../Gateways/LocalUserGateway.php';
require_once __DIR__ . '/../Gateways/LocalLdapUserGateway.php';
require_once __DIR__ . '/../Gateways/LdapUserGateway.php';
require_once __DIR__ . '/../Exceptions/HTTP422_UnprocessableEntity.php';
require_once __DIR__ . '/../Exceptions/HTTP401_Unauthorized.php';
require_once __DIR__ . '/../Config/RegistrationConfig.php';

use norb_api\Gateways\UserGateway;
use norb_api\Gateways\SessionGateway;
use norb_api\Gateways\LocalUserGateway;
use norb_api\Gateways\LocalLdapUserGateway;
use norb_api\Exceptions\HTTP422_UnprocessableEntity;
use norb_api\Exceptions\HTTP401_Unauthorized;

class MeController extends AbstractHeaderController
{
    private $UserGateway = null;
    private $SessionGateway = null;
    private $User = null;

    /**
     * The Me Controller qruies a session and user gateway, other gateways are used on demand
     * MeController constructor.
     * @param string $requestMethod
     * @param string $Authorization
     */
    public function __construct(string $requestMethod,string $Authorization)
    {


        $this->UserGateway = new UserGateway();
        $this->SessionGateway = new SessionGateway();
        parent::__construct($requestMethod,$Authorization);
    }

    /**
     * Helper Function to determine if Logged in
     * @throws HTTP401_Unauthorized
     */
    private function require_valid_session()
    {
        if(is_null($this->User))
        {
            throw new HTTP401_Unauthorized();
        }
    }

    /**
     * When Get Request
     * @return mixed|void will return member_since data and where to find more about the user
     * @throws HTTP401_Unauthorized, requires a valid session
     * @throws HTTP422_UnprocessableEntity, will return 422 if for some reason the user does not exist anymore or something strange happens
     */
    protected function GetRequest()
    {
        $this->require_valid_session();
        $resp['status_code_header'] = 'HTTP/1.1 200 OK';
//        $resp['data']['usr_id'] = $this->User->getUsrId();//usr id does not need to be disclosed for requests, but might be nice to know
        $resp['data']['member_since'] = $this->User->getMemberSince();
        try {
            $LocalUserGateway = new LocalUserGateway();
            $localuser = $LocalUserGateway->findUserByUsrID($this->User->getUsrId());
            $resp['data']['type'] = 'local';
            return $resp;
        }
        catch (\Exception $e){}
        try {
            $LocalLdapUserGateway = new LocalLdapUserGateway();
            $ldapuser = $LocalLdapUserGateway->findUserID($this->User->getUsrId());
            $resp['data']['type'] = 'ldap';
            return $resp;
        }
        catch (\Exception $e){}
        throw new HTTP422_UnprocessableEntity();
    }


    /**
     * Parses The Authorization String, and create a session on demand
     */
    protected function ParseAuthorization()
    {
        try
        {
            $session = $this->SessionGateway->find_session($this->Authorization);
            $session->getUsrId();
            $this->User = $this->UserGateway->findUser($session->getUsrId());
        }
        catch (\Exception $e)
        {
            //to reduce cors problems acl is determined in the request
            $this->User = null;
        }
    }

}
