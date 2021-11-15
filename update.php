<?php
  require_once '/usr/share/simple-rrd/config.inc.php';
  #抓取/stat/slb/group 1的real server session
/*
  #$exec = "snmpwalk -m ALL -O qn -c $alteon3408_community -v $alteon3408_snmpv  $alteon3408_ip .1.3.6.1.4.1.1872.2.5.4.2.2.1.2";
  $exec = "snmpwalk -m ALL -O qn -c $alteon3408_community -v $alteon3408_snmpv  $alteon3408_ip .1.3.6.1.4.1.1872.2.5.4.2.2.1.3";
  #echo "$exec\n";
  exec($exec,$output);
  
  #update Sessions
  foreach ($output as $line){
    #$row = str_replace('.1.3.6.1.4.1.1872.2.5.4.2.2.1.2.','',$line);
    $row = str_replace('.1.3.6.1.4.1.1872.2.5.4.2.2.1.3.','',$line);
    $row = split(" ",$row);
    #print_r($row);
    $rrd_file = str_pad($row[0],2,"0",STR_PAD_LEFT);
    $value1 = $row[1];
    #echo "$value1\n";

    $db_file = "{$rrd_db_dir}rs{$rrd_file}.rrd";
    #如果檔案不存在則產生rrd db file
    if(!file_exists($db_file)){
       echo "creating $db_file  .....";
       #$exec = "cp {$rrd_db_dir}default/default-rs.rrd $db_file";
       $exec = "$rrd_bin create $db_file --step 60 DS:ds0:COUNTER:120:U:U RRA:AVERAGE:0.5:1:525600  RRA:MAX:0.5:5:1";
       exec($exec);
       echo "done\n";
    }
   
    $exec = "$rrd_bin update $db_file N:{$value1}";
    #echo "$exec\n";
    exec($exec);
  }

  #update CPU
  #mpx1 mpCpuStatsUtil64Seconds.0
  #spx4 spStatsCpuUtil64Seconds 
  $exec = "snmpwalk -m ALL -O qv -c $alteon3408_community -v $alteon3408_snmpv $alteon3408_ip .1.3.6.1.4.1.1872.2.5.1.2.2.3.0";
  exec($exec,$value_mp64);

  $exec = "snmpwalk -m ALL -O qv -c $alteon3408_community -v $alteon3408_snmpv $alteon3408_ip .1.3.6.1.4.1.1872.2.5.1.2.4.1.1.4";  
  exec($exec,$t_value);
 
  $db_file = "{$rrd_db_dir}alteonCPU.rrd"; 
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

 */ 


#20120501 new slb session 
# function update_slb($name, $ip, $community, $snmp_version, $rs_array)

foreach($alteon_slb as $slb){
  $name = $slb[0];
  $ip = $slb[1];
  $community = $slb[2];
  $snmp_version = $slb[3];
  $rs_array = $slb[4];
  update_slb($name, $ip, $community, $snmp_version, $rs_array);
}



  #update Linux CPU

  #unset($mrtg_cfg);
  #$mrtg_cfg[] = "WorkDir: {$rrd_root_dir}db\n";
  #$mrtg_cfg[] = "LogFormat: rrdtool\n";
  #$mrtg_cfg[] = "PathAdd: /usr/bin\n";
  #$mrtg_cfg[] = "Logdir: {$rrd_root_dir}db\n";

  foreach($proxy_server as $server){
    $db_file = "{$rrd_db_dir}{$server[0]}-cpu.rrd";
    if(!file_exists($db_file)){
       echo "creating $db_file  .....";
       #$exec = "cp {$rrd_db_dir}default/default-linux-cpu-3p.rrd $db_file";
       $exec = "$rrd_bin create $db_file --step 60 DS:ds0:GAUGE:120:U:U DS:ds1:GAUGE:120:U:U DS:ds2:GAUGE:120:U:U RRA:AVERAGE:0.5:1:525600  RRA:MAX:0.5:5:1";
       exec($exec);
       echo "done\n";
    }#if
    $dbf_name = "{$server[0]}";
    $tmp_ip = $server[2];
    $tmp_community = $server[3];
    $tmp_snmp_version = $server[4];
    
    $cpu_idle = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'ssCpuIdle.0');
    $cpu_system = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'ssCpuSystem.0');
    $cpu_user = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'ssCpuUser.0');
    
    $exec = "$rrd_bin update $db_file N:$cpu_idle:$cpu_system:$cpu_user";
    exec($exec);
    #echo $exec."\n";

    # 20100523 .1.3.6.1.4.1.2021.10.1.3.1 UCD-SNMP-MIB::laLoad.1
    $db_file = "{$rrd_db_dir}{$server[0]}-load.rrd";
    if(!file_exists($db_file)){
       echo "creating $db_file  .....";
       #$exec = "cp {$rrd_db_dir}default/default-linux-cpu-3p.rrd $db_file";
       $exec = "$rrd_bin create $db_file --step 60 DS:ds0:GAUGE:120:0:600 DS:ds1:GAUGE:120:0:600 DS:ds2:GAUGE:120:0:600 RRA:AVERAGE:0.5:1:525600  RRA:MAX:0.5:5:1";
       exec($exec);
       echo "done\n";
    }#if
    $dbf_name = "{$server[0]}";
    $tmp_ip = $server[2];
    $tmp_community = $server[3];
    $tmp_snmp_version = $server[4];

    $load_1m = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'laLoad.1');
    $load_5m = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'laLoad.2');
    $load_15m = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'laLoad.3');

    $exec = "$rrd_bin update $db_file N:$load_1m:$load_5m:$load_15m";
    exec($exec);



    ################################################################
    $db_file = "{$rrd_db_dir}{$server[0]}-memory.rrd";
    if(!file_exists($db_file)){
       echo "creating $db_file  .....";
       $exec = "$rrd_bin create $db_file --step 60 DS:ds0:GAUGE:120:U:U DS:ds1:GAUGE:120:U:U DS:ds2:GAUGE:120:U:U DS:ds3:GAUGE:120:U:U RRA:AVERAGE:0.5:1:525600  RRA:MAX:0.5:5:1";
       exec($exec);
       echo "done\n";
    }#if

/*
    $buffer_hrStorageSize = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'hrStorageSize.1');
    $buffer_hrStorageUsed = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'hrStorageUsed.1');
    $memory_hrStorageSize = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'hrStorageSize.2');
    $memory_hrStorageUsed = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'hrStorageUsed.2');
    $swap_hrStorageSize = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'hrStorageSize.3');
    $swap_hrStorageUsed = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'hrStorageUsed.3');
    
    $buffer = round(($buffer_hrStorageUsed/$buffer_hrStorageSize)*100,2);
    #echo "$memory_hrStorageUsed $memory_hrStorageSize\n";
    $memory = round(($memory_hrStorageUsed/$memory_hrStorageSize)*100,2);
    $swap = round(($swap_hrStorageUsed/$swap_hrStorageSize)*100,2);

*/
    $total = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'memTotalReal.0');
    $available = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'memAvailReal.0');
    $p_used = (($total - $available) / $total) * 100;

    $buffer = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'memBuffer.0');
    $p_buffer = ($buffer/$total) * 100;

    $cached = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'memCached.0');
    $p_cached = ($cached/$total) * 100;

    $total_swap = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'memTotalSwap.0');
    $available_swap = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'memAvailSwap.0');
    $swap_used = $total_swap - $available_swap;
    $p_swap_used = ($swap_used/$total) * 100;

    $exec = "$rrd_bin update $db_file N:$p_used:$p_buffer:$p_cached:$p_swap_used";
    #echo "$exec\n";
    exec($exec);

    
    #monitor diskIO
    if (is_array($server[5])){
      foreach($server[5] as $disk_index => $disk_key){
        $db_file = "{$rrd_db_dir}{$server[0]}-disk_io-{$disk_index}.rrd";
        if(!file_exists($db_file)){
          echo "creating $db_file  .....";
          $exec = "$rrd_bin create $db_file --step 60 DS:ds0:COUNTER:120:U:U DS:ds1:COUNTER:120:U:U RRA:AVERAGE:0.5:1:525600  RRA:MAX:0.5:5:1";
          exec($exec);
          echo "done\n";
        }#if
        $diskIONReadX = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,"diskIONReadX.$disk_index");
        $diskIONWrittenX = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,"diskIONWrittenX.$disk_index");
        $exec = "$rrd_bin update $db_file N:$diskIONReadX:$diskIONWrittenX";
        exec($exec);
      } //for
    } //if

  }#foreach  

  foreach ($bw_rrd as $row){
    #echo "$row[0]\n";
    $db_file = "{$rrd_db_dir}{$row[0]}.rrd";
    if(!file_exists($db_file)){
       echo "creating bandwidth $db_file  .....";
#       $exec = "cp {$rrd_db_dir}default/default-bw-2.rrd $db_file";
       $exec = "$rrd_bin create $db_file --step 60 DS:ds0:COUNTER:120:U:U DS:ds1:COUNTER:120:U:U RRA:AVERAGE:0.5:1:525600  RRA:MAX:0.5:5:1";
       #$exec = "$rrd_bin create $db_file --step 300 DS:ds0:COUNTER:600:U:U DS:ds1:COUNTER:600:U:U RRA:AVERAGE:0.5:1:315360";
       exec($exec);
       echo "done\n";
    }#if
    $dbf_name = "{$row[1]}";
    $tmp_ip = $row[2];
    $tmp_community = $row[3];
    $tmp_snmp_version = $row[4];

    if ($tmp_snmp_version == 1){
      $InOID = "ifInOctets.{$row[5]}";
      $OutOID = "ifOutOctets.{$row[5]}";
    }else{
      $InOID = "ifHCInOctets.{$row[5]}";
      $OutOID = "ifHCOutOctets.{$row[5]}";
    }

    $ifInOctets = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,$InOID);
    $ifOutOctets = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,$OutOID);

    $exec = "$rrd_bin update $db_file N:$ifInOctets:$ifOutOctets";
    #echo $exec."\n";
    exec($exec);
  }#foreach

  foreach ($cisco_switch as $row){
    $db_file = "{$rrd_db_dir}{$row[0]}-cpu.rrd";
    if(!file_exists($db_file)){
       echo "creating $db_file  .....";
       $exec = "$rrd_bin create $db_file --step 60 DS:ds0:GAUGE:120:U:U DS:ds1:GAUGE:120:U:U RRA:AVERAGE:0.5:1:525600  RRA:MAX:0.5:5:1";
       #$exec = "$rrd_bin create $db_file --step 300 DS:ds0:COUNTER:600:U:U DS:ds1:COUNTER:600:U:U RRA:AVERAGE:0.5:1:315360";
       exec($exec);
       echo "done\n";
    }#if
    $tmp_ip = $row[2];
    $tmp_community = $row[3];
    $tmp_snmp_version = $row[4];

    $cpu_cur = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'.1.3.6.1.4.1.9.2.1.56.0');
    $cpu_5mins = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'.1.3.6.1.4.1.9.2.1.58.0');

    $exec = "$rrd_bin update $db_file N:$cpu_cur:$cpu_5mins";    
    exec($exec);

  }




  foreach ($fortinet_fw as $row){
    $db_file = "{$rrd_db_dir}{$row[0]}-session.rrd";
    if(!file_exists($db_file)){
       echo "creating $db_file  .....";
       $exec = "$rrd_bin create $db_file --step 60 DS:ds0:GAUGE:120:U:U RRA:AVERAGE:0.5:1:525600  RRA:MAX:0.5:5:1";
       #$exec = "$rrd_bin create $db_file --step 300 DS:ds0:COUNTER:600:U:U DS:ds1:COUNTER:600:U:U RRA:AVERAGE:0.5:1:315360";
       exec($exec);
       echo "done\n";
    }#if
    $tmp_ip = $row[2];
    $tmp_community = $row[3];
    $tmp_snmp_version = $row[4];

    $session_cur = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,' .1.3.6.1.4.1.12356.1.10.0');
    #$cpu_5mins = mysnmpget($tmp_ip,$tmp_community,$tmp_snmp_version,'.1.3.6.1.4.1.9.2.1.58.0');

    $exec = "$rrd_bin update $db_file N:$session_cur";
    exec($exec);

  }

  $db_file = "{$rrd_db_dir}temp-test.rrd";
  if (!file_exists($db_file)){
    echo "creating $db_file  .....";
    $exec = "$rrd_bin create $db_file --step 60 DS:temperature:GAUGE:120:-20:100 DS:humidity:GAUGE:120:0:100 RRA:AVERAGE:0.5:1:525600  RRA:MAX:0.5:5:1";
    exec($exec);
    echo "done\n";
  }#if
  
  $tt = get_temperature();
  $temperature = $tt[1];
  $humidity = $tt[0];
  $exec = "$rrd_bin update $db_file N:$temperature:$humidity";
  exec($exec);

?>
