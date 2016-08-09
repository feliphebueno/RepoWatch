<?php
namespace Whatsapp;
require_once './WhatsappVO.php';

class Whatsapp extends WhatsappVO 
{

    public function __construct()
    {
        return $this;
    }

    public function sendMessage($waMsg, $waRecp = NULL)
    {
        if(\is_null($recp) === false){
            $this->setWaRecp($waRecp);
        }
        
        return $this->setUrlAPI('http://wapi.phphive.info/api/message/send.php')
            ->setWaMsg($waMsg)
            ->execute(
            [
                'token'     => $this->getToken(),
                'wa_uid'    => $this->getWaUid(),
                'wa_pwd'    => $this->getWaPwd(),
                'wa_recp'   => $this->getWaRecp(),
                'wa_msg'    => $this->getWaMsg()
            ]
        );
    }

    public function readMessages()
    {
        return $this->setUrlAPI('http://wapi.phphive.info/api/message/receive.php')
            ->execute(
            [
                'token'     => $this->getToken(),
                'wa_uid'    => $this->getWaUid(),
                'wa_pwd'    => $this->getWaPwd(),
                'wa_recp'   => $this->getWaRecp()
            ]
        );
    }
    
    private function execute($formParams)
    {
        $client     = new \GuzzleHttp\Client(['verify' => false]);
        $response   = $client->request('POST', $this->getUrlAPI(), [
            'form_params' => $formParams
        ]);

        return \json_decode($response->getBody()->getContents(), true);
    }
    
    
    /**
     * 
     * SOBRECARGA DE MÉTODOS
     * 
     */
    public function setUrlAPI($urlAPI)
    {
        parent::setUrlAPI($urlAPI);
        return $this;
    }
    
    public function setToken($token) 
    {
        parent::setToken($token);
        return $this;
    }

    public function setWaUid($waUid) 
    {
        parent::setWaUid($waUid);
        return $this;
    }

    public function setWaPwd($waPwd) 
    {
        parent::setWaPwd($waPwd);
        return $this;
    }

    public function setWaRecp($waRecp) 
    {
        parent::setWaRecp($waRecp);
        return $this;
    }

    public function setWaMsg($waMsg) 
    {
        parent::setWaMsg($waMsg);
        return $this;
    }
}