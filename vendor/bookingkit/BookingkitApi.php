<?php

namespace bookingkit;


class BookingkitApi{

    private $token;
    private static $prefixContent = '<?php exit(); ?>';
    public $config = [
        'cachePath'=>__DIR__.DIRECTORY_SEPARATOR.'tmp',
        'cacheFile'=>'BKTokenStorage.tmp.php',
        //'server'=>"https://api.bookingkit.de",
        'server'=>"http://lapi.bookingkit.de",
        'version'=>"v3",
        'scope'=>'calendar_read orders_read_owned orders_write_owned'
        // 'client_id'=>"demo",
        // 'client_secret'=>"demo",
    ];

    public $isNew = false;


    public function __construct($apiCreds)
    {
        $this->token = $this->getFormCache();
        if(empty($this->token)){
            //get Token:
            $curl = curl_init($this->config['server']."/oauth/token");
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, [
                'client_id' => $apiCreds['client_id'],
                'client_secret' => $apiCreds['client_secret'],
                'grant_type' => 'client_credentials',
                //'scope'=>$this->config['scope'],
            ]);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $auth = curl_exec($curl);
            //ShouldDo check curl errors
            $secret = json_decode($auth);
            if (!empty($secret->error)) {
                throw new \Exception("Bookingkit Login Error: ".$secret->error."\n\n".$auth."\n\n"."HTTP CODE:".curl_getinfo($curl, CURLINFO_HTTP_CODE));
            }
            //expire time
            $secret->expires = time()+$secret->expires_in;
            $this->writeToCache($secret);
        }
    }

    private function getCacheFilePath(){
        return $this->config['cachePath'].DIRECTORY_SEPARATOR.$this->config['cacheFile'];
    }

    private function getFormCache(){
        if(!is_file($this->getCacheFilePath())){
            return null;
        }
        $data = file_get_contents($this->getCacheFilePath());
        if ($data === false) {
            return null;
        }
        $serialized = str_replace(self::$prefixContent, '', $data);
        $token = unserialize($serialized);
        if($token->expires < time()){
            $token = null;
        }
        return $token;
    }

    private function writeToCache($token){
        if (!is_writable($this->config['cachePath'])) {
            throw new \Exception('Cannot create or write to file to' . $this->config['cachePath']);
        }

        $serialized = serialize($token);
        $result = file_put_contents($this->getCacheFilePath(), self::$prefixContent . $serialized, LOCK_EX);
        if ($result === false) {
            throw new \Exception('Cannot put token to file');
        }
        $this->token = $token;
    }


    public function getRest($url){
        $curl = curl_init($url);
        $headers = ['Authorization: Bearer ' . $this->token->access_token];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
        $result = curl_exec($curl);
        $info = curl_getinfo($curl);

        return $result;
    }

    public function postRest($url,$post,$request = "PATCH"){
        $curl = curl_init($url);
        $headers = ['Authorization: Bearer ' . $this->token->access_token, "Content-type: application/json"];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
        if($request !== "POST"){
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request);
        }
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($post));
        $result = curl_exec($curl);
        $json = json_decode($result);
        if(!empty($json) && empty($json->success)){
            throw new \Exception("Bookingkit Error response: ".$result."\n\n"."URL:".$url."\n\n".$result."\n\n"."HTTP CODE:".curl_getinfo($curl, CURLINFO_HTTP_CODE));
        }

        return $result;
    }

    public function getUrl($model,$params = []){
        $url = $this->config['server']."/". $this->config['version']."/".$model."?".http_build_query($params);
        return $url;
    }

    public function getOrders($params){
        $url = $this->getUrl("orders",$params);
        $orders=$this->getRest($url);
        return json_decode($orders);
    }

    public function getDates($params, $asJson=false){
        $url = $this->getUrl("dates",$params);
        $dates=$this->getRest($url);
        if($asJson){
            return $dates;
        }else{
            return json_decode($dates);
        }
    }

    public function getEvents($params, $asJson=false){
        $url = $this->getUrl("events",$params);
        $dates=$this->getRest($url);
        if($asJson){
            return $dates;
        }else{
            return json_decode($dates);
        }
    }

    public function getEventDates($params, $asJson=false){
        $url = $this->getUrl("eventDates",$params);
        $dates=$this->getRest($url);
        if($asJson){
            return $dates;
        }else{
            return json_decode($dates);
        }
    }

}