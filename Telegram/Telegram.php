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
            ->executeMultiPart(
            [
                'chat_id'                       => $this->getChatId(),
                'text'                          => $this->getMsg(),
                'parse_mode'                    => 'HTML',
                'disable_web_page_preview'      => true
            ]
        );
    }

    public function sendSticker($sticker, $chatId = NULL)
    {
        if(\is_null($chatId) === false){
            $this->setChatId($chatId);
        }

        return $this->setAction('sendSticker')
            ->execute(
            [
                'chat_id'                       => $this->getChatId(),
                'sticker'                       => $sticker,
                'parse_mode'                    => 'HTML',
                'disable_web_page_preview'      => 'true'
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
        $multipartData = [];
        foreach ($formParams as $key=>$param){
            $multipartData[] = [
                'name'      => $key,
                'contents'  => $param,
            ];
        }

        $client     = new \GuzzleHttp\Client(['verify' => false]);
        $response   = $client->request('POST', $this->getUrlAPI() . $this->getToken() .'/'. $this->getAction(), [
            'multipart' => $multipartData
        ]);

        return \json_decode($response->getBody()->getContents(), true);
    }
    
    private function executeMultiPart($formParams)
    {
        $headers = array("Content-Type:multipart/form-data"); // cURL headers for file uploading
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $this->getUrlAPI() . $this->getToken() .'/'. $this->getAction(),
            CURLOPT_HEADER => true,
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $formParams,
            CURLOPT_INFILESIZE => \filesize('/home/siprevcloudcom/public_html/RepoWatch/Telegram/sticker.webp'),
            CURLOPT_RETURNTRANSFER => true
        ]; // cURL options
        
        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);
        
        curl_close($ch);

        return $result;
    }
    
    
    /**
     * 
     * SOBRECARGA DE MÃ‰TODOS
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