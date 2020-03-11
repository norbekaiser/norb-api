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
trait PosixAccount
{
    //Required
    private $cn;
    private $uid;
    private $uidNUmber;
    private $gidNUmber;
    private $homeDirectory;
    //Optional
    private $loginShell;

    /**
     * @return mixed
     */
    public function getCn()
    {
        return $this->cn;
    }

    /**
     * @param mixed $cn
     */
    public function setCn($cn): void
    {
        $this->cn = $cn;
    }

    /**
     * @return mixed
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param mixed $uid
     */
    public function setUid($uid): void
    {
        $this->uid = $uid;
    }

    /**
     * @return mixed
     */
    public function getUidNUmber()
    {
        return $this->uidNUmber;
    }

    /**
     * @param mixed $uidNUmber
     */
    public function setUidNUmber($uidNUmber): void
    {
        $this->uidNUmber = $uidNUmber;
    }

    /**
     * @return mixed
     */
    public function getGidNUmber()
    {
        return $this->gidNUmber;
    }

    /**
     * @param mixed $gidNUmber
     */
    public function setGidNUmber($gidNUmber): void
    {
        $this->gidNUmber = $gidNUmber;
    }

    /**
     * @return mixed
     */
    public function getHomeDirectory()
    {
        return $this->homeDirectory;
    }

    /**
     * @param mixed $homeDirectory
     */
    public function setHomeDirectory($homeDirectory): void
    {
        $this->homeDirectory = $homeDirectory;
    }

    /**
     * @return mixed
     */
    public function getLoginShell()
    {
        return $this->loginShell;
    }

    /**
     * @param mixed $loginShell
     */
    public function setLoginShell($loginShell): void
    {
        $this->loginShell = $loginShell;
    }


}
