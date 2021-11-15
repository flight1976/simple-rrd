<?php
require_once '/usr/share/aibank/config.inc.php';

############################################
# 每五分鐘檢查一次 Alteon 3408 之CPU 最大值
# 超過20%即發送告警訊息
#############################################
$send_interval = 1800;


############################################




  $data_dir = "/usr/share/aibank/db";
  $exec = "rrdtool fetch {$data_dir}/alteonCPU.rrd MAX -s -5min";
  exec($exec,$output);
  #print_r($output);
  $data = split("[ ,:]",$output[2]);
  #print_r($data);

  $dd[] = date("r",$data[0]);
  $dd[] = 0 + $data[2];
  $dd[] = 0 + $data[3];
  $dd[] = 0 + $data[4];
  $dd[] = 0 + $data[5];
  $dd[] = 0 + $data[6];


  #print_r($dd);

  # 檢查上次發送簡訊時間 超過 interval 才再發送
  $check_file = '/tmp/check.txt';
  if (!file_exists($check_file)){
    echo "產生檢查檔\n";
    exec("touch $check_file");
  }

  #上次更新時間
  $mtime = filemtime($check_file);
  echo "上次告警時間:". date("r",$mtime) ."\n";
  $now = date('U');

  $interval = $now - $mtime;
  if ($interval < $send_interval){
    $timeout = $send_interval - $interval;
    echo "尚需 $timeout 秒後才可發送\n";
    exit();
  }else{
    echo "開始檢查設備狀態\n";
  }


  # 檢查CPU Loading狀態
  $alarm = false;
  for($i=1;$i<count($dd);$i++){
    #echo "$dd[$i]\n";
    if ($dd[$i] >= 20){
      echo "告警事件成立\n";
      $alarm = true;
    }
  }

  if ($alarm){
    $msg = "Alteon 3408 Alarm Message:\n CPU MP:{$dd[1]}% SP1:{$dd[2]}% SP2:{$dd[3]}% SP3:{$dd[4]}% SP4:{$dd[5]}%";
    echo "$msg \n";
    sms('0912000000',$msg); #flight
    sms('0921000000',$msg); #


    exec("touch $check_file");
  }else{
    echo "系統正常\n";
  }


  


  
?>
