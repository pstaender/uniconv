#!/bin/bash
echo "Press [CTRL+C] / [CTRL+Z] to stop"
while :
do
  echo -n -e "\n$(date +"%Y-%m-%d %H:%m:%S") convert_jobs.php: ";
  php convert_jobs.php;
	sleep 1;
done
