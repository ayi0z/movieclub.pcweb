<?php
  header('Content-type: text/html;charset=utf-8');

  require_once 'util/com.php';
  require_once 'util/db_mongodb.php';
  require_once 'redir.php';


  /**
   * pi 页码
   */
  $pi= isset($_GET['pi']) ? intval(trim($_GET['pi'])) : 0;
  /**
   * type: all-电影+电视剧，move-电影，tvs-电视剧
   */
  $type= isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'all';
  /**
   * sort: 1-最新更新，2-最高人气，3-最高评分
   */
  $sort= isset($_GET['sort']) ? intval(trim($_GET['sort'])) : 1;
  /**
   * query: 查询条件
   */
  $query= isset($_GET['query']) ? $_GET['query'] : '';
  //$query = strtolower(trim(mb_convert_encoding($query,"gbk","utf-8")));  

  $media_html_handler = new HandlerMeidaHtml();
  $media = $media_html_handler->get_mediaslist($pi, $type, $sort, $query);
  echo $media_html_handler->parse_html_play($media);

/**
 * 读取视频信息，构建静态页面
 * 1. 读 cover info
 */
class HandlerMeidaHtml {

  protected $html_path = 'tpl/index.tpl.html';
  protected $html_head_meta_tpl = 'tpl/meta.tpl.html';
  protected $html_nav_right_tpl = 'tpl/nav_right.tpl.html';
  protected $html_footer_tpl = 'tpl/footer.tpl.html';
  protected $mongo_db = null;
  protected $media_collec = 'mediascover';

  public function __construct() {
    if ( is_null($this->mongo_db) ) {
      $this->mongo_db = new DB_MongoDB_Handler('mobox');
    }
  }
  
  public function parse_html_play($data){
    if(is_null($data) || empty($data)){
      $rdp = new ReDirPage();
      return $rdp->redir_404();
      exit;
    }

    $html = file_get_contents($this->html_path);
    // <img src="{{$pic$}}" alt="{{$title$}}">
    // <div class="tags"><span class="langue">{{$langue$}}</span></div>
    $html_plitem_tpl = '<li class="plitem" style="background-image:url({{$pic$}});">
                          <a href="/h/{{$mocode$}}.html" target="_blank">
                            <div style="position:relative;">
                              <div class="pl-ct-img"></div>
                              <span class="hot"><i class="iconfont icon-huo"></i>{{$hot$}}</span>
                              <span class="top-left"><span>{{$year$}}</span>{{$area$}}</span>
                              {{$epsprog$}}
                            </div>
                            <div class="show">
                              <span class="score">{{$score$}}</span>
                              <h3 class="title short-show">{{$title$}}</h3>
                              <h4 class="title_en short-show">{{$title_en$}}</h4>
                              <div class="tags">{{$subtype$}}</div>
                              <div class="short-show">导演：{{$director$}}</div>
                              <div class="short-show">演员：{{$actor$}}</div>
                            </div>
                          </a>
                        </li>';
    $html_plitem = '';
    foreach ($data as $value) {
      $html_tmp = $html_plitem_tpl;
      $html_tmp = str_replace('{{$mocode$}}',$value['mocode'], $html_tmp);
      $html_tmp = str_replace('{{$pic$}}',$value['pic'], $html_tmp);
      $score_db = floatval($value['score']['douban']);
      $score_db = $score_db ? $score_db : '';
      $html_tmp = str_replace('{{$score$}}',$score_db, $html_tmp);
      $title = explode(' ', $value['title'], 2);
      $title_en = empty($value['title_en']) ? ( count($title)>1 ? $title[1] : $value['title']) : $value['title_en'];
      $html_tmp = str_replace('{{$title$}}',$title[0], $html_tmp);
      $html_tmp = str_replace('{{$title_en$}}', $title_en, $html_tmp);
      $html_tmp = str_replace('{{$year$}}',$value['year'], $html_tmp);
      $html_tmp = str_replace('{{$area$}}', implode(',', $value['area']), $html_tmp);
      // $html_tmp = str_replace('{{$langue$}}',$value['langue'], $html_tmp);
      $html_tmp = str_replace('{{$hot$}}',$value['hot'], $html_tmp);

      $epsprog = isset($value['epsprog']) ? $value['epsprog'] : '';
      $type = isset($value['type']) ? $value['type'] : 2;
      if(empty($epsprog)) {
        $epsc = '';
      }elseif(intval($type) == 1) {
        $epsc = intval($epsprog['epsc']) ? '<span class="bottom-left">全集</span>' : '<span class="bottom-left">'.$epsprog['epsc'].'</span>';
      }else {
        $epsc = intval($epsprog['isall']) ? '<span class="bottom-left">全集</span>' : '<span class="bottom-left">'.$epsprog['epsc'].'</span>';
      }
      $html_tmp = str_replace('{{$epsprog$}}',$epsc, $html_tmp);

      $html_tmp = str_replace('{{$director$}}', empty($value['director'])? '' : implode(', ', $value['director']), $html_tmp);
      $html_tmp = str_replace('{{$actor$}}', empty($value['actor']) ? '' : implode(', ', $value['actor']), $html_tmp);

      $subtype_html = '';
      if(!empty($value['subtype'])){
        foreach ($value['subtype'] as $sb) {
          $subtype_html = $subtype_html.'<span>'.$sb.'</span>';
        }
      }
      $html_tmp = str_replace('{{$subtype$}}',$subtype_html, $html_tmp);

      $html_plitem = $html_plitem.$html_tmp;
    }

    $html_plist = empty($html_plitem) ? '' : '<ul>'.$html_plitem.'</ul>';
    $html = str_replace('{{$playlist$}}', $html_plist, $html);

    $html_head_meta = file_get_contents($this->html_head_meta_tpl);
    $html = str_replace('{{$head_meta$}}', $html_head_meta, $html);
    $html_nav_right = file_get_contents($this->html_nav_right_tpl);
    $html = str_replace('{{$nav_right$}}', $html_nav_right, $html);
    $html_footer = file_get_contents($this->html_footer_tpl);
    $html = str_replace('{{$footer$}}', $html_footer, $html);
    
    return $html;
  }
 /**
  * pi: 页码
  * type: all-电影+电视剧， move-电影， tvs-电视剧
  * sort: 1-最新更新(latime)，2-最高人气(hot)，3-最高评分(score.douban)
  */
  function get_mediaslist($pi, $type='all', $sort=1, $query='')
  {
    $pi = intval($pi);
    $type= empty($type) ? 'all' : strtolower(trim($type));
    $sort = intval($sort);
    $query= empty($query) ? '' : trim($query);

    $pagesize = 64;
    $offset = $pi * $pagesize;

    $sort_op = ['latime'=>-1, 'hot' => -1,  'year' => -1, 'pubdate' => -1];
    if($sort == 2){
      $sort_op = ['hot'=>-1, 'latime' => -1, 'year' => -1, 'pubdate' => -1];
    } elseif ($sort == 3) {
      $sort_op = ['score.douban'=>-1, 'hot'=>-1, 'latime' => -1, 'year' => -1, 'pubdate' => -1];
    }

    $coll = $this->media_collec;
    $options = [
        'projection' => ['_id'=>1, 'title'=>1, 'title_en'=>1, 'year'=>1, 'area'=>1, 'pic'=>1, 'mocode'=>1, 'epsprog'=>1,
                         'hot'=>1, 'director'=>1, 'actor'=>1, 'type'=>1, 'subtype'=>1, 'score.douban'=>1],
        'skip' => $offset, 
        'limit' => $pagesize, 
        'sort' => $sort_op
    ];

    if($type == 'all') {
      $filter_type = [
        '$or'=>[['isoff' => 0],['isoff' => '0']]
      ];
    }else {
      $typs = ['movie'=>1, 'tvs'=>2, 'anime'=>3, 'zy'=>4];
      $type = $typs[$type];

      // $filter_type = [
      //   '$and' => [
      //     ['$or'=>[['isoff' => 0],['isoff' => '0']]],
      //     ['$or'=>[['type' => $type],['type' => strval($type)]]]
      //   ]
      // ];
      $filter_type = ['$and' => [
                                  ['$or'=>[['isoff' => 0],['isoff' => '0']]],
                                  ['$or'=>[['type' => $type],['type' => strval($type)]]]
                                ]
                    ];
    }
    if(!empty($query)){
      $t_arr = array_filter(explode(' ', $query));

      $t_years = array_filter($t_arr, 'array_filter_callback_get_years', ARRAY_FILTER_USE_BOTH);
      $filter_query = [];
      if(!empty($t_years)) {
        $t_years = array_values($t_years);
        $filter_query['year'] = ['$in'=>$t_years];
        $t_arr = array_diff($t_arr, $t_years);
      }

      $t = '.*'.implode('.*', $t_arr).'.*';
      $t = new MongoDB\BSON\Regex($t, 'i');

      $filter_query['$or'] =  [
                                ['title' => $t], 
                                ['title_en' => $t],
                                ['alias' => $t],
                                // ['actor' => ['$in'=>$t_arr]],
                                ['subtype' => ['$in'=>$t_arr]]
                              ];
    }

    $filter['$and'] = [['subtype' => ['$nin'=>['情色']]]];
    if(isset($filter_type)) {
      array_push($filter['$and'], $filter_type);
    }
    if(isset($filter_query)) {
      array_push($filter['$and'], $filter_query);
    }
    $media_cover = $this->mongo_db->query($coll, $filter, $options);

    return json_decode(json_encode($media_cover),TRUE);
  }
}

exit;
?>