<?php 
namespace Whatsapp;

class WhatsappVO
{
    /** @var string API URL wheter http://wapi.phphive.info/api/message/send.php to send or http://wapi.phphive.info/api/message/receive.php to read messages*/
    private $urlAPI   = NULL;
    
    /** @var string PHPHive WhatsAPI Token, Get it from http://wapi.phphive.info*/
    private $token      = NULL;
    
    /** @var string WhatsApp Username */
    private $waUid     = NULL;
    
    /** @var string WhatsApp Password, refer to https://www.phphive.info/255/get-whatsapp-password/ */
    private $waPwd     = NULL;
    
    /** @var string Recipient */
    private $waRecp    = NULL;

    /** @var string Message You want to Send */
    private $waMsg     = NULL;
    
    public function getUrlAPI() 
    {
        return $this->urlAPI;
    }
        
    public function getToken() 
    {
        return $this->token;
    }

    public function getWaUid() 
    {
        return $this->waUid;
    }

    public function getWaPwd() 
    {
        return $this->waPwd;
    }

    public function getWaRecp() 
    {
        return $this->waRecp;
    }

    public function getWaMsg() 
    {
        return $this->waMsg;
    }
    
    protected function setUrlAPI($urlAPI)
    {
        $this->urlAPI = $urlAPI;
    }
    
    public function setToken($token) 
    {
        $this->token = $token;
    }

    public function setWaUid($waUid) 
    {
        $this->waUid = $waUid;
    }

    public function setWaPwd($waPwd) 
    {
        $this->waPwd = $waPwd;
    }

    public function setWaRecp($waRecp) 
    {
        $this->waRecp = $waRecp;
    }

    public function setWaMsg($waMsg) 
    {
        $this->waMsg = \urlencode($waMsg);
    }
}