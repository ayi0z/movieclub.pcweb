<?php
//   header("charset=utf-8");

  class Response_Json{
    public static function json($code, $message="", $data=null){
        $res["code"] = $code;
        $res["msg"] = $message;
        $res["data"] = $data;
        
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }
}
?>