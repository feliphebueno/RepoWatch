<?php
namespace RepoWatch\Telegram;

class Telegram extends TelegramVO 
{

    public function __construct($token = NULL)
    {
        if(\is_null($token) === false){
            $this->setToken($token);
        }
        return $this;
    }

    public function sendMessage($msg, $chatId = NULL)
    {
        if(\is_null($chatId) === false){
            $this->setChatId($chatId);
        }

        return $this->setMsg($msg)
            ->setAction('sendMessage')
            ->execute(
            [
                'chat_id'   => $this->getChatId(),
                'text'      => $this->getMsg()
            ]
        );
    }

    public function readMessages()
    {
        return $this->setAction('getUpdates')
            ->execute([]);
    }
    
    private function execute($formParams)
    {
        $client     = new \GuzzleHttp\Client(['verify' => false]);
        $response   = $client->request('POST', $this->getUrlAPI() . $this->getToken() .'/'. $this->getAction(), [
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
    
    public function setChatId($chatId) 
    {
        parent::setChatId($chatId);
        return $this;
    }

    public function setMsg($msg) 
    {
        parent::setMsg($msg);
        return $this;
    }

    public function setAction($action)
    {
        parent::setAction($action);
        return $this;
    }
}