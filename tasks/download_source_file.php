<?php

require(__DIR__.'/../config/config.php');

[
    'accesstoken' => $accesstoken,
    'baseUrl' => $baseUrl,
    'targetDir' => $targetDir,
    'job' => $jobData,
] = \App\TaskHelper::apiExchangeParameters();

$cmd = "curl -H 'Accept-Charset: utf-8' -H 'Authorization: Bearer $accesstoken' $baseUrl/exchange/".$jobData['file']['filepath']." > $targetDir/".basename($jobData['file']['filepath']);

echo $cmd;
echo shell_exec($cmd);
