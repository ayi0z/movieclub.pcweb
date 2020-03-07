<?php
    require_once dirname(__FILE__).'/../util/db_mongodb.php';

     /* 
     * 微信公众号服务接口  
    */
    define('TOKEN','her is wechat token');

    $apiHandler = new wxApiHandler();
    $apiHandler -> RunApi(false);

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

                $motoken_arr = array();
                foreach($moInfo as $mi=>$mi_i){
                    $t = trim($mi_i->title);
                    $code = strtolower(trim($mi_i->mocode));
                    $year = trim($mi_i->year);
                    if(strlen($t)>0 && strlen($code)>0)
                    {
                        array_push($motoken_arr,($mi+1)."-".$t."(".$year.")\n"."=> http://m.bsswhg.com/p/".$code);
                    }
                }
                $msg_content = count($motoken_arr)>0 ? implode("\n",$motoken_arr)."\n-----------------\n=> 关键字越多，越容易获得准确推荐信息。\n=> 多个关键字用' '空格隔开，如“大爆炸 十二” " : "暂未查询到相关影片，\n请减少关键字再次尝试。\n未收录影片一般将在24小时内收录。";
                echo sprintf($this->_msg_template["text"], $from_user_name, $to_user_name, time(), $msg_content);

                // $this->logWxRequest($from_user_name, $msgtype, $content, $xml->CreateTime, count($motoken_arr) > 0 );
            } elseif ($msgtype == "event") {
                $to_user_name = $xml->ToUserName;
                $from_user_name = $xml->FromUserName;
                $content = $xml->Event;
                if($content == 'subscribe') {
                    echo sprintf($this->_msg_template["news"], $from_user_name, $to_user_name, time(),
                        '欢迎关注“优觅Lu电影”, 公众号使用指南',
                        '及时获取国内外最新的即将上映的电影及预告片、电影剪辑，以及电影资讯',
                        'https://mmbiz.qpic.cn/mmbiz_jpg/dKe1WTSiaJo1vKXzvtsxBtcUEgOk1cef3k8jpqGvEqZka5tdJmLpxhBEgg54xfVTA65KZBn9fKnOKF0urySMoSA/0?wx_fmt=jpeg',
                        'https://mp.weixin.qq.com/s?__biz=MzU3MTc0NDUyNg==&mid=2247483970&idx=1&sn=b0d628231ea70bab3d5543783197f2ea&chksm=fcdaca23cbad4335f526cb778eb4ed7c9f84bb3813b2686f2203e58b7a5ae14c28f20583469f&token=1329523789&lang=zh_CN#rd');
                } elseif($content == 'unsubscribe') {
                    echo sprintf($this->_msg_template["text"], $from_user_name, $to_user_name, time(), '再见，期待您再次关注！');
                }

                // $this->logWxRequest($from_user_name, $msgtype, $content, $xml->CreateTime);
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

              $mongo_db = new DB_MongoDB_Handler('mobox');
  
              $options = [
                  'projection' => ['title'=>1, 'mocode'=>1, 'year'=>1],
                  'sort' => ['year'=>-1, 'hot'=>-1, 'latime' => -1],
                  'skip' => 0, 
                  'limit' => 10
              ];
              $title = '.*'.str_replace(' ', '.*', $title).'.*';
              $title = new MongoDB\BSON\Regex($title); 
              $filter = [
                    '$and' => [
                        ['$or'=> [['isoff' => 0],['isoff' => '0']]],
                        ['$or'=> [['title' => ['$regex'=>$title, '$options' => 'i']], 
                                ['title_en' => ['$regex'=>$title, '$options' => 'i']],
                                ['alias' => ['$regex'=>$title, '$options' => 'i']]]
                        ]
                    ]
              ];
              return $mongo_db->query('medias_cover', $filter, $options);
            }
        }

        /*
         * 记录微信搜索信息，
         * 用于异常报警，信息补录等
         */
        function logWxRequest($from_user, $msgtype, $content, $ctime, $reply=null){
            if(empty($content)){ return; }
            return;
            $mongo_db = new DB_MongoDB_Handler('mobox');

            $data['fromuser'] = $from_user;
            $data['msgtype'] = $msgtype;
            $data['content'] = $content;
            $data['ctime'] = $ctime;
            $data['reply'] = $reply;

            $mongo_db->insert($data, "wx_req_log");
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
            //  <Content><![CDATA[大爆炸 十二]]></Content>
            //  <MsgId>6272960105994287618</MsgId>
            //  </xml>";
            // $post_data = "<xml>
            //     <ToUserName><![CDATA[toUser]]></ToUserName>
            //     <FromUserName><![CDATA[FromUser]]></FromUserName>
            //     <CreateTime>123456789</CreateTime>
            //     <MsgType><![CDATA[event]]></MsgType>
            //     <Event><![CDATA[unsubscribe]]></Event></xml>";
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
