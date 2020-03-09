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
require_once __DIR__ .'/../Exceptions/HTTP400_BadRequest.php';
require_once __DIR__ .'/../Exceptions/HTTP422_UnprocessableEntity.php';
require_once __DIR__ . '/../Gateways/LocalUserGateway.php';
require_once __DIR__ . '/../Config/RecaptchaConfig.php';
require_once __DIR__ . '/../Config/RegistrationConfig.php';
require_once __DIR__ . '/../recaptcha/recaptcha_validator.php';

class RegisterController extends AbstractController
{
    private $userGateway = null;

    public function __construct(string $requestMethod)
    {
        parent::__construct($requestMethod);
        $this->userGateway = new LocalUserGateway();
    }

    protected function PostRequest()
    {
        $input = (array) json_decode(file_get_contents('php://input'), true);
        try
        {
            $this->validateCandidate($input);
            $new_user = $this->userGateway->insertLocalUser($input['username'],$input['password']);
            $resp['status_code_header'] = 'HTTP/1.1 201 Created';
            $resp['data'] = array(
                'username' => $new_user->getUsername(),
            );
        }
        catch (HTTP_Exception $e)
        {
            throw $e;
        }
        catch (Exception $e)
        {
            throw new HTTP422_UnprocessableEntity($e->getMessage());
        }

        return $resp;
    }

    private function validateCandidate($input){
        $CaptchaConfig = new RecaptchaConfig();
        $RegistrationConfig = new RegistrationConfig();
        if(!($RegistrationConfig->getEnabled())){
            throw new HTTP400_BadRequest("Registration is Disabled");
        }
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
        /** Candidate must provide a google recaptcha response */
        if($CaptchaConfig->getEnabled() && !isset($input['g_recaptcha_response']))
        {
            throw new HTTP400_BadRequest("No Google Recaptcha Response Supplied");
        }

        /** username must be email */
        if(!filter_var($input['username'],FILTER_VALIDATE_EMAIL))
        {
            throw new HTTP400_BadRequest("Username is not a Valid Email Address");
        }

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
        /** Verifying Google Recaptcha */
        if($CaptchaConfig->getEnabled() && $CaptchaConfig->getVersion()==2)
        {
            $captcha = new recaptcha_validator($CaptchaConfig);
            if(! ($captcha->verify($input['g_recaptcha_response'])))
            {
                throw new HTTP422_UnprocessableEntity("Recaptcha Failed");
            }
        }
    }
}
