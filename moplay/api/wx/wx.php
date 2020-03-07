<?php
    require_once dirname(__FILE__).'/../util/db_mongodb.php';

     /* 
     * 微信公众号服务接口  
    */
    define('TOKEN','here is wechats token');

    $apiHandler = new wxApiHandler();
    $apiHandler -> RunApi(true);

    class wxApiHandler{

        public function RunApi($is_signed = false){
            if($is_signed){
                $this->switchService();
            }else{
                $this->doSign();
            }
        }

        function switchService(){
            $xml = $this->loadResXml();
            $msgtype = $xml->MsgType;
            if($msgtype == "text"){
                $to_user_name = $xml->ToUserName;
                $from_user_name = $xml->FromUserName;
                $content = $xml->Content;

                $moInfo = $this->queryMoToken($content);
                /**
                 * 1.神奇动物：格林德沃之罪(2019)
                 * 【CDC720】
                 * 2.所有邪佞之人
                 * 【CDC720】
                 * 3.无双【790EFD】
                 */
                $motoken_arr = array();
                foreach($moInfo as $mi=>$mi_i){
                    // $t = trim($mi_i["title"]);
                    // $code = trim($mi_i["mocode"]);
                    // $year = trim($mi_i["year"]);
                    $t = trim($mi_i->title);
                    $code = trim($mi_i->mocode);
                    $year = trim($mi_i->year);
                    if(strlen($t)>0 && strlen($code)>0)
                    {
                        array_push($motoken_arr,($mi+1)."-".$t."(".$year.")\n"."【http://www.dybox.top/h/".$code.".html】");
                    }
                }
                $msg_content = count($motoken_arr)>0 ? implode("\n",$motoken_arr)." \n（查询名称越准确，越容易获得准确推荐信息。）" : "暂未收录相关影片，我们已经知道你的需求，并将尽快为你收录，你可以先试试其他电影名称。";
                echo sprintf($this->_msg_template["text"], $from_user_name, $to_user_name, time(), $msg_content);
            } else {
                die('success');
            }
        }

        private $_msg_template = array(
            "text"=>"<xml><ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content></xml>",
            "news"=>"<xml><ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <ArticleCount>1</ArticleCount>
            <Articles>
            <item><Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url></item></Articles></xml>"
        );

        /*
         * 根据电影名称抓取相关的MoCode（即MoToken）
         */
        function queryMoToken($title){
            $title = trim($title);
            if(strlen($title)==0){
                die('success');
            }else{

              $coll = 'medias_cover';
              $mongo_db = new DB_MongoDB_Handler('mobox');
  
              $options = [
                  'projection' => ['title'=>1, 'mocode'=>1, 'year'=>1],
                  'skip' => 0, 
                  'limit' => 5
              ];
              $filter = [
                   '$or'=> [['title' => ['$regex'=>$title, '$options' => 'i']], 
                   ['title_en' => ['$regex'=>$title, '$options' => 'i']]]
              ];
              return $mongo_db->query($coll, $filter, $options);
            }
        }

        // 读来自微信的xml消息，并解析成对象
        function loadResXml(){
            // $post_data = $GLOBALS["HTTP_RAW_POST_DATA"]; 
            $post_data = file_get_contents('php://input');
            // $post_data="<xml>
            // <ToUserName><![CDATA[ymlshow]]></ToUserName>
            //  <FromUserName><![CDATA[一号铁粉]]></FromUserName>
            //  <CreateTime>1460537339</CreateTime>
            //  <MsgType><![CDATA[text]]></MsgType>
            //  <Content><![CDATA[邪佞]]></Content>
            //  <MsgId>6272960105994287618</MsgId>
            //  </xml>";
            if(empty($post_data)){
                die('no data');
            }else{
                return simplexml_load_string($post_data);
            }
        }

        function doSign(){
            if($this->checkSignation()){
                $echostr = $_GET['echostr'];
                if($echostr){
                    echo $echostr;
                }
            }
        }

        function checkSignation(){
            $signature = $_GET["signature"];
            $timestamp = $_GET["timestamp"];
            $nonce = $_GET["nonce"];    
                    
            $token = TOKEN;
            $ttn = array($token, $timestamp, $nonce);
            sort($ttn);
            $ttnstr = implode($ttn);
            $ttnstr = sha1($ttnstr);
            
            if( $ttnstr == $signature ){
                return true;
            }else{
                return false;
            }
        }
    }
    exit;
?>
