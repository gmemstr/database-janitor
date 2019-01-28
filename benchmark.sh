#!/bin/bash
#
# Benchmark Janitor by running 5 times and collecting results.
COUNTER=0
while [  $COUNTER -lt 5 ]; do
	/usr/bin/time -o benchmark-results.txt -a -f "%E real" ./database-janitor --host=localhost -uroot -ppassword database | gzip -c > janitor-dump.sql.gz
    let COUNTER=COUNTER+1
done

COUNTER=0
while [  $COUNTER -lt 5 ]; do
	/usr/bin/time -o benchmark-results.txt -a -f "%E mysqldump" mysqldump -uroot -ppassword database | gzip -c > mysqldump-dump.sql.gz
    let COUNTER=COUNTER+1
done
