<?php
  require_once '/usr/share/simple-rrd/config.inc.php';


  if (isset($_GET['stime'])){
    $stime = $_GET['stime'];
  }else{
    $stime = 0;
  }


  # 圖檔產生Time區間
  if (isset($_GET['interval'])){
    # 每月秒數為 2592000
    if (($_GET['interval'] <= 0) or ($_GET['interval'] > 31536000)){ # 錯誤時間
      echo 'input time error!';
      exit();
    }else{
      $interval = $_GET['interval'];
    }
  }else{
    $interval = '57600';
  }

  #顯示種類
  if (isset($_GET['cate'])){
    $cate=$_GET['cate'];
  }else{
    $cate='';
  }


  $html[] = "<html>\n<head>\n";
  $html[] = "<title>$web_title</title>\n";
  $html[] = "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">\n";
  $html[] = "<META HTTP-EQUIV=\"EXPIRES\" CONTENT=\"Mon, 22 Jul 2002 11:12:01 GMT\">\n";

  #只有非歷史查詢時才需refresh網頁
  if ($stime == '0'){
    $html[] = "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"60\">\n";
  }

#  $html[] = "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
#  $html[] = "<META HTTP-EQUIV=\"Cache-Control\" content=\"no-cache\">\n";
  #$html[] = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";
  if ($stime == '0'){
    $html[] = "</head>\n<body bgcolor='#66CCFF'>\n";
  }else{
    $html[] = "</head>\n<body bgcolor='#7FFFD4'>\n";
  }

  $html[] = "<font color='blue'><b>$web_title</b></font><br>";

#  $cate_arr = array('cpu'=>'CPU','session'=>'Sessions','bandwidth'=>'Bandwidth','banned'=>'Banned IP');  
/*
  $cate_arr = array('slb_cpu'=>'AlteonCPU', 'bandwidth'=>'Bandwidth', 'session'=>'Sessions(rs 1-16)', 'linux'=>'Linux', 
                     'linux_cpu'=>'Linux(CPU)', 'linux_memory'=>'Linux(Mem)', 'linux_diskio'=>'Linux(DiskIO)', 'switch_health'=>'Cisco Switch',
                     'fortinet_session'=>'FortiNet(Session)');  
*/
  $cate_arr = array('rack_temp'=>'機櫃溫度', 'slb_cpu_all'=>'AlteonCPU', 'session_all'=>'Sessions', 'bandwidth'=>'Bandwidth', 'linux'=>'Linux', 'linux_netstat'=>'Linux Netstat', 'linux_load'=>'Linux(Load)', 'linux_cpu'=>'Linux(CPU)', 
                    'linux_memory'=>'Linux(Mem)', 'linux_diskio'=>'Linux(DiskIO)', 'bookmarks'=>'Bookmarks');



  foreach($cate_arr as $key => $value){
    if ($cate == $key){
       $html[] = "<b>$value</b>\n";
    }else{
       $html[] = "<a href='{$_SERVER['PHP_SELF']}?cate=$key&stime=$stime&interval=$interval'>$value</a>\n";
    }
  } #foreach


/*
  $html[] = "<a href='{$_SERVER['PHP_SELF']}?cate=cpu&interval={$interval}'>CPU</a>\n";
  $html[] = "<a href='{$_SERVER['PHP_SELF']}?cate=session&interval={$interval}'>Sessions</a>\n";
  $html[] = "<a href='{$_SERVER['PHP_SELF']}?cate=bandwidth&interval={$interval}'>Bandwidth</a>\n";
*/


  $html[] = "<hr>\n";
  if ($cate <> ''){

    $arr = array('7200'=>'2hrs','14400'=>'4hrs','28800'=>'8hrs','57600'=>'16hrs','86400'=>'Day','604800'=>'Week','2592000'=>'Month','31536000'=>'Year');
#    $arr2 = array('604800'=>'Week','2592000'=>'Month','31104000'=>'Year');


    foreach($arr as $key => $value){
      if ($interval == $key){
         $html[] = "<b>$value</b>\n";
      }else{
         $html[] = "<a href='{$_SERVER['PHP_SELF']}?cate=$cate&stime=$stime&interval=$key'>$value</a>\n";
      }
    } #foreach


    $html[] = "<br>\n";
  } #if 

  


  switch ($cate){
    case 'rack_temp':
        $db_file = "{$rrd_db_dir}temp-test.rrd";
        $tmp_name = "Rack Temperature/Humidity";
        $png_file = "{$png_dir}temperature-test.png";
	drawArduinoTempHum($db_file,$png_file,$tmp_name,'200','600',$stime,$interval);
	$html[] = "<img src='./getimg.php?png_file=temperature-test.png'>\n";
        
      break;
    case 'switch_health':
      foreach($cisco_switch as $row){
        $db_file = "{$rrd_db_dir}{$row[0]}-cpu.rrd";
        $tmp_name = "$row[1]";
        $png_file = "{$png_dir}{$row[0]}-cpu.png";
        drawCiscoCPUPng($db_file,$png_file,$tmp_name,'100','400',$stime,$interval);
        $html[] = "<img src='./getimg.php?png_file={$row[0]}-cpu.png'>\n";
      }
      break;
/*
    case 'fortinet_session':
      foreach($fortinet_fw as $row){
        $db_file = "{$rrd_db_dir}{$row[0]}-session.rrd";
        $tmp_name = "$row[1]";
        $png_file = "{$png_dir}{$row[0]}-session.png";
        drawSessPng1($db_file,$png_file,"$tmp_name",'100','400',$stime,$interval);
        $html[] = "<img src='./getimg.php?png_file={$row[0]}-session.png'>\n";
      } 
      break;
    case 'session2':
      for($i=1;$i<=8;$i++){
        $tmp = str_pad($i,2,"0",STR_PAD_LEFT);
        $tmp_name = "rs{$tmp}";
        $db_file = "{$rrd_db_dir}{$tmp_name}.rrd";
        $png_file = "{$png_dir}{$tmp_name}.png";
        drawSessPng2($db_file,$png_file,"RealServer $tmp",'100','300',$stime,$interval);
        $html[] = "<img src='./getimg.php?png_file={$tmp_name}.png'>\n";
      } #for
      break;

*/
    case 'session':
      for($i=1;$i<=4;$i++){
        $tmp = str_pad($i,2,"0",STR_PAD_LEFT);
        $tmp_name = "rs{$tmp}";
        $db_file = "{$rrd_db_dir}{$tmp_name}.rrd";
        $png_file = "{$png_dir}{$tmp_name}.png";
        drawSessPng2($db_file,$png_file,"RealServer $tmp",'100','300',$stime,$interval);
        $html[] = "<img src='./getimg.php?png_file={$tmp_name}.png'>\n";
      } #for
      break;
    case 'session_all':
      foreach ($alteon_slb as $line){ //處理每台SLB
        $slb_name = $line[0];
        $slb_ip = $line[1];
        $slb_rs = $line[4];
        $slb_rs_color = $line[5];
        foreach ($slb_rs as $rs_key => $rs_value){ //處理每個Real Server Session圖
          $rrd_file = "$slb_name-rs-$rs_key";
          $db_file = "{$rrd_db_dir}{$rrd_file}.rrd";
          $png_file = "{$png_dir}{$rrd_file}.png";
          drawSessPng3($db_file,$png_file,"$slb_name RealServer $rs_value",'100','300',$stime,$interval,$slb_rs_color);
          $html[] = "<img src='./getimg.php?png_file={$rrd_file}.png'>\n";
        }
        $html[] = "<hr>\n";
      }
      break;

    case 'bandwidth':

      #uplink total
      if (isset($bw_group_rrd[0])){
        $png_file = "{$png_dir}{$bw_group_rrd[0]}.png";
        foreach ($bw_group_rrd[2] as $tmp_db_file){
          $db_files[] = "{$rrd_db_dir}{$tmp_db_file}.rrd";
        }
        #print_r($db_files);
        drawBWPng_multi($db_files,$png_file,$bw_group_rrd[1],100,400,$stime,$interval);
        $html[] = "<img src='./getimg.php?png_file={$bw_group_rrd[0]}.png'>\n";
      }

      foreach ($bw_rrd as $row){
         $db_file = "{$rrd_db_dir}{$row[0]}.rrd";
         $tmp_name = "$row[1]";
         $png_file = "{$png_dir}{$row[0]}.png";
         $ulimit = $row[6];
	 $bg_color = $row[7];
         drawBWPng($db_file, $png_file, $tmp_name, '100', '400', $stime, $interval, $ulimit, $bg_color);
         $html[] = "<img src='./getimg.php?png_file={$row[0]}.png'>\n";

      } #foreach
      $html[] = "<hr>\n";
      
      # 手動設定開始繪圖時間點
      $today = date('U', mktime(0, 0, 0, date("m") , date("d") , date("Y")));
      $html[] = "<table border='1' cellspacing='0' style='font-size:13px;'>\n";
      $html[] = "<tr><td>Date</td><td colspan='23'>Hours</td></tr>\n";
      # 產生今日00:00往後推1~23小時的歷史資料連結
      $html[] = "<tr><td><a href='{$_SERVER['PHP_SELF']}?cate=bandwidth&stime=0&interval=$interval'><b>reset</b></a></td>\n";
      for ($j=1;$j<=23;$j++){ //產生每日小時數
        $manual_shour = $today + 3600 * $j;
        $display_shour = str_pad($j, 2, '0',STR_PAD_LEFT);
        $html[] = "<td><a href='{$_SERVER['PHP_SELF']}?cate=bandwidth&stime=$manual_shour&interval=$interval'>+{$display_shour}h</a></td>\n";
      }
      $html[] = "</tr>\n";

      # 產生昨日起之資料連結
      for ($i=1;$i<=30;$i++){ //產生日期
        $html[] = "<tr>\n";
        $manual_stime = $today - 86400 * $i;
        $display_manual_stime = date('r', $manual_stime);
        $html[] = "<td><a href='{$_SERVER['PHP_SELF']}?cate=bandwidth&stime=$manual_stime&interval=$interval'>$display_manual_stime</a></td>\n";
        for ($j=1;$j<=23;$j++){ //產生每日小時數
          $manual_shour = $manual_stime + 3600 * $j;
          $display_shour = str_pad($j, 2, '0',STR_PAD_LEFT);
          $html[] = "<td><a href='{$_SERVER['PHP_SELF']}?cate=bandwidth&stime=$manual_shour&interval=$interval'>+{$display_shour}h</a></td>\n";
        }
        $html[] = "</tr>\n";
      }
      $html[] = "</table>\n";
      break;
    case 'slb_cpu':
       $db_file = "{$rrd_db_dir}alteonCPU.rrd";
       $png_file = "{$png_dir}alteonCPU.png";
       drawAlteonCPUPng1($db_file,$png_file,'Alteon3408 CPU','120','600',$stime,$interval); 
       $html[] = "<img src='./getimg.php?png_file=alteonCPU.png'><br>\n";
       break;
    case 'slb_cpu_all':
      foreach($alteon_slb as $line){
        $name = $line[0];
        $db_file = "{$rrd_db_dir}$name-alteonCPU.rrd";
        $png_file = "{$png_dir}$name-alteonCPU.png";
        drawAlteonCPUPng1($db_file,$png_file,"$name CPU",'120','600',$stime,$interval);
        $html[] = "<img src='./getimg.php?png_file=$name-alteonCPU.png'><br>\n";
      }
       break;
    case 'linux':
       #draw linux cpu
       foreach($proxy_server as $server){
         $db_file = "{$rrd_db_dir}{$server[0]}-cpu.rrd";
         $png_file = "{$png_dir}{$server[0]}-cpu.png";
         drawLinuxCpuPng1($db_file,$png_file,"CPU {$server[1]}",'100','300',$stime,$interval);
         $html[] = "<img src='./getimg.php?png_file={$server[0]}-cpu.png'>\n";
         #draw memory
         $db_file = "{$rrd_db_dir}{$server[0]}-memory.rrd";
         $png_file = "{$png_dir}{$server[0]}-memory.png";
         drawLinuxMemoryPng2($db_file,$png_file,"Memory {$server[1]}",'100','360',$stime,$interval);
         $html[] = "<img src='./getimg.php?png_file={$server[0]}-memory.png'>\n";

         #draw diskIO
         foreach($server[5] as $disk_key => $disk_name){
           $db_file = "{$rrd_db_dir}{$server[0]}-disk_io-$disk_key.rrd";
           $png_file = "{$png_dir}{$server[0]}-disk_io-$disk_key.png";
           drawLinuxDiskIOPng1($db_file,$png_file,"DiskIO {$server[1]} ($disk_name)",'100','400',$stime,$interval);
           $html[] = "<img src='./getimg.php?png_file={$server[0]}-disk_io-$disk_key.png'>\n";
         }//foreach
         $html[] = "<hr>\n";
       }//foreach
       break;
    case 'linux_cpu':
      foreach($proxy_server as $server){
         $db_file = "{$rrd_db_dir}{$server[0]}-cpu.rrd";
         $png_file = "{$png_dir}{$server[0]}-cpu.png";
         drawLinuxCpuPng1($db_file,$png_file,"CPU {$server[1]}",'100','300',$stime,$interval);
         $html[] = "<img src='./getimg.php?png_file={$server[0]}-cpu.png'>\n";
      }
      break;
    case 'linux_load':
      foreach($proxy_server as $server){
         $db_file = "{$rrd_db_dir}{$server[0]}-load.rrd";
         $png_file = "{$png_dir}{$server[0]}-load.png";
         drawLinuxLoadPng($db_file,$png_file,"Load {$server[1]}",'100','320',$stime,$interval);
         $html[] = "<img src='./getimg.php?png_file={$server[0]}-load.png'>\n";
      }
      break;


    case 'linux_memory':
      foreach($proxy_server as $server){
         #draw memory
         $db_file = "{$rrd_db_dir}{$server[0]}-memory.rrd";
         $png_file = "{$png_dir}{$server[0]}-memory.png";
         drawLinuxMemoryPng2($db_file,$png_file,"Memory {$server[1]}",'100','360',$stime,$interval);
         $html[] = "<img src='./getimg.php?png_file={$server[0]}-memory.png'>\n";

      }

      break;
    case 'linux_diskio':
      foreach($proxy_server as $server){
         #draw diskIO
         foreach($server[5] as $disk_key => $disk_name){
           $db_file = "{$rrd_db_dir}{$server[0]}-disk_io-$disk_key.rrd";
           $png_file = "{$png_dir}{$server[0]}-disk_io-$disk_key.png";
           drawLinuxDiskIOPng1($db_file,$png_file,"DiskIO {$server[1]} ($disk_name)",'100','400',$stime,$interval);
           $html[] = "<img src='./getimg.php?png_file={$server[0]}-disk_io-$disk_key.png'>\n";
         }//foreach
         $html[] = "<hr>\n";
      }//foreach

      break;

    case 'linux_netstat':
      $html[] = "<font color=red>建置中</font>";
/*
      $html[] = "
        <img src='http://203.69.82.201/rrd-netstat/img/day.png'>\n
        <img src='http://203.69.82.202/rrd-netstat/img/day.png'>\n
        <img src='http://203.69.82.203/rrd-netstat/img/day.png'>\n
        <img src='http://203.69.82.204/rrd-netstat/img/day.png'>\n
      ";
*/
      break;

    case 'banned':
      #Alteon Banned IP
      # drawAlteonBanIpPng1($rrd_file,$png_file,$title,$h,$w,$stime,$interval)
      drawAlteonBanIpPng1("{$rrd_db_dir}alteonban.rrd","{$png_dir}alteonban.png","Alteon Banned IP",100,400,$stime,$interval);
      $html[] = "<img src='./getimg.php?png_file=alteonban.png'>\n";

/*
      # RealServer Banned IP
      foreach($rs_arr2 as $ip => $file){
        $db_file = "{$rrd_db_dir}{$file}.rrd";
        $png_file = "{$png_dir}{$file}.png";
        drawBannedPng1($db_file,$png_file,"Server: $ip",'100','400',$stime,$interval);
        $html[] = "<img src='./getimg.php?png_file={$file}.png'>\n";
      } #for

*/
      break;
 
    case 'bookmarks':
      $html[] = "<font color=red>建置中</font>";

/*
      $row[0] = 'bw-gluster08-eth0';
      $db_file = "{$rrd_db_dir}{$row[0]}.rrd";
      $tmp_name = "Gluster08 eth0";
      $png_file = "{$png_dir}{$row[0]}.png";
      $ulimit = 1000000;
      drawBWPng($db_file, $png_file, $tmp_name, '100', '400', $stime, $interval, $ulimit);
      $html[] = "<img src='./getimg.php?png_file={$row[0]}.png'>\n";

      $row[0] = 'bw-gluster08-eth1';
      $db_file = "{$rrd_db_dir}{$row[0]}.rrd";
      $tmp_name = "Gluster08 eth1";
      $png_file = "{$png_dir}{$row[0]}.png";
      $ulimit = 1000000;
      drawBWPng($db_file, $png_file, $tmp_name, '100', '400', $stime, $interval, $ulimit);
      $html[] = "<br><img src='./getimg.php?png_file={$row[0]}.png'>\n";

      $server[0] = 'lab-gluster08';
      $server[1] = 'Lab: Gluster08';
      $db_file = "{$rrd_db_dir}{$server[0]}-load.rrd";
      $png_file = "{$png_dir}{$server[0]}-load.png";
      drawLinuxLoadPng($db_file,$png_file,"Load {$server[1]}",'100','400',$stime,$interval);
      $html[] = "<br><img src='./getimg.php?png_file={$server[0]}-load.png'>\n";


      $db_file = "{$rrd_db_dir}lab-gluster08-disk_io-19.rrd";
      $png_file = "{$png_dir}lab-gluster08-disk_io-19.png";
      $disk_name = 'sda2';
      drawLinuxDiskIOPng1($db_file,$png_file,"DiskIO gluster08 ($disk_name)",'100','400',$stime,$interval);
      $html[] = "<br><img src='./getimg.php?png_file=lab-gluster08-disk_io-19.png'>\n";
*/
      break; 
    default:
      $html[] = "simple-rrd 2012/05/01 by 謝志宏 (jihhong@cht.com.tw)<br>"; 
      break;
  }




  $html[] = "</body>\n</html>\n";
  #sleep(1);

 
  # send html header
  #header('Content-type: text/html; charset=UTF-8');
  #header('EXPIRES: Mon, 22 Jul 2002 11:12:01 GMT');
  #header('Refresh: 60');
  header('Pragma: no-cache');
  header('Cache-Control: no-cache');

  $html_all = "";
  foreach ($html as $line){
    $html_all = "{$html_all}{$line}";
  }
  echo $html_all;
?>
