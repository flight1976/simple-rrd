<?php
 
  #require_once '/usr/rrdtool/auth.php';
  require_once '/usr/share/simple-rrd/config.inc.php';
  

  $png_file = $_GET['png_file'];
 
  $png_file = "{$png_dir}$png_file"; 
  $im = LoadPNG($png_file);
  header('Pragma: no-cache');
  header('Cache-Control: no-cache');
  header("Content-type: image/png");
  
  imagepng($im);
  
?>
