<?php
  class ReDirPage {
    protected $html_head_meta_tpl = 'tpl/meta.tpl.html';
    protected $html_nav_right_tpl = 'tpl/nav_right.tpl.html';
    protected $html_footer_tpl = 'tpl/footer.tpl.html';
    
    public function redir_404(){
      $html = file_get_contents('tpl/404.tpl.html');
      $html_head_meta = file_get_contents($this->html_head_meta_tpl);
      $html = str_replace('{{$head_meta$}}', $html_head_meta, $html);
      $html_nav_right = file_get_contents($this->html_nav_right_tpl);
      $html = str_replace('{{$nav_right$}}', $html_nav_right, $html);
      $html_footer = file_get_contents($this->html_footer_tpl);
      $html = str_replace('{{$footer$}}', $html_footer, $html);
      return $html;
    }
  }
?>