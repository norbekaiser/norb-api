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

namespace norb_api\Config;

require_once __DIR__ . '/Traits/SecretKey.php';
require_once __DIR__ . '/Traits/SiteKey.php';
require_once __DIR__ . '/Config.php';

class FriendlycaptchaConfig extends Config
{
    use SiteKey, SecretKey;

    public function __construct()
    {
        $this->SecretKey ="";
        $this->SiteKey ="";
        parent::__construct(__DIR__ . '/../../config/captcha.ini',true);
    }

    protected function parse_file($ini_data)
    {
        if(isset($ini_data['friendlycaptcha']['SecretKey']) and is_string($ini_data['friendlycaptcha']['SecretKey']))
        {
            $this->SecretKey = (string) $ini_data['friendlycaptcha']['SecretKey'];
        }

        if(isset($ini_data['friendlycaptcha']['SiteKey']) and is_string($ini_data['friendlycaptcha']['SiteKey']))
        {
            $this->SiteKey = (string) $ini_data['friendlycaptcha']['SiteKey'];
        }
    }
}
