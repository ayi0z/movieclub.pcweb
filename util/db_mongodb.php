<?php
    use MongoDB\Driver\BulkWrite;
    use MongoDB\Driver\Exception\Exception;
    use MongoDB\Driver\Manager;
    use MongoDB\Driver\Query;
    use MongoDB\Driver\WriteConcern;
    use MongoDB\Driver\WriteResult;
    use MongoDB\Driver\MongoException;


    class DB_MongoDB_Handler{
        protected static $constring = "mongodb+srv://mobox_ayi:okm,lp@mobox-t31ru.azure.mongodb.net/mobox?retryWrites=true";
        protected static $Manager = null;
        private $manager = null;
        private $db = null;

        public function __construct($_db) {
            //连接数据库
            if ( is_null(self::$Manager) ) {
                $this->_connect();
            }
            $this->manager = self::$Manager;
            $this->db = $_db;
        }
        protected function _connect() {
            if(empty(self::$Manager)){
                self::$Manager = new MongoDB\Driver\Manager(self::$constring);
            }
        }

        private function throwerror($error=''){
            // Response_Json::json("0", $error);
            echo '500 error in db: '.$error;
        }

        public function update($coll, $set, $filter=[], $options=['multi' => true, 'upsert' => true]) {
            if(empty($set) || empty($coll)){
                return false;
            }

            try {
                $bulk = new BulkWrite();
                $bulk->update($filter, $set, $options);
                $concern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
                $this->manager->executeBulkWrite($this->db.'.'.$coll, $bulk, $concern);
            } catch (Exception $e) {
                $this->throwerror($e->getMessage());
            }
        }

        public function insert($doc, $coll, $rnId = false) {
            if(empty($doc) || empty($coll)){
                return false;
            }

            try {
                $bulk = new BulkWrite();
                $inerted_id = $bulk->insert($doc);
                $this->manager->executeBulkWrite($this->db.'.'.$coll, $bulk);
                if($rnId){ return $inerted_id; }
            } catch (Exception $e) {
                $this->throwerror($e->getMessage());
            }
        }

        public function insertMany($data, $coll){
            if(empty($data) || empty($coll)){
                return false;
            }
            try {
                $bulk = new BulkWrite();
                foreach ($data as $key => $value) {
                    $bulk->insert($value);
                }
                $this->manager->executeBulkWrite($this->db.'.'.$coll, $bulk);
            } catch (Exception $e) {
                $this->throwerror($e->getMessage());
            }
        }

        /**
         * eg:
         *   $filter = ['x' => ['$gt' => 1]];
         *   $options = [
         *      'projection' => ['_id' => 0, 'title' => 0],   // _id  和 title 字段不会输出，0 不输出，1输出。
         *      'sort' => ['x' => -1],    // 按照 x 降序排序： 1升序，-1降序。
         *      'skip' => 100,    // 跳过100条，从第101条开始输出。
         *      'limit' => 100      // 最多输出 100 条。
         *   ];
         */
        public function query($coll, $filter=[], $options=[]){
            try {
                $query = new \MongoDB\Driver\Query($filter, $options);
                $cursor = $this->manager->executeQuery($this->db.'.'.$coll, $query);
                $info = $cursor->toArray();
                return $info;
            } catch (\MongoDB\Driver\Exception $e) {
                $this->throwerror($e->getMessage());
            }
        }

        public function queryCount($coll, $filter = []){
            try {
                $cmd["count"] = $coll;
                if(!empty($filter)){
                    $cmd["query"] = $filter;
                }

                $command = new \MongoDB\Driver\Command($cmd);
                $cursor = $this->manager->executeCommand($this->db, $command);
                $info = $cursor->toArray();
                $count = $info[0]->n;
                return $count;
            } catch (\MongoDB\Driver\Exception $e) {
                $this->throwerror($e->getMessage());
            }
        }
    }
?>
