<?php
    header("content-type:application/json;charset=utf-8");

    require_once dirname(__FILE__).'/../util/response_json.php';
    require_once dirname(__FILE__).'/../util/db_mongodb.php';

    $Request_Method = strtoupper($_SERVER['REQUEST_METHOD']);
    if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
        if (isset($_POST["mo"])) {  // 查询影片信息
            $query_par_motoken = $_POST["mo"];
            if(strlen($query_par_motoken) == 6
            && ctype_alnum($query_par_motoken)){
                $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
                $pagesize = isset($_POST['pagesize']) ? intval($_POST['pagesize']) : 1000;
                Response_Json::json(1, "" , get_medias_mo_code($query_par_motoken, $offset, $pagesize));
            }else{
                Response_Json::json(0,"invalid parameters", null);
            }
        } else if (isset($_POST["mid"])){   // 查询分集信息
            $query_par_mid = $_POST["mid"];
            if(!empty($query_par_mid)){
                Response_Json::json(1, "" , get_medias_Eps_mid($query_par_mid));
            }else{
                Response_Json::json(0,"invalid parameters", null);
            }
        }
    } else {
        Response_Json::json(0, "invalid request method");
    }

    /**
     * mocode 查询媒体信息
     * M - 电影     -> 1
     * T - 电视剧   -> 2
     * A - 动漫     -> 3
     * V - 综艺     -> 4
     * L - 播放列表
     */
    function get_medias_mo_code($par_motoken, $offset=0, $pagesize=30){
        $par_motoken = strtoupper($par_motoken);
        $mark = substr($par_motoken, 0, 1);
        if($mark === 'M'){  //  电影
            return get_medias_M_code($par_motoken, $offset, $pagesize);
        }elseif ($mark === 'L') {   // 打包集合的口令密码
            # code...
        }elseif ($mark === 'T') {   // 电视剧集
            return get_medias_M_code($par_motoken, $offset, $pagesize);
        }elseif ($mark == 'S') {
            return get_medias_M_code($par_motoken, $offset, $pagesize);
        }
    }

    function get_medias_M_code($par_motoken, $offset=0, $pagesize=30)
    {
        if(empty($par_motoken)){
            Response_Json::json(0,"invalid motoken", null);
        }else{
            $coll = 'medias_cover';
            $par_motoken = strtoupper($par_motoken);
            $mongo_db = new DB_MongoDB_Handler('mobox');

            $options = [
                'projection' => ['eps'=>0, 'isoff'=>0],
                'skip' => $offset, 
                'limit' => $pagesize, 
                'sort' => ['year' => -1, 'hot' => -1]
            ];

            // check the motoken is a super token, you will gei all movies
            if($par_motoken == 'SUP1RS'){
                return $mongo_db->query($coll, ['$or'=>[['isoff' => 0],['isoff' => '0']]], $options);
            }

            $filter = [
                '$or'=>[['isoff' => 0],['isoff' => '0']],
                'mocode' => $par_motoken
            ];
            return $mongo_db->query($coll, $filter, $options);
        }
    }

    function epsfilter ($x) {
        return intval($x->isoff) === 0;
    }
    function get_medias_Eps_mid($par_mid)
    {
        if(empty($par_mid)){
            Response_Json::json(0,"invalid mid", null);
        }else{
            $coll = 'medias_cover';
            $mongo_db = new DB_MongoDB_Handler('mobox');

            $options = [
                'projection' => ['eps'=>1],
                '$sort' => ['eps.hot' => -1]
            ];

            $filter = [
                '$or'=>[['eps.isoff' => 0],['eps.isoff' => '0']],
                '_id' => new \MongoDB\BSON\ObjectId($par_mid)
            ];
            $ep = $mongo_db->query($coll, $filter, $options);
            $eps = null;

            foreach ($ep as $doc) {
                $eps = json_decode(json_encode(array_filter($doc->eps, 'epsfilter')),TRUE);
            }



            $res = Array();
            if(!empty($eps)){
                foreach ($eps as $value) {
                    $value['gate'] = $mongo_db->query("playgate",
                        ['isoff'=>'0', 'inscope'=>['$regex'=>$value['playgate'], '$options' => 'i']],
                        [ 'projection' => ['inscope'=>0, 'isoff'=>0], '$sort' => ['hit' => -1]]);
                    array_push($res, $value);
                }
            }
            return $res;
        }
    }
    exit;
?>
