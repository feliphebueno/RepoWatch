<?php
namespace RepoWatch\WhatsApp;

class Whatsapp extends WhatsappVO 
{    
    public function __construct($waUid = null, $waPwd = null)
    {
        if(\is_null($waUid) === false){
            $this->setWaUid($waUid);
        }
        if(\is_null($waPwd) === false){
            $this->setWaPwd($waPwd);
        }
        
        return $this;
    }

    public function sendMessage($waMsg, $waRecp = NULL)
    {
        if(\is_null($waRecp) === false){
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
        return $this->executeAPI($formParams);
        $client     = new \GuzzleHttp\Client(['verify' => false]);
        $response   = $client->request('POST', $this->getUrlAPI(), [
            'form_params' => $formParams
        ]);

        return \json_decode($response->getBody()->getContents(), true);
    }
    private function executeAPI($formParams)
    {
        $wa = new \WhatsProt($formParams['wa_uid'], 'WhatsApp', false);
        $wa->eventManager()->bind('onGetSyncResult', 'onSyncResult');

        $wa->connect();
        $wa->loginWithPassword($formParams['wa_pwd']);

        $send = $wa->sendMessage($formParams['wa_recp'] , $formParams['wa_msg']);

        $wa->pollMessage();
        \sleep(2);
        $wa->pollMessage();
        
        return $send;
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