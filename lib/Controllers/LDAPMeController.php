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
require_once __DIR__ . '/AbstractHeaderController.php';
require_once __DIR__ . '/../Exceptions/HTTP400_BadRequest.php';
require_once __DIR__ . '/../Exceptions/HTTP401_Unauthorized.php';
require_once __DIR__ . '/../Exceptions/HTTP422_UnprocessableEntity.php';
require_once __DIR__ . '/../Config/RegistrationConfig.php';
require_once __DIR__ . '/../Gateways/LocalLdapUserGateway.php';
require_once __DIR__ . '/../Gateways/LdapUserGateway.php';
require_once __DIR__ . '/../Gateways/SessionGateway.php';

class LDAPMeController extends AbstractHeaderController
{
    private $localLdapUserGateway = null;
    private $sessionGateway = null;
    private $ldap_user = null;

    /**
     * The Me Controller qruies a session and user gateway, other gateways are used on demand
     * MeController constructor.
     * @param string $requestMethod
     * @param string $Authorization
     */
    public function __construct(string $requestMethod,string $Authorization)
    {
        $this->localLdapUserGateway = new LocalLdapUserGateway();
        $this->sessionGateway = new SessionGateway();
        parent::__construct($requestMethod,$Authorization);
    }


    /**
     * Maps the LDAP user to the result
     * @param LDAPUser $user
     * @return array
     */
    private function LdapUser_data_to_resp(LDAPUser $user): array
    {
        return array(
//            'usr_id' => $user->getUsrId(),
            'dn' => $user->getDn(),
            'member_since' => $user->getMemberSince(),
            'cn' => $user->getCn(),
            "uid" => $user->getUid(),
            "uidNumber" => $user->getUidNUmber(),
            "gidNumber" => $user->getGidNUmber(),
            "homeDirectory" => $user->getHomeDirectory(),
            "loginShell" => $user->getLoginShell(),
        );
    }

    /**
     * Verifys that a session user was issued
     * @throws HTTP401_Unauthorized
     */
    private function require_valid_session()
    {
        if(is_null($this->ldap_user))
        {
            throw new HTTP401_Unauthorized();
        }
    }

    /**
     * Returns the User Data
     * @return mixed|void boils down to at least usr_id  and username/dn( also username)
     * @throws HTTP401_Unauthorized , requires a valid session
     * @throws HTTP422_UnprocessableEntity, if the user can't be found either in userlocal or userldap , this will hapoen
     */
    protected function GetRequest()
    {
        $this->require_valid_session();
        $LdapUserGateway = new LdapUserGateway();
        $ldapuser = $LdapUserGateway->fillUser($this->ldap_user);
        $resp['status_code_header'] = 'HTTP/1.1 200 OK';
        $resp['data'] = $this->LdapUser_data_to_resp($ldapuser);
        return $resp;
    }

    /**
     * Used to Modify User Data
     * @return mixed|void
     * @throws HTTP422_UnprocessableEntity if the user is not n ldap user
     * @throws HTTP400_BadRequest if the format is not valid, e.g. not enough digits or no valid email
     * @throws HTTP401_Unauthorized if the user is ńot authorized to do this request
     */
    protected function PatchRequest()
    {
        $this->require_valid_session();
        $input = (array) json_decode(file_get_contents('php://input'), true);
        $this->validatePatchData($input);
        $LdapUserGateway = new LdapUserGateway();

//        $ldapuser = $LdapUserGateway->fillUser($this->ldap_user);
        if(isset($input['password']))
        {
            $LdapUserGateway->ChangePassword($this->ldap_user,$input['password']);
            $resp['data']['password'] = "modified";
        }
        if(isset($input['email']))
        {
            $LdapUserGateway->ChangeEmail($this->ldap_user,$input['password']);
            $resp['data']['email'] = "modified";
        }
        $resp['status_code_header'] = 'HTTP/1.1 200 OK';
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
            $this->ldap_user = $this->localLdapUserGateway->findUserID($session->getUsrId());
        }
        catch (Exception $e)
        {
            $this->ldap_user = null;
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
            if(strlen($input['password']) < $RegistrationConfig->getMinLength())
            {

                throw new HTTP400_BadRequest("Password must Contain at least ".$RegistrationConfig->getMinLength()." Characters");
            }

            if($RegistrationConfig->getLetters() && !(preg_match('[\D]',$input['password'])))
            {
                throw new HTTP400_BadRequest("Password must Contain at least 1 Letter");
            }

            if($RegistrationConfig->getDigits() && !(preg_match('[\d]',$input['password'])))
            {
                throw new HTTP400_BadRequest("Password must Contain at least 1 Digit");
            }
        }

    }
}
