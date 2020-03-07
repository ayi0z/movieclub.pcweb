<?php
  function array_filter_callback_get_years($value, $key){
    $val = intval($value);
    return $val && $val>1887 && $val<2888;
  }
?>