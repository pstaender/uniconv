<?php

require(__DIR__.'/../config/config.php');

$user = $argv[1] ?? null;
$id = $argv[2] ?? null;
$accessToken = \App\TaskHelper::internalApiAccessToken();
$baseUrl = \App\TaskHelper::baseUrl();
$targetDir = \App\TaskHelper::conversionDir();

if (empty($user)) {
    throw new InvalidArgumentException("1st argument hast to be the user(name)");
}

if (empty($id)) {
    throw new InvalidArgumentException("2nd argument hast to be the job id");
}

$cmd = "curl -H 'Accept-Charset: utf-8' -H 'Authorization: Bearer $accessToken' $baseUrl/exchange/jobs/$user/$id > $targetDir/job.json";

echo $cmd;
echo shell_exec($cmd);
