@echo off 
set CAMPAIGN_ID=15641 
set DATADIR=d:/data
docker run -it -v c:/www/dynamo-orm:/usr/src/orm -w /usr/src/orm --network="host" php/orm:7.2 /bin/bash
pause
