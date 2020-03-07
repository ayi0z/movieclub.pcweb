<?php
  header('Content-type: text/html;charset=utf-8');

  require_once 'util/db_mongodb.php';
  require_once 'redir.php';

  if(isset($_GET['mo']))
  {
    $mo = $_GET['mo'];
    $mo = trim($mo);

    if(empty($mo)){
      $rdp = new ReDirPage();
      return $rdp->redir_404();
    } else {
      $tpl = strtolower(trim($mo))=='tvlive' ? 'tpl/tvlive.tpl.html' : 'tpl/play.tpl.html';
      $media_html_handler = new HandlerMeidaHtml($tpl);
      $media = $media_html_handler->get_medias_M_code($mo);
      echo $media_html_handler->parse_html_play($media);
    }
  }




function parse_query($ac){
  return '<a href="/q/'.$ac.'/">'.$ac.'</a>';
}
/**
 * 读取视频信息，构建静态页面
 * 1. 读 cover info
 * 2. 读 playgate
 */
class HandlerMeidaHtml {

  protected $html_path = 'tpl/play.tpl.html';
  protected $html_head_meta_tpl = 'tpl/meta.tpl.html';
  protected $html_nav_right_tpl = 'tpl/nav_right.tpl.html';
  protected $html_footer_tpl = 'tpl/footer.tpl.html';
  protected $mongo_db = null;
  protected $media_collec = 'mediascover';

  public function __construct($tpl = '') {
    if ( is_null($this->mongo_db) ) {
      $this->mongo_db = new DB_MongoDB_Handler('mobox');
    }
    if(!empty($tpl)){
      $this->html_path=$tpl;
    }
  }

  public function parse_html_play($data){
    if(is_null($data) || empty($data)){
      $rdp = new ReDirPage();
      return $rdp->redir_404();
      exit;
    }

    $play = array_shift($data);
    $html = file_get_contents($this->html_path);
    $html = str_replace('{{$pic$}}',$play['pic'], $html);
    $html = str_replace('{{$title$}}',$play['title'], $html);
    $html = str_replace('{{$title_en$}}',$play['title_en'], $html);
    $html = str_replace('{{$year$}}',$play['year'], $html);
    $html = str_replace('{{$area$}}',empty($play['area']) ? '':'<span class="tag area">'.implode(',', $play['area']).'</span>', $html);
    $html = str_replace('{{$langue$}}',empty($play['langue']) ? '':'<span class="tag langue">'.$play['langue'].'</span>', $html);
    $html = str_replace('{{$hot$}}',$play['hot'], $html);
    $html = str_replace('{{$director$}}', empty($play['director'])? '' : implode(', ', $play['director']), $html);
    $html = str_replace('{{$actor$}}', empty($play['actor']) ? '' : implode(', ', $play['actor']), $html);
    $html = str_replace('{{$desc$}}',$play['desc'], $html);

    $subtype = '';
    $subtype_data = empty($play['subtype']) ? [] : $play['subtype'];
    foreach ($subtype_data as $value) {
      $subtype = $subtype.'<span class="tag subtype"><a href="/q/'.$value.'/">'.$value.'</a></span>';
    }
    $html = str_replace('{{$subtype$}}',$subtype, $html);

    $eps = '';
    $eps_data = empty($play['eps']) ? [] : $play['eps'];
    foreach ($eps_data as $value) {
      $eps = $eps.'<li class="eps-epli" data-epid="'.$value['epid'].'" onclick="switch_ep(\''.$value['epid'].'\')">'.$value['title'].'</li>';
    }
    $html = str_replace('{{$eps$}}',$eps, $html);
    $html = str_replace('{{$jsoneps$}}', json_encode($eps_data, JSON_UNESCAPED_UNICODE), $html);

    $html_plitem_tpl = '<li class="plitem">
          <a href="/h/{{$mocode$}}.html" target="_blank">
            <div style="position:relative;">
              <div class="pl-ct-img"><img src="{{$pic$}}" alt="{{$title$}}"></div>
              <span class="hot"><i class="iconfont icon-huo"></i>{{$hot$}}</span>
              <h3 class="title">{{$title$}}</h3>
              <span class="year">{{$year$}}</span>
              <span class="area">{{$area$}}</span>
            </div>
          </a>
        </li>';
    $html_plitem = '';

    $related_data = $this->get_related_medias($play);

    foreach ($related_data as $value) {
      $html_tmp = $html_plitem_tpl;
      $html_tmp = str_replace('{{$mocode$}}',$value['mocode'], $html_tmp);
      $html_tmp = str_replace('{{$pic$}}',$value['pic'], $html_tmp);
      $html_tmp = str_replace('{{$title$}}',$value['title'], $html_tmp);
      $html_tmp = str_replace('{{$year$}}',$value['year'], $html_tmp);
      $html_tmp = str_replace('{{$area$}}',empty($value['area']) ? '' : implode(',', $value['area']), $html_tmp);
      $html_tmp = str_replace('{{$hot$}}',$value['hot'], $html_tmp);

      $subtype_html = '';
      if(!empty($value['subtype'])){
        foreach ($value['subtype'] as $sb) {
          $subtype_html = $subtype_html.'<span>'.$sb.'</span>';
        }
      }
      $html_tmp = str_replace('{{$subtype$}}',$subtype_html, $html_tmp);

      $html_plitem = $html_plitem.$html_tmp;
    }
    $html = str_replace('{{$plitem$}}',$html_plitem, $html);

    $html_head_meta = file_get_contents($this->html_head_meta_tpl);
    $html = str_replace('{{$head_meta$}}', $html_head_meta, $html);
    $html_nav_right = file_get_contents($this->html_nav_right_tpl);
    $html = str_replace('{{$nav_right$}}', $html_nav_right, $html);
    $html_footer = file_get_contents($this->html_footer_tpl);
    $html = str_replace('{{$footer$}}', $html_footer, $html);
    return $html;
  }

  public function playgate ($eps) {
    $res = Array();
    if(!empty($eps)){
        foreach ($eps as $value) {
            $value['gate'] = $this->mongo_db->query("playgate",
                ['isoff'=>'0', 'inscope'=>['$regex'=>$value['playgate'], '$options' => 'i']],
                [ 'projection' => ['inscope'=>0, 'isoff'=>0], '$sort' => ['hit' => -1]]);
            array_push($res, $value);
        }
    }
    return $res;
  }

  function get_related_medias($querypar, $limit = 12) {
      if(empty($querypar)){
          return [];
      }
      
      $coll = $this->media_collec;
      $options = [
          'projection' => ['_id'=>1, 'title'=>1, 'title_en'=>1, 'year'=>1, 'area'=>1, 'pic'=>1, 'mocode'=>1, 'type'=>1,
                           'langue'=>1, 'hot'=>1, 'director'=>1, 'actor'=>1, 'desc'=>1, 'subtype'=>1, 'epsprog'=>1],
          'limit' => $limit
      ];
      $k_title = trim($querypar['title']);
      $k_title = '.*'.str_replace(' ', '.*', $k_title).'.*';
      $k_title = new MongoDB\BSON\Regex($k_title); 
      $k_actor = isset($querypar['actor']) ? $querypar['actor'] : [];
      $k_director = isset($querypar['director']) ? $querypar['director'] : [];
      $k_year = isset($querypar['year']) ? $querypar['year'] : [];

      $ft_or = [
                  ['title' => ['$regex'=>$k_title, '$options' => 'i']], 
                  ['title_en' => ['$regex'=>$k_title, '$options' => 'i']],
                  ['alias' => ['$regex'=>$k_title, '$options' => 'i']]
              ];
      if(!empty($k_actor)) { array_push($ft_or, ['actor' => ['$in' => $k_actor]]); }
      if(!empty($k_director)) { array_push($ft_or, ['director' => ['$in' => $k_director]]); }
      if(!empty($k_year)) { array_push($ft_or, ['year' => $k_year]); }

      $filter = [
        '$and' => [
          ['$or'=>[['isoff' => 0],['isoff' => '0']]],
          ['$or'=>$ft_or ]
        ],
        '_id' =>['$ne' => new \MongoDB\BSON\ObjectId($querypar['_id']['$oid'])]
      ];
      $media_cover = $this->mongo_db->query($coll, $filter, $options);
      return json_decode(json_encode($media_cover),TRUE);
  }

  function get_medias_M_code($par_motoken, $offset=0, $pagesize=30)
  {
      $par_motoken = trim($par_motoken);
      if(empty($par_motoken)){
          $rdp = new ReDirPage();
          return $rdp->redir_404();
          exit;
      }
      
      $coll = $this->media_collec;
      $options = [
          'projection' => ['_id'=>1, 'title'=>1, 'title_en'=>1, 'year'=>1, 'area'=>1, 'pic'=>1, 'mocode'=>1, 'type'=>1,
                           'langue'=>1, 'hot'=>1, 'director'=>1, 'actor'=>1, 'desc'=>1, 'subtype'=>1, 'eps'=>1],
          'skip' => $offset, 
          'limit' => $pagesize, 
          'sort' => ['year' => -1, 'hot' => -1]
      ];

      $filter = [
        '$or'=>[['isoff' => 0],['isoff' => '0']],
        'mocode' => $par_motoken
      ];
      $media_cover = $this->mongo_db->query($coll, $filter, $options);

      $eps = null;
      foreach ($media_cover as $doc) {
          if(!empty($doc->eps)){
            $eps = json_decode(json_encode(array_filter($doc->eps, 'epsfilter')),TRUE);
            $doc->eps = $this->playgate($eps);

            $upfilter = ["_id" => $doc->_id];
            $seter = ['$inc' => ['hot'=>1]];
            $this->mongo_db->update($this->media_collec, $seter, $upfilter, ['multi' => false, 'upsert' => false]);
          }
      }
      return json_decode(json_encode($media_cover),TRUE);
  }
}
function epsfilter ($x) {
  return intval($x->isoff) === 0;
}

exit;
?>