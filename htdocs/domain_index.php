<?php
  require_once '/usr/share/simple-rrd/config.inc.php';

  # 圖檔產生Time區間
  if (isset($_GET['interval'])){
    # 每月秒數為 2592000
    if (($_GET['interval'] < 604800) or ($_GET['interval'] > 31104000)){ # 錯誤時間
      echo 'input time error!';
      exit();
    }else{
      $interval = $_GET['interval'];
    }
  }else{
    $interval = '604800';
  }

  #顯示種類
  if (isset($_GET['cate'])){
    $cate=$_GET['cate'];
  }else{
    $cate='';
  }


  $html[] = "<html>\n<head>\n";
  $html[] = "<title>Proxy Monitor</title>\n";
  $html[] = "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"86400\">\n";
  $html[] = "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
  $html[] = "<META HTTP-EQUIV=\"Cache-Control\" content=\"no-cache\">\n";
  #$html[] = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";
  $html[] = "</head>\n<body bgcolor='#66CCFF'>\n";


  $cate_arr = array('bandwidth'=>'Bandwidth');  

  foreach($cate_arr as $key => $value){
    if ($cate == $key){
       $html[] = "<b>$value</b>\n";
    }else{
       $html[] = "<a href='{$_SERVER['PHP_SELF']}?cate=$key&interval=$interval'>$value</a>\n";
    }
  } #foreach




  $html[] = "<hr>\n";
  if ($cate <> ''){

    $arr = array('604800'=>'Week','2592000'=>'Month','31104000'=>'Year');


    foreach($arr as $key => $value){
      if ($interval == $key){
         $html[] = "<b>$value</b>\n";
      }else{
         $html[] = "<a href='{$_SERVER['PHP_SELF']}?cate=$cate&interval=$key'>$value</a>\n";
      }
    } #foreach


    $html[] = "<br>\n";
  } #if 

  


  switch ($cate){
    case 'bandwidth':

      foreach ($proxy_domain as $row){
         $db_file = "{$rrd_db_dir}{$row[0]}.rrd";
         $tmp_name = "$row[1]";
         $png_file = "{$png_dir}{$row[0]}.png";
         drawProxyDomainPng1($db_file,$png_file,$tmp_name,'100','400',$interval);
         $html[] = "<img src='./getimg.php?png_file={$row[0]}.png'>\n";

      } #foreach
      break;
    default:
      $html[] = "HiNet-IDC Monitor<br> 2007/04/12 更新至新網段<br>"; 
      #$html[] = "2006/12/12 新增SLB port 9 流量";
      break;
  }




  $html[] = "</body>\n</html>\n";
  #sleep(1);
  $html_all = "";
  foreach ($html as $line){
    $html_all = "{$html_all}{$line}";
  }
  echo $html_all;
?>
