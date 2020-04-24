#!/bin/bash  
step=10 #间隔的秒数，不能大于60  
  
for (( i = 0; i < 600000; i=(i+step) )); do  
    git pull  
    sleep $step  
done  
  
exit 0  
