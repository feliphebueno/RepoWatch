<?php 
namespace RepoWatch\Telegram;

class TelegramVO
{
    /** @var string API URL wheter http://wapi.phphive.info/api/message/send.php to send or http://wapi.phphive.info/api/message/receive.php to read messages*/
    private $urlAPI   = 'https://api.telegram.org/';
    
    /** @var string PHPHive WhatsAPI Token, Get it from http://wapi.phphive.info*/
    private $token      = NULL;
    
    private $chatId     = NULL;
    
    private $msg        = NULL;
    
    private $action     = NULL;
    
    public function getUrlAPI() 
    {
        return $this->urlAPI;
    }
        
    public function getToken() 
    {
        return $this->token;
    }

    public function getChatId() 
    {
        return $this->chatId;
    }

    public function getMsg() 
    {
        return $this->msg;
    }

    public function getAction() 
    {
        return $this->action;
    }

    protected function setUrlAPI($urlAPI)
    {
        $this->urlAPI = $urlAPI;
    }
    
    public function setToken($token) 
    {
        $this->token = $token;
    }
    
    public function setChatId($chatId) 
    {
        $this->chatId = $chatId;
    }

    public function setMsg($msg) 
    {
        $this->msg = \utf8_encode($msg);;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }
}