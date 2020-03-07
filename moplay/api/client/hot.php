<?php
  require_once dirname(__FILE__)."/../util/fastclosecon.php";
  require_once dirname(__FILE__).'/../util/db_mongodb.php';

  header('content-type:application/json;charset=utf-8');

  $Request_Method = strtoupper($_SERVER['REQUEST_METHOD']);
  if ($Request_Method === 'POST' || $Request_Method === 'GET') {

    if(isset($_POST["mid"])){
      $mid = $_POST["mid"];
      $mongo_db = new DB_MongoDB_Handler("mobox");
      $filter = ["_id" => new \MongoDB\BSON\ObjectId($mid)];
      $seter = ['$inc' => ['hot'=>1]];
      $mongo_db->update('medias_cover', $seter, $filter);
    }
  }
?>