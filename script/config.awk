##
#
function sumall(a,b){
  return a+b;
}
BEGIN{
  #取得squidlog
  s_time = systime();
}
/test1.com.tw/ {
  x=$9+0;
  if(x!=0)
    bw_count[1] += x;
}

/test2.com.tw/{
  x=$9+0;
  if(x!=0){
    bw_count[2] += x;
  }
}


END{
  test1_bw = (bw_count[1]/1000000)*8;
  test2_bw = (bw_count[2]/1000000)*8;
  bw_all = sumall(test1_bw,test2_bw);
  #print "test1: " test1_bw"Mb";
  #print "test1: " test2_bw"Mb";
  now = strftime();
  printf("report generated at %s\n",now);
  printf("test1 usage: %10.2fMb\n",test1_bw);
  printf(" test2 usage: %10.2fMb\n",test2_bw);
  printf("          all: %10.2fMb\n",bw_all);
  e_time = systime();
  time_used = e_time - s_time;
  printf("time used: %iseconds\n",time_used);
  #sys_cmd = "rrdtool update ./db/squid-test2.rrd N:" test2_bw;
  #print sys_cmd;
  #update rrdtool database
  cmd1 = "rrdtool updatev /usr/share/monitor/db/squid-test2.rrd N:$test2_bw";
  cmd1 | getline test2_rrd;
  "rrdtool updatev /usr/share/monitor/db/squid-test1.rrd N:$test1_bw" | getline test1_rrd;
  print cmd1
  print test1_bw;
  print test2_rrd;
  print test1_rrd;
}

