<?php
require_once "config.inc.php";

echo "Check Memory SNMP index:\n";
foreach ($proxy_server as $server){
  $cmd = "snmpwalk -c {$server[3]} -v {$server[4]} {$server[2]} hrStorageType.{$server[6]} ";
  exec($cmd,$output);
}

print_r($output);
unset($output);


echo "Check diskIO SNMP index:\n";
foreach ($proxy_server as $server){
  $cmd = "snmpwalk -c {$server[3]} -v {$server[4]} {$server[2]} diskIODevice.{$server[5]} ";
  exec($cmd,$output);
}
print_r($output);
unset($output);

?>
