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

namespace norb_api\Controllers;

require_once __DIR__ . '/AbstractHeaderController.php';
require_once __DIR__ . '/../Exceptions/HTTP401_Unauthorized.php';
require_once __DIR__ . '/../Exceptions/HTTP422_UnprocessableEntity.php';
require_once __DIR__ . '/../Config/RegistrationConfig.php';
require_once __DIR__ . '/../Gateways/LocalUserGateway.php';
require_once __DIR__ . '/../Gateways/LdapUserGateway.php';
require_once __DIR__ . '/../Gateways/UserGateway.php';
require_once __DIR__ . '/../Gateways/SessionGateway.php';


use norb_api\Gateways\UserGateway;
use norb_api\Gateways\LocalUserGateway;
use norb_api\Gateways\SessionGateway;
use norb_api\Models\LocalUser;
use norb_api\Exceptions\HTTP400_BadRequest;
use norb_api\Exceptions\HTTP401_Unauthorized;
use norb_api\Config\RegistrationConfig;

class LocalMeController extends AbstractHeaderController
{
    private $UserGateway = null;
    private $sessionGateway = null;
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
        $this->sessionGateway = new SessionGateway();
        parent::__construct($requestMethod,$Authorization);
    }

    /**
     * Maps the Local User to the result
     * @param LocalUser $user
     * @return array
     */
    private function LocalUser_data_to_resp(LocalUser $user): array
    {
        return array(
//            'usr_id' => $user->getUsrId(),
            'username' => $user->getUsername(),
            'member_since' => $user->getMemberSince(),
        );
    }

    /**
     * Verifys that a session user was issued
     * @throws HTTP401_Unauthorized
     */
    private function require_valid_session()
    {
        if(is_null($this->User))
        {
            throw new HTTP401_Unauthorized();
        }
    }

    protected function GetRequest()
    {
        $this->require_valid_session();
        $resp['status_code_header'] = 'HTTP/1.1 200 OK';
        $LocalUserGateway = new LocalUserGateway();
        $localuser = $LocalUserGateway->findUserByUsrID($this->User->getUsrId());
        $resp['data'] = $this->LocalUser_data_to_resp($localuser);
        return $resp;
    }

    protected function PatchRequest()
    {
        $this->require_valid_session();
        $input = (array) json_decode(file_get_contents('php://input'), true);
        $this->validatePatchData($input);
        $resp['status_code_header'] = 'HTTP/1.1 200 OK';//ggf modified?
        $LocalUserGateway = new LocalUserGateway();
        $LocalUser = $LocalUserGateway->findUserByUsrID($this->User->getUsrId());
        if(isset($input['password']))
        {
            $LocalUserGateway->ChangePassword($LocalUser,$input['password']);
        }
        $resp['data'] = $this->LocalUser_data_to_resp($LocalUser);
        return $resp;
    }

    /**
     * Parses The Authorization String, and create a session on demand
     */
    protected function ParseAuthorization()
    {
        try
        {
            $session = $this->sessionGateway->find_session($this->Authorization);
            $session->getUsrId();
            $this->User = $this->UserGateway->findUser($session->getUsrId());
        }
        catch (\Exception $e)
        {
            $this->User = null;
        }
    }

    private function validatePatchData($input){
        $RegistrationConfig = new RegistrationConfig();
        /**
         * Verifys Data If A Password Change is Requried
         */
        if(isset($input['password']))
        {
            /** password must be at least 8 letters long */
            if(strlen($input['password']) < $RegistrationConfig->getMinimumLength())
            {

                throw new HTTP400_BadRequest("Password must Contain at least ".$RegistrationConfig->getMinimumLength()." Characters");
            }

            if($RegistrationConfig->getRequiresLetters() && !(preg_match('[\D]',$input['password'])))
            {
                throw new HTTP400_BadRequest("Password must Contain at least 1 Letter");
            }

            if($RegistrationConfig->getRequiresDigits() && !(preg_match('[\d]',$input['password'])))
            {
                throw new HTTP400_BadRequest("Password must Contain at least 1 Digit");
            }
        }
    }
}
