#!/bin/bash

if $(cat /proc/1/cgroup | grep -q ':/docker/'); then
  # -> https://devopscube.com/run-docker-in-docker/
  sudo chmod 777 /var/run/docker.sock
  sh ./convert_jobs_daemon.sh
else
  echo 'This script should only be called inside a docker container'
  echo 'use ./convert_jobs_daemon.sh instead. Exiting now'
  exit 1;
fi
