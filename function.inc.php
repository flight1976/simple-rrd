<?php
  
  function get_ifAlias($ip,$community,$snmp_version,$index){
    $exec = "snmpget -c $community -v $snmp_version $ip ifAlias.$index -O nv";
    #echo "$exec<br>\n";
    unset($output);
    exec($exec,$output);
    $data = trim($output[0]);
    $pos = strpos($data,"STRING:");
    if ($pos === false){
      return false;
    }else{
      $tmp = split(":",$data);
      return trim($tmp[1]);
    }
  }



function LoadPNG($imgname)
{
    $im = @imagecreatefrompng($imgname); /* Attempt to open */
    if (!$im) { /* See if it failed */
        $im  = imagecreatetruecolor(150, 30); /* Create a blank image */
        $bgc = imagecolorallocate($im, 255, 255, 255);
        $tc  = imagecolorallocate($im, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, 150, 30, $bgc);
        /* Output an errmsg */
        imagestring($im, 1, 5, 5, "Error loading $imgname", $tc);
    }
    return $im;
}

function get_temperature(){
  $exec = '/usr/bin/python /usr/rrdtool/simple-rrd/readtemp.py';
  exec ($exec, $output);
  $tt = preg_split('/\,/',$output[0]);
  #print_r($tt);
  return $tt;
}

function update_slb($name, $ip, $community, $snmp_version, $rs_array){
  global $rrd_bin, $rrd_db_dir;
  #抓取/stat/slb/group 1的real server session

  #$exec = "snmpwalk -m ALL -O qn -c $alteon3408_community -v $alteon3408_snmpv  $alteon3408_ip .1.3.6.1.4.1.1872.2.5.4.2.2.1.2";
  $exec = "snmpwalk -m ALL -O qn -c $community -v $snmp_version  $ip .1.3.6.1.4.1.1872.2.5.4.2.2.1.3";
  #echo "$exec\n";
  exec($exec,$output);

  #整理資料
  foreach ($output as $line){
    $row = str_replace('.1.3.6.1.4.1.1872.2.5.4.2.2.1.3.','',$line);
    $row = split(" ",$row);
    $tt = $row[0];
    $value1[$tt] = trim($row[1]);
  }


  #print_r($value1);
  #update Sessions
  foreach ($rs_array as $rs_key => $rs_value){
    $rrd_file = "$name-rs-$rs_key";

    $db_file = "{$rrd_db_dir}{$rrd_file}.rrd";
    #如果檔案不存在則產生rrd db file
    if(!file_exists($db_file)){
       echo "creating $db_file  .....";
       #$exec = "cp {$rrd_db_dir}default/default-rs.rrd $db_file";
       $exec = "$rrd_bin create $db_file --step 60 DS:ds0:COUNTER:120:U:U RRA:AVERAGE:0.5:1:525600  RRA:MAX:0.5:5:1";
       exec($exec);
       echo "done\n";
    }

    $exec = "$rrd_bin update $db_file N:{$value1[$rs_key]}";
    #echo "$exec\n";
    exec($exec);
  }

  #update CPU
  #mpx1 mpCpuStatsUtil64Seconds.0
  #spx4 spStatsCpuUtil64Seconds
  $exec = "snmpwalk -m ALL -O qv -c $community -v $snmp_version $ip .1.3.6.1.4.1.1872.2.5.1.2.2.3.0";
  exec($exec,$value_mp64);

  $exec = "snmpwalk -m ALL -O qv -c $community -v $snmp_version $ip .1.3.6.1.4.1.1872.2.5.1.2.4.1.1.4";
  exec($exec,$t_value);

  $db_file = "{$rrd_db_dir}$name-alteonCPU.rrd";
  #如果檔案不存在則產生rrd db file
    if(!file_exists($db_file)){
       echo "creating $db_file  .....";
#       $exec = "cp {$rrd_db_dir}default/default-alteonCPU-1m.rrd $db_file";
       $exec = "$rrd_bin create $db_file --step 60 DS:ds0:GAUGE:120:0:100 DS:ds1:GAUGE:120:0:100 DS:ds2:GAUGE:120:0:100 DS:ds3:GAUGE:120:0:100 DS:ds4:GAUGE:120:0:100 RRA:AVERAGE:0.5:1:525600  RRA:MAX:0.5:5:1";
       exec($exec);
       echo "done\n";
    }

  #$exec = "$rrd_bin update $db_file N:$value_mp64[0]:$t_value[0]:$t_value[1]:$t_value[2]:$t_value[3]";
  $exec = "$rrd_bin update $db_file N:$value_mp64[0]:$t_value[0]:$t_value[1]:$t_value[2]:-1";
  #echo "$exec\n";
  exec($exec);
}
#function update_slb ############################################################################################################

  function drawProxyDomainPng1($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }


    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -h $h -w $w -s $stime -e $etime ";
    $exec[] = "DEF:ds0a={$rrd_file}:ds0:AVERAGE ";
    $exec[] = "CDEF:ds0=1000000,ds0a,* ";
    $exec[] = "AREA:ds0#00FF00:\"download \\n\"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%6.2lf %sb/s\" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%6.2lf %sb/s \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%6.2lf %sb/s \\n\" ";

    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end


  function drawSessPng1($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }
    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -h $h -w $w -s $stime -e $etime --upper-limit 200";
    $exec[] = "DEF:ds0={$rrd_file}:ds0:AVERAGE ";
    #$exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";
    $exec[] = "AREA:ds0#FF99CC:\"Sessions \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%2.0lf \\n\" ";
    
    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end

  function drawSessPng2($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }

    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -h $h -w $w -s $stime -e $etime --upper-limit 200";
    $exec[] = "DEF:ds0a={$rrd_file}:ds0:AVERAGE ";
    $exec[] = "CDEF:ds0=60,ds0a,* ";
    #$exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";
    $exec[] = "AREA:ds0#FF99CC:\"Sessions \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%2.0lf \\n\" ";

    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end

  function drawSessPng3($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval,$bg_color='FFFF0F'){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }

    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -h $h -w $w -s $stime -e $etime --upper-limit 200 --color BACK#$bg_color ";
    $exec[] = "DEF:ds0a={$rrd_file}:ds0:AVERAGE ";
    $exec[] = "CDEF:ds0=60,ds0a,* ";
    #$exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";
    $exec[] = "AREA:ds0#FF99CC:\"Sessions \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%2.0lf \\n\" ";

    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end


  function drawSessPngAll($rrd_files, $png_file, $title, $h, $w, $interval=0, $start_time=0, $end_time=0){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $current_time = time();
    if ($interval == 0){
      $stime = $start_time;
      $etime = $end_time;
    }else{
      $stime = $current_time - $interval;
      $etime = $current_time;
    }

    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -h $h -w $w -s $stime -e $etime --upper-limit 200";
    $count = 0;

    foreach($rrd_files as $rrd_file){
      $count++;
      $exec[] = "DEF:tmp{$count}={$rrd_file}:ds0:AVERAGE ";
      if (!isset($tmp_CDEF)){ //init 
        $tmp_CDEF = "CDEF:all=tmp{$count}";
      }else{
        $tmp_CDEF = $tmp_CDEF.",tmp{$count},+";
      }//if
    }//foreach
    
     

    $exec[] = "$tmp_CDEF ";
    $exec[] = "CDEF:ds0=60,all,* ";
    #$exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";
    $exec[] = "AREA:ds0#FF99CC:\"Sessions \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%2.0lf \\n\" ";

    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time\\t\\t\\t\\t\\t Powered by HiNet-IDC 系統組\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end




  function drawCiscoCPUPng($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }

    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -h $h -w $w -s $stime -e $etime --upper-limit 15";
    $exec[] = "DEF:ds0={$rrd_file}:ds0:AVERAGE ";
    $exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";

    $exec[] = "AREA:ds0#FF99CC:\"Current \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%2.0lf \\n\" ";
    $exec[] = "LINE1:ds1#FF0000:\"  5mins \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds1:MAX:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds1:AVERAGE:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds1:LAST:\"%2.0lf \\n\" ";
    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end


  function drawLinuxCpuPng1($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }

    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -h $h -w $w -s $stime -e $etime --upper-limit 15";
    $exec[] = "DEF:ds0a={$rrd_file}:ds0:AVERAGE ";
    $exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";
    $exec[] = "DEF:ds2={$rrd_file}:ds2:AVERAGE ";

    $exec[] = "CDEF:ds0=100,ds0a,- ";
    $exec[] = "AREA:ds0#FF99CC:\"  Busy \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%2.0lf \\n\" ";
    $exec[] = "LINE1:ds1#FF0000:\"System \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds1:MAX:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds1:AVERAGE:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds1:LAST:\"%2.0lf \\n\" ";
    $exec[] = "LINE1:ds2#0000FF:\"  User \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds2:MAX:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds2:AVERAGE:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds2:LAST:\"%2.0lf \\n\" ";


    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end


  function drawLinuxLoadPng($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }

    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -v Load -h $h -w $w -s $stime -e $etime --upper-limit 10";
    $exec[] = "DEF:ds0={$rrd_file}:ds0:AVERAGE ";
    $exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";
    $exec[] = "DEF:ds2={$rrd_file}:ds2:AVERAGE ";

    $exec[] = "AREA:ds0#FFB7DD:\"  1 Min \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%2.2lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%2.2lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%2.2lf \\n\" ";
    $exec[] = "LINE1:ds1#FF0000:\"  5 Min \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds1:MAX:\"%2.2lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds1:AVERAGE:\"%2.2lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds1:LAST:\"%2.2lf \\n\" ";
/*
    $exec[] = "LINE1:ds2#FF00FF:\" 15 Min \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds2:MAX:\"%2.2lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds2:AVERAGE:\"%2.2lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds2:LAST:\"%2.2lf \\n\" ";
*/

    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end




  function drawLinuxMemoryPng1($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }

    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -h $h -w $w -s $stime -e $etime --upper-limit 99 ";
    $exec[] = "DEF:ds0={$rrd_file}:ds0:AVERAGE ";
    $exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";
    $exec[] = "DEF:ds2={$rrd_file}:ds2:AVERAGE ";

#    $exec[] = "CDEF:ds0=100,ds0a,- ";
    $exec[] = "AREA:ds1#66FFFF:\"Physical \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds1:MAX:\"%2.0lf %% \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds1:AVERAGE:\"%2.0lf %% \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds1:LAST:\"%2.0lf %% \\n\" ";
    $exec[] = "LINE1:ds0#3366FF:\"Buffered \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%2.0lf %% \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%2.0lf %% \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%2.0lf %% \\n\" ";
    $exec[] = "LINE1:ds2#FF0000:\"    SWAP \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds2:MAX:\"%2.0lf %% \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds2:AVERAGE:\"%2.0lf %% \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds2:LAST:\"%2.0lf %% \\n\" ";

    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end

  function drawLinuxMemoryPng2($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }

    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -v Memory -h $h -w $w -s $stime -e $etime --upper-limit 100 --lower-limit 0 ";
    $exec[] = "DEF:ds0={$rrd_file}:ds0:AVERAGE ";
    $exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";
    $exec[] = "DEF:ds2={$rrd_file}:ds2:AVERAGE ";
    $exec[] = "DEF:ds3={$rrd_file}:ds3:AVERAGE ";

    $exec[] = "AREA:ds2#CCFF33:\"Cached \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds2:MAX:\"%4.1lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds2:AVERAGE:\"%4.1lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds2:LAST:\"%4.1lf \\n\" ";
    $exec[] = "STACK:ds1#99FFFF:\"Buffer \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds1:MAX:\"%4.1lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds1:AVERAGE:\"%4.1lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds1:LAST:\"%4.1lf \\n\" ";
    $exec[] = "STACK:ds3#FF00FF:\"  SWAP \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds3:MAX:\"%4.1lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds3:AVERAGE:\"%4.1lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds3:LAST:\"%4.1lf \\n\" ";
    $exec[] = "LINE1:ds0#FF0000:\"  Used \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%4.1lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%4.1lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%4.1lf \\n\" ";


    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end




  function drawLinuxDiskIOPng1($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }

    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -v IOPS -h $h -w $w -s $stime -e $etime --upper-limit 1600000 -r ";
    $exec[] = "DEF:ds0={$rrd_file}:ds0:AVERAGE ";
    $exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";

#    $exec[] = "CDEF:ds0=100,ds0a,- ";
#    $exec[] = "AREA:ds1#66FFCC:\"Write \"";
    $exec[] = "AREA:ds1#FF0000:\"Write \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds1:MAX:\"%5.0lf %s\" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds1:AVERAGE:\"%5.0lf %s\" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds1:LAST:\"%5.0lf %s\\n\" ";
    $exec[] = "LINE1:ds0#3366FF:\" Read \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%5.0lf %s\" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%5.0lf %s\" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%5.0lf %s\\n\" ";
    $exec[] = "COMMENT:\" \\n\" ";

    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end





  function drawAlteonBanIpPng1($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }

    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -h $h -w $w -s $stime -e $etime --upper-limit 1000";
    $exec[] = "DEF:ds0={$rrd_file}:ds0:AVERAGE ";
    #$exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";
    $exec[] = "AREA:ds0#FF99CC:\"AlteonBannedIP \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%2.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%2.0lf \\n\" ";
    

    $exec[]= "COMMENT:\"\\n\"";
    $exec[]= "COMMENT:\"\\n\"";
    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end


function drawBWPng_multi($rrdfiles, $pngfile, $title, $h, $w, $stime=0, $interval){
  global $rrd_bin;
  $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }
  $string_stime = date("Y-m-d H\\\:i\\\:s", $stime);
  $string_etime = date("Y-m-d H\\\:i\\\:s", $etime);
  $exec[]= "$rrd_bin graph $pngfile --title '$title' --imgformat PNG -h $h -w $w -v Network -s $stime -e $etime --color BACK#FFCCFF ";

  $i = 0;
  foreach ($rrdfiles as $rrdfile){
    $exec[]= "DEF:InOctets_$i={$rrdfile}:ds1:AVERAGE ";
    $exec[]= "DEF:OutOctets_$i={$rrdfile}:ds0:AVERAGE ";
    $i++;
  }//end for


  $i = 0;
  foreach ($rrdfiles as $rrdfile){
    if(!isset($InOctets)){
      $InOctets = "InOctets_$i";
      $OutOctets = "OutOctets_$i";
    }else{
      $InOctets = "$InOctets,InOctets_$i,+";
      $OutOctets = "$OutOctets,OutOctets_$i,+";
    }
    $i++;
  }//foreach

  $exec[]= "CDEF:InOctets=$InOctets ";
  $exec[]= "CDEF:OutOctets=$OutOctets ";
  $exec[]= "CDEF:preIn=PREV\(InOctets\) ";
  $exec[]= "CDEF:preOut=PREV\(OutOctets\) ";
  $exec[]= "CDEF:preIn1=PREV\(preIn\) ";
  $exec[]= "CDEF:preOut1=PREV\(preOut\) ";
  $exec[]= "CDEF:ifInOctets=InOctets,10000000000,GT,preIn1,InOctets,IF ";
  $exec[]= "CDEF:ifOutOctets=OutOctets,10000000000,GT,preOut1,OutOctets,IF ";
  $exec[]= "CDEF:ifInAvg1=InOctets,8,* ";
  $exec[]= "CDEF:ifOutAvg1=OutOctets,8,* ";
  $exec[]= "CDEF:ifInAvg=InOctets,1000,/,8,* ";
  $exec[]= "CDEF:ifOutAvg=OutOctets,1000,/,8,* ";
  $exec[]= "AREA:ifOutAvg1#00ff00:\" IN\" ";
  $exec[]= "COMMENT:\"Max\:\" GPRINT:ifOutAvg1:MAX:\"%6.1lf %sb/s\" ";
  $exec[]= "COMMENT:\"Avg\:\" GPRINT:ifOutAvg1:AVERAGE:\"%6.1lf %sb/s\" ";
  $exec[]= "COMMENT:\"Last\:\" GPRINT:ifOutAvg1:LAST:\"%6.1lf %sb/s\\n\" ";
  $exec[]= "LINE1:ifInAvg1#0000ff:\"OUT\" ";
  $exec[]= "COMMENT:\"Max\:\" GPRINT:ifInAvg1:MAX:\"%6.1lf %sb/s\" ";
  $exec[]= "COMMENT:\"Avg\:\" GPRINT:ifInAvg1:AVERAGE:\"%6.1lf %sb/s\" ";
  $exec[]= "COMMENT:\"Last\:\" GPRINT:ifInAvg1:LAST:\"%6.1lf %sb/s\\n\" ";
  $exec[]= "COMMENT:\"Interval\: $string_stime - $string_etime\"";
  $exec[]= "COMMENT:\"Last Update\: $create_time by jihhong@cht.com.tw\"";
  $exec[]= "\n";

  $exec_all = "";
  foreach($exec as $line){
   $exec_all = "$exec_all $line";
  }

  #echo $exec_all;
  exec($exec_all);
}



  function drawBannedPng1($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }

    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -h $h -w $w -s $stime -e $etime --upper-limit 100";
    $exec[] = "DEF:ds0={$rrd_file}:ds0:AVERAGE ";
    $exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";
    $exec[] = "DEF:ds2={$rrd_file}:ds2:AVERAGE ";
    $exec[] = "AREA:ds0#FF99CC:\"        Banned IP\"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%6.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%6.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%6.0lf \\n\" ";

    $exec[] = "LINE1:ds2#FF0000:\"   SYN_RECV state\"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds2:MAX:\"%6.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds2:AVERAGE:\"%6.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds2:LAST:\"%6.0lf \\n\" ";

    $exec[] = "LINE1:ds1#0000FF:\"ESTABLISHED state\"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds1:MAX:\"%6.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds1:AVERAGE:\"%6.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds1:LAST:\"%6.0lf \\n\" ";




    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    #echo "$exec_all<br>";
    exec($exec_all);
  }#function end


  function drawAlteonCPUPng1($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }
    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -h $h -w $w -s $stime -e $etime --upper-limit 15";
    $exec[] = "DEF:ds0={$rrd_file}:ds0:AVERAGE ";
    $exec[] = "DEF:ds1={$rrd_file}:ds1:AVERAGE ";
    $exec[] = "DEF:ds2={$rrd_file}:ds2:AVERAGE ";
    $exec[] = "DEF:ds3={$rrd_file}:ds3:AVERAGE ";
    $exec[] = "DEF:ds4={$rrd_file}:ds4:AVERAGE ";
    $exec[] = "AREA:ds0#999999:\"mpCpuUtil   \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%4.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%4.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%4.0lf \\n\" ";

    $exec[] = "LINE1:ds1#3399FF:\"spCpuUtil01 \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds1:MAX:\"%4.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds1:AVERAGE:\"%4.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds1:LAST:\"%4.0lf \\n\" ";

    $exec[] = "LINE1:ds2#669900:\"spCpuUtil02 \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds2:MAX:\"%4.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds2:AVERAGE:\"%4.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds2:LAST:\"%4.0lf \\n\" ";

    $exec[] = "LINE1:ds3#FF3399:\"spCpuUtil03 \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds3:MAX:\"%4.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds3:AVERAGE:\"%4.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds3:LAST:\"%4.0lf \\n\" ";

    $exec[] = "LINE1:ds4#0000FF:\"spCpuUtil04 \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds4:MAX:\"%4.0lf \" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds4:AVERAGE:\"%4.0lf \" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds4:LAST:\"%4.0lf \\n\" ";

    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end



function drawBWPng($rrdfile, $pngfile, $title, $h, $w, $stime=0, $interval, $ulimit, $bg_color='FFFFFF'){
  global $rrd_bin;
  #$create_time=date("r");
  #$MIN = date("i");
  #$SEC = date("s");
  #$create_time = date("Y-m-d H");
  #$create_time = "$create_time\\:$MIN\\:$SEC";
  $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }
  $string_stime = date("Y-m-d H\\\:i\\\:s", $stime);
  $string_etime = date("Y-m-d H\\\:i\\\:s", $etime);

  $exec[]= "$rrd_bin graph $pngfile --title '$title' --imgformat PNG -h $h -w $w -v Network -s $stime -e $etime -u $ulimit --color BACK#$bg_color  ";
  $exec[]= "DEF:InOctets={$rrdfile}:ds1:AVERAGE ";
  $exec[]= "DEF:OutOctets={$rrdfile}:ds0:AVERAGE ";
  $exec[]= "CDEF:preIn=PREV\(InOctets\) ";
  $exec[]= "CDEF:preOut=PREV\(OutOctets\) ";
  $exec[]= "CDEF:preIn1=PREV\(preIn\) ";
  $exec[]= "CDEF:preOut1=PREV\(preOut\) ";
  $exec[]= "CDEF:ifInOctets=InOctets,10000000000,GT,preIn1,InOctets,IF ";
  $exec[]= "CDEF:ifOutOctets=OutOctets,10000000000,GT,preOut1,OutOctets,IF ";
  $exec[]= "CDEF:ifInAvg1=InOctets,8,* ";
  $exec[]= "CDEF:ifOutAvg1=OutOctets,8,* ";
  $exec[]= "CDEF:ifInAvg=InOctets,1000,/,8,* ";
  $exec[]= "CDEF:ifOutAvg=OutOctets,1000,/,8,* ";
  $exec[]= "AREA:ifOutAvg1#00ff00:\" IN\" ";
  $exec[]= "COMMENT:\"Max\:\" GPRINT:ifOutAvg1:MAX:\"%6.1lf %sb/s\" ";
  $exec[]= "COMMENT:\"Avg\:\" GPRINT:ifOutAvg1:AVERAGE:\"%6.1lf %sb/s\" ";
  $exec[]= "COMMENT:\"Last\:\" GPRINT:ifOutAvg1:LAST:\"%6.1lf %sb/s\\n\" ";
  $exec[]= "LINE1:ifInAvg1#0000ff:\"OUT\" ";
  $exec[]= "COMMENT:\"Max\:\" GPRINT:ifInAvg1:MAX:\"%6.1lf %sb/s\" ";
  $exec[]= "COMMENT:\"Avg\:\" GPRINT:ifInAvg1:AVERAGE:\"%6.1lf %sb/s\" ";
  $exec[]= "COMMENT:\"Last\:\" GPRINT:ifInAvg1:LAST:\"%6.1lf %sb/s\\n\" ";
  $exec[]= "COMMENT:\"Interval\: $string_stime ~ $string_etime\"";
  $exec[]= "COMMENT:\"Create by jihhong@cht.com.tw\"";
  $exec[]= "\n";

  $exec_all = "";
  foreach($exec as $line){
   $exec_all = "$exec_all $line";
  }
  exec($exec_all);
}


  function drawArduinoTempHum($rrd_file,$png_file,$title,$h,$w,$stime=0,$interval){
    global $rrd_bin;
    global $rrd_db_dir;
    $create_time = date("Y-m-d H\\\:i\\\:s");
    $now = time();
    if ($stime == 0){
      $stime = $now - $interval;
      $etime = $now;
    }else{
      $etime = $stime + $interval;
    }

    #$exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -v Value -h $h -w $w -s $stime -e $etime --upper-limit 90 --lower-limit 0 -r ";
    $exec[] = "$rrd_bin graph $png_file --title '$title' --imgformat PNG -v Value -h $h -w $w -s $stime -e $etime --lower-limit 10 -r ";
    $exec[] = "DEF:ds1={$rrd_file}:temperature:AVERAGE ";
    $exec[] = "DEF:ds0={$rrd_file}:humidity:AVERAGE ";

#    $exec[] = "CDEF:ds0=100,ds0a,- ";
#    $exec[] = "AREA:ds1#66FFCC:\"Write \"";
    $exec[] = "AREA:ds1#FF0000:\"Temperature \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds1:MAX:\"%5.0lf %s\" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds1:AVERAGE:\"%5.0lf %s\" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds1:LAST:\"%5.0lf %s\\n\" ";
    $exec[] = "LINE1:ds0#3366FF:\"   Humidity \"";
    $exec[]= "COMMENT:\"Max\:\" GPRINT:ds0:MAX:\"%5.0lf %s\" ";
    $exec[]= "COMMENT:\"Avg\:\" GPRINT:ds0:AVERAGE:\"%5.0lf %s\" ";
    $exec[]= "COMMENT:\"Last\:\" GPRINT:ds0:LAST:\"%5.0lf %s\\n\" ";
    $exec[] = "COMMENT:\" \\n\" ";

    #$exec[] = "LINE1:ds0#FF0000:\"current \"";
    $exec[] = "COMMENT:\"$create_time by jihhong@cht.com.tw\"";
    $exec[] = "\n";

    $exec_all = "";
    foreach ($exec as $line){
      $exec_all = "$exec_all $line";
    } #foreach
    exec($exec_all);
  }#function end



function mysnmpget($ip,$community,$snmp_version,$oid){
  $exec="snmpget -O nqv -c $community -v $snmp_version $ip $oid";
  exec($exec,$output);
  return trim($output[0]);
}

function sms($PHONE,$message){

  error_reporting (E_ALL);
  $user_acc  = "80008089";
  $user_pwd  = "pucl402";
  $mobile_number= "$PHONE";
  //$message= "簡訊內容";

  $interface_type=0; /* interface_type: 0 ( 1 to 255 保留) */
  $msg_type=0;     /* 0:檢查帳號密碼 1:傳送簡訊 2:查詢傳送結果 */
  $send_type=100;          /* 100:即時傳送 , 101:預約傳送*/
  $ret_msg_len=129;  /* Socket 接收 Ret_Msg 的長度為129 */

  /* Socket to Air Server IP ,Port */
  $address = '203.66.172.131';
  $service_port = 8000;

  /* Create a TCP/IP socket. */
  $socket = socket_create (AF_INET, SOCK_STREAM, 0);
  if ($socket < 0) {
      echo "socket_create() failed: reason: " . socket_strerror ($socket) . "<br>\n";
  }

  echo "Attempting to connect to '$address' on port '$service_port'... <br>\n";
  $result = socket_connect ($socket, $address, $service_port);
  if ($result < 0) {
      echo "socket_connect() failed.\nReason: ($result) " . socket_strerror($result) . "<br>\n";
  } else {
      echo "Connection OK.<br>\n";
  }

  echo "<p>";

  /* 帳號密碼檢查 */
  $msg_content=$user_acc . "\0" . $user_pwd . "\0";
  $in = pack("C",$interface_type) . pack("C",$msg_type) . pack("C",strlen($msg_content)) . $msg_content;

  $out = '';

  echo "帳號密碼檢查 : ";
  socket_send ($socket, $in, strlen($in), 0);

  $out_len = socket_recv ($socket, $out , $ret_msg_len, 0);
  $ret_C = substr($out, 0, 1);     /* 取出 ret_code */
  $ret_code_array = unpack("C", $ret_C); /* 將$ret_C 轉成unsigned char , unpack 會return array*/
  $ret_code = array_pop ($ret_code_array); /* 從array 中pop出ret_code值 */

  if($ret_code==0){ /* ret_code ==0 , ID/Passwd check OK!*/
     echo "帳號密碼檢查成功! <p>\n";

     /* Start Send Message */
     $msg_type=1; /* 傳送簡訊 */
     $msg_content=$mobile_number . "\0" . $message . "\0";
     $in = pack("C",$interface_type) . pack("C",$msg_type) . pack("C",strlen($msg_content)) . $msg_content . pack("C",$send_type);

     echo "傳送文字簡訊:";
     socket_send ($socket, $in, strlen($in), 0);
     $out_len = socket_recv ($socket, $out, $ret_msg_len, 0);
     $ret_C = substr($out, 0, 1); /* 取出 ret_code */
     $ret_code_array = unpack("C", $ret_C); /* 將$ret_C 轉成unsigned char , unpack 會return array*/
     $ret_code = array_pop ($ret_code_array); /* 從array 中pop出ret_code值 */
     $ret_description_len = strlen($out) - 1; /* 扣掉ret_code的一個長度 */
     $ret_description = substr($out, 1, $ret_description_len); /* 取得回傳的內容*/

     if($ret_code==0){
        echo "簡訊傳送成功!\n";
        echo "ret_code=" . $ret_code . ", MessageID=" . $ret_description;
     }else{
        echo "簡訊傳送失敗!\n";
        echo "ret_code=" . $ret_code . ", ret_description=" . $ret_description;
     }

  }else {
     echo "帳號密碼檢查失敗! \n";
  }


  echo "<p>";
  echo "Closing socket...";
  socket_close ($socket);
  echo "OK.\n\n";
}


?>
