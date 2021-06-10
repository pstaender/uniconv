<?php

declare(strict_types=1);

namespace App;
class Converter
{
    public static function commands(string $fileDir, string $from, string $to, \Converter\ConverterInterface $converter)
    {
        $dockerImageName = 'uniconv_' . preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($converter::class));

        $tmpDir = sys_get_temp_dir() . "/docker_process_".time();
        mkdir($tmpDir.'/', recursive: true);

        $dockerFileName = $tmpDir . '/Dockerfile.' . $dockerImageName;
        file_put_contents($dockerFileName, $converter->dockerFile());

        $scriptFileFolder = $tmpDir . '/script';
        mkdir($scriptFileFolder, recursive: true);

        $sourceFile = "source.$from";
        $targetFile = "target.$to";

        $workerIsRunningOnSameEnvironmentAsWebserver = file_exists("$fileDir/$sourceFile");

        // if ($workerIsRunningOnSameEnvironmentAsWebserver) {
        //     $absoluteFileDir = realpath(__DIR__.'/../'.$fileDir);
        //
        //     $source = "$absoluteFileDir/$sourceFile";
        //     $target = "$absoluteFileDir/$targetFile";

        // } else {

        $outputFolder = '/convertfiles';
        $source = "$outputFolder/$sourceFile";
        $target = "$outputFolder/$targetFile";

        $scriptContent = "#!/bin/sh\n".$converter->convertCommand($source, $target);
        $scriptFile = $scriptFileFolder.'/script.sh';
        file_put_contents($scriptFile, $scriptContent);

        if ($workerIsRunningOnSameEnvironmentAsWebserver) {
            // worker is running directly on the server
            $absoluteFileDir = realpath(__DIR__.'/../'.$fileDir);
            // write logs directly to the output folder
            $logs = " > $absoluteFileDir/stdout.log 2> $absoluteFileDir/stderr.log";
            $commands = [
                "docker build -t $dockerImageName - < $dockerFileName",
                "docker run -it -v '$absoluteFileDir/:/convertfiles/' -v '$scriptFileFolder/:/convertscript/' $dockerImageName sh /convertscript/script.sh" . $logs,
            ];
        } else {
            // worker is running inside a docker container
            // write logs to local tmp dir
            $logs = " > $tmpDir/stdout.log 2> $tmpDir/stderr.log";
            $commands = [
                "cp $source $tmpDir/$sourceFile",
                "cp $scriptFile $tmpDir/script.sh",
                "docker build -t $dockerImageName - < $dockerFileName",
                "docker run -it -v '$tmpDir/:/convertfiles/' $dockerImageName sh /convertfiles/script.sh" . $logs,
                "cp rm $tmpDir/script.sh",
                "cp $tmpDir/* /convertfiles/",
                "rm -rf $tmpDir",
            ];
        }

        // cleanup tmp dir
        $commands[] = "rm -rf $tmpDir";

        return $commands;
    }

    public static function createFromRequestParameters(string $fromExtension, string $toExtension, Controller $controller)
    {
        $args = self::createConvertArguments($fromExtension, $toExtension, $controller);
        return self::create($fromExtension, $toExtension, $args);
    }

    public static function createConvertArguments(string $fromExtension, string $toExtension, Controller $controller): array
    {
        $converterClass = self::converterClassName($fromExtension, $toExtension);
        if ($converterClass::allowConstructParametersFromRequest() && method_exists($converterClass, '__construct')) {
            // apply request parameters matching class construction arguments for converter
            $r = new \ReflectionMethod($converterClass, '__construct');

            $params = $r->getParameters();
            $args = [];
            foreach ($params as $param) {
                try {
                    $val = $param->getDefaultValue();
                } catch (\ReflectionException $e) {
                    $val = null;
                }
                if ($param->isOptional()) {
                    $paramValue = $controller->param($param->getName(), (string) $param->getType() ?? null);
                    if ($paramValue !== null) {
                        $val = $paramValue;
                    }
                } else {
                    $val = $controller->requireParam($param->getName(), $param->getType() ?? null);
                }
                $args[] = $val;
            }
            return $args;
        } else {
            return [];
        }
    }

    private static function converterClassName(string $fromExtension, string $toExtension): string
    {
        $converterClass = null;
        $baseNamespaces = [
            '\\Converter\\',
        ];
        $baseNamespaces = array_merge(config()['converterNamespaces'], $baseNamespaces);
        foreach($baseNamespaces as $baseNamespace) {
            $converterClass = implode('', [
                $baseNamespace,
                ucfirst(strtolower($fromExtension)),
                'To',
                ucfirst(strtolower($toExtension)),
            ]);
            if (class_exists($converterClass)) {
                return $converterClass;
            }
        }
        throw new \App\ConverterClassNotFound("No matching converter class found");
    }

    public static function create(string $fromExtension, string $toExtension, array $options = [])
    {
        $converterClass = self::converterClassName($fromExtension, $toExtension);
        if (!empty($options)) {
            $reflection = new \ReflectionClass($converterClass);
            return $reflection->newInstanceArgs($options);
        } else {
            return new $converterClass;
        }
    }

}
