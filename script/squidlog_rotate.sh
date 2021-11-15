#!/bin/bash
DATE=`date +%Y%m%d`
ROOT_DIR='/usr/rrdtool/monitor'

#取得squid log file
scp logserver:/var/log/squidlog.1 /tmp/squid-tmplog
awk -f ${ROOT_DIR}/script/config.awk /tmp/squid-tmplog
mv /tmp/squid-tmplog /tmp/squidlog-${DATE}.log
cd /
tar zcvf ${ROOT_DIR}/squidlog/squidlog-${DATE}.tar.gz /tmp/squidlog-${DATE}.log
rm -rf /tmp/squidlog-${DATE}.log

