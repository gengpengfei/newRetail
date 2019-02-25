#!/bin/bash

    #店铺报表
    curl http://47.104.65.26/api/task/storeReport
    #店铺人气    
    curl http://47.104.65.26/api/task/storeDailyTask
    #店铺账单
    curl http://47.104.65.26/api/task/storeBill

exit 0
