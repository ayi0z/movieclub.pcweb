<?php

if(!function_exists('fastcgi_finish_request')) {
  ob_end_flush();
  ob_start();
}
echo '{"return":"success"}';
if(!function_exists('fastcgi_finish_request')) {
  // header("Content-Type: text/html;charset=utf-8");
  header("Connection: close");
  header('Content-Length: '. ob_get_length());
  ob_flush();
  flush();
} else {
  fastcgi_finish_request();
}

?>