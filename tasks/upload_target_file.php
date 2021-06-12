<?php

require(__DIR__.'/../config/config.php');

[
    'accesstoken' => $accesstoken,
    'baseUrl' => $baseUrl,
    'targetDir' => $targetDir,
    'job' => $jobData,
] = \App\TaskHelper::apiExchangeParameters();

$file = $argv[2] ?? null;

if (!$file || !file_exists($file)) {
    throw new Exception("2nd argument must be the path to the converted file for uploading");
}

$file = realpath($file);

$cmd = "curl -H 'Accept-Charset: utf-8' -H 'Authorization: Bearer $accesstoken' --form 'file=@$file' $baseUrl/exchange/".dirname($jobData['file']['filepath'])."/target";

echo $cmd;
echo shell_exec($cmd);
