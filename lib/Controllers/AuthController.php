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
require_once __DIR__ . '/AbstractController.php';
require_once __DIR__ .'/../Exceptions/HTTP422_UnprocessableEntity.php';
require_once __DIR__ .'/../Exceptions/HTTP400_BadRequest.php';
require_once __DIR__ . '/../Gateways/LocalUserGateway.php';
require_once __DIR__ .'/../Gateways/LdapUserGateway.php';
require_once __DIR__ . '/../Gateways/SessionGateway.php';
require_once __DIR__ . '/../Config/RecaptchaConfig.php';
require_once __DIR__ . '/../recaptcha/recaptcha_validator.php';

class AuthController extends AbstractController
{
    private $LocalUserGateway = null;
    private $LdapUserGateway = null;
    private $sessionGateway = null;


    public function __construct(string $requestMethod)
    {
        parent::__construct($requestMethod);
        $this->LocalUserGateway = new LocalUserGateway();
        $this->LdapUserGateway = new LdapUserGateway();
        $this->sessionGateway = new SessionGateway();
    }

    private function Authenticate(string $username,string $password): User
    {
        $user= null;
        //Check Against LDAP
        try
        {
            $user = $this->LdapUserGateway->AuthenticateUser($username,$password);
        }
        catch (Exception $e)
        {

        }
        //Check Against Local Database
        try
        {
            $user = $this->LocalUserGateway->AuthLocalUser($username,$password);
        }
        catch (Exception $e)
        {

        }

        if(is_null($user))
        {
            throw new Exception("Authentication Failed");
        }
        return $user;
    }

    protected function PostRequest()
    {
        $input = (array) json_decode(file_get_contents('php://input'), true);
        try
        {
            $this->validateCandidate($input);
            $user = $this->Authenticate($input['username'],$input['password']);
        }
        catch (HTTP_Exception $e)
        {
            throw $e;
        }
        catch (Exception $e)
        {
            throw new HTTP422_UnprocessableEntity($e->getMessage());
        }

        //use the 'usr_id' to create a session, return the session
        $new_session = $this->sessionGateway->create_session($user);

        $resp['status_code_header'] = 'HTTP/1.1 200 OK';
        $resp['data'] = array(
            'sessionid' => $new_session->getSessionId()
        );
        return $resp;
    }


    private function validateCandidate($input)
    {
        $CaptchaConfig = new RecaptchaConfig();
        /** Candidate must provide a username/email */
        if(!isset($input['username']))
        {
            throw new HTTP400_BadRequest("No Username Supplied");
        }
        /** Candidate must provide a password */
        if(!isset($input['password']))
        {
            throw new HTTP400_BadRequest("No Password Supplied");
        }

        if($CaptchaConfig->getEnabled() && !isset($input['g_recaptcha_response']))
        {
            throw new HTTP400_BadRequest("No Google Recaptcha Response Supplied");
        }

        if($CaptchaConfig->getEnabled() && $CaptchaConfig->getVersion()==2)
        {
            $captcha = new recaptcha_validator($CaptchaConfig);
            if(! ($captcha->verify($input['g_recaptcha_response'])))
            {
                throw new HTTP422_UnprocessableEntity(("Recaptcha Failed"));
            }
        }
    }

}
