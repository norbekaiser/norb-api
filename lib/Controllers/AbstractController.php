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
require_once __DIR__ . '/../Exceptions/HTTP405_MethodNotAllowed.php';
abstract class AbstractController //TODO consider, abstract authorized controlelr, der auch Authorization Header Parsen kann um davon ggf für me controlelr zu erben
{
    protected $requestMethod;

    public function __construct(string $requestMethod)
    {
        $this->requestMethod = $requestMethod;
    }

    protected function GetRequest()
    {
        return $this->MethodNotAvailableResponse();
    }

    protected  function PostRequest()
    {
        return $this->MethodNotAvailableResponse();
    }

    protected function PutRequest()
    {
        return $this->MethodNotAvailableResponse();
    }

    protected function DelRequest()
    {
        return $this->MethodNotAvailableResponse();
    }

    protected function OptionsRequest()
    {
        $resp['status_code_header'] = 'HTTP/1.1 200 OK';
        return $resp;
    }

    public function processRequest()
    {
        switch ($this->requestMethod)
        {
            case 'GET':
                $resp = $this->GetRequest();
                break;
            case 'POST':
                $resp = $this->PostRequest();
                break;
            case 'PUT':
                $resp = $this->PutRequest();
                break;
            case 'DELETE':
                $resp = $this->DelRequest();
                break;
            case 'OPTIONS':
                $resp = $this->OptionsRequest();
                break;
            default:
                $resp = $this->MethodNotAvailableResponse();
                break;
        }
        return $resp;
    }

    private function MethodNotAvailableResponse()
    {
        throw new HTTP405_MethodNotAllowed();
    }
}
