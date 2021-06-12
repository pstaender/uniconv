<?php

declare(strict_types=1);

namespace App;

final class TaskHelper
{

    public static function baseUrl(): string
    {
        return !empty(getenv('API_BASE_URL')) ? getenv('API_BASE_URL') : 'http://localhost:8080';
    }
    public static function internalApiAccessToken(): string
    {
        return array_keys(\App\config()['admins']['by_accesstoken'])[0];
    }

    public static function conversionDir(): string
    {
        $targetDir = !empty(getenv('TARGET_DIR')) ? getenv('TARGET_DIR') : __DIR__.'/../tempConversionFolder';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, recursive: true);
        }
        return realpath($targetDir);
    }

    public static function apiExchangeParameters()
    {
        global $argv;
        $jobFile = $argv[1] ?? null;

        if (empty($jobFile)) {
            throw new Exception('1st argument has to be the job file');
        }
        $jobFile = realpath($jobFile);

        $jobData = json_decode(file_get_contents($jobFile), true);
        if (!$jobData) {
            throw new Exception("Could not read job data $jobFile");
        }

        $targetDir = self::conversionDir();

        $accesstoken = self::internalApiAccessToken();
        $baseUrl = self::baseUrl();

        return [
            'accesstoken' => $accesstoken,
            'baseUrl' => $baseUrl,
            'targetDir' => $targetDir,
            'job' => $jobData,
        ];
    }
}
