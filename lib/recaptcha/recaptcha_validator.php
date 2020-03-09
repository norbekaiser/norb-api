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
require_once __DIR__ . '/../Config/RecaptchaConfig.php';

class recaptcha_validator
{
    private $url;
    private $secretkey;

    public function __construct(RecaptchaConfig $config)
    {
        $this->url = 'https://www.google.com/recaptcha/api/siteverify';
        $this->secretkey = $config->getSecretkey();
    }

    /**
     * Verifies a Google Recaptcha REsponse
     */
    public function verify(string $g_recaptcha_response) : bool
    {
        $data = array(
            'secret' => urlencode($this->secretkey),
            'response' => urlencode($g_recaptcha_response)
        );

        $curl = curl_init($this->url);
        curl_setopt($curl,CURLOPT_URL,$this->url);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,2);
        curl_setopt($curl,CURLOPT_POST,true);
        curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($data));
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION,false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        if(curl_getinfo($curl,CURLINFO_HTTP_CODE)!=200)
        {
            // technisch gesehen keine ahnung was passiert is, allerdings dann is es halt automatisch falsch
            return false;
        }
        $responseData = json_decode($response,true);
        if($responseData["success"])
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
