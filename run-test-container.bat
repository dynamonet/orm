@echo off 
set CAMPAIGN_ID=15641 
set DATADIR=d:/data
docker run -it -v c:/www/dynamo-orm:/usr/src/orm -w /usr/src/orm --network="host" dynamonet/push-starter /bin/bash
pause
