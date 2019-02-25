#!/bin/bash
step=1

for((i=0;i<60;i=(i+step)));
do
    #订单超时未支付
    curl http://47.104.65.26/api/task/storeOrder
    #自动确认收货    
    curl http://47.104.65.26/api/task/ordeAutoConfirma
    #用户券超时返还
    curl http://47.104.65.26/api/task/userVoucherAutoCancel
    sleep $step
done

exit 0
