#!/bin/bash

# This script should only be called inside a docker container!
# -> https://devopscube.com/run-docker-in-docker/

sudo chmod 777 /var/run/docker.sock
sh ./convert_jobs/sh
