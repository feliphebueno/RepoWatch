<?php
namespace RepoWatch\WhatsApp;

class Whatsapp extends WhatsappVO 
{    
    private $confs;

    public function __construct($waUid = null, $waPwd = null)
    {
        $this->confs        = \App\Config::$SIS_CFG;

        if(\is_null($waUid) === false){
            $this->setWaUid($waUid);
        } else {
            $this->setWaUid($this->confs['apiKeys']['whatsapp']['user']);
        }

        if(\is_null($waPwd) === false){
            $this->setWaPwd($waPwd);
        } else {
            $this->setWaPwd($this->confs['apiKeys']['whatsapp']['pass']);
        }

        return $this;
    }

    public function sendMessage($waMsg, $waRecp = NULL)
    {
        if(\is_null($waRecp) === false){
            $this->setWaRecp($waRecp);
        }
        
        return $this->setWaMsg($waMsg)
            ->execute(
            [
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
        $wa = new \WhatsProt($formParams['wa_uid'], 'WhatsApp', false);
        //$wa->eventManager()->bind('onGetSyncResult', 'onSyncResult');
        
        $wa->connect();
        $wa->loginWithPassword($formParams['wa_pwd']);

        //send dataset to server
        //$wa->sendSync(['+556593152857']);
        
        $send = $wa->sendMessage($formParams['wa_recp'] , $formParams['wa_msg']);

        $wa->pollMessage();
        \sleep(2);
        $wa->pollMessage();
        
        $wa->sendOfflineStatus();
        
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