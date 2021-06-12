<?php

declare(strict_types=1);

namespace App;

class Converter
{
    public static function commands(string $fileDir, string $from, string $to, \Converter\ConverterInterface $converter)
    {
        $dockerImageName = 'uniconv_' . preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($converter::class));

        $workerIsRunningInsideDocker = file_exists(__DIR__ . '/../.running_inside_docker');

        if ($workerIsRunningInsideDocker) {
            $tmpDir = realpath(__DIR__ . '/../') . "/docker_process_" . time();
        } else {
            $tmpDir = sys_get_temp_dir() . "/docker_process_" . time();
        }
        mkdir($tmpDir . '/', recursive: true);

        $scriptFileFolder = $tmpDir . '/script';
        mkdir($scriptFileFolder, recursive: true);

        $sourceFile = "source.$from";
        $targetFile = "target.$to";


        $outputFolder = '/convertfiles';
        $source = "$outputFolder/$sourceFile";
        $target = "$outputFolder/$targetFile";

        $scriptContent = "#!/bin/sh\n" . $converter->convertCommand($source, $target);
        $scriptFile = $scriptFileFolder . '/script.sh';
        file_put_contents($scriptFile, $scriptContent);

        $absoluteFileDir = realpath(__DIR__ . '/../' . $fileDir);

        $logs = " > $absoluteFileDir/stdout.log 2> $absoluteFileDir/stderr.log";

        if (!$workerIsRunningInsideDocker) {
            // worker is running directly on the server
            // write logs directly to the output folder

            $dockerFileName = $tmpDir . '/Dockerfile.' . $dockerImageName;
            file_put_contents($dockerFileName, $converter->dockerFile());

            $commands = [
                "docker build -t $dockerImageName - < $dockerFileName",
                "docker run -t -v '$absoluteFileDir/:/convertfiles/' -v '$scriptFileFolder/:/convertscript/' $dockerImageName sh /convertscript/script.sh" . $logs,
            ];
        } else {
            // worker is running inside a docker container
            // write logs to local tmp dir
            // $logs = " > $tmpDir/stdout.log 2> $tmpDir/stderr.log";
            $dockerFileName = "$absoluteFileDir/Dockerfile.$dockerImageName";
            $dockerFileContent = $converter->dockerFile();
            $targetDir = '/convertfiles';
            $jobFile = "$targetDir/job.json";

            $appendFileOperation = [
                "ENV TARGET_DIR=$targetDir",
                "ENV SCRIPT_DIR=/convertscript",
                "RUN mkdir \$TARGET_DIR",
                "RUN mkdir \$SCRIPT_DIR",
                "COPY $scriptFile \$SCRIPT_DIR/script.sh",
                "RUN cat \$SCRIPT_DIR/script.sh",
                "RUN php tasks/download_job_file.php \$JOB_USER \$JOB_ID \$TARGET_DIR/job.json",
                "RUN php tasks/download_source_file.php \$TARGET_DIR/job.json",
                "RUN sh \$SCRIPT_DIR/script.sh",
                "RUN php tasks/upload_target_file.php $jobFile $targetDir/$targetFile",
                // php tasks/upload_target_file.php jobs/philipp.7e34fb4462a147ae387235ab641a08c4d30ed566.json tempConversionFolder/test.flac
            ];
            $dockerFileContent .= implode("\n", $appendFileOperation);

            file_put_contents($dockerFileName, $dockerFileContent);

            echo $dockerFileContent;

            $commands = [
                // "cp $absoluteFileDir/$sourceFile $tmpDir/$sourceFile",
                // "cp $scriptFile $tmpDir/script.sh",
                // "ls -al $tmpDir/",
//                "ls -al $absoluteFileDir/",
//                "cat $dockerFileName",
                "docker build -t $dockerImageName - < $dockerFileName",
//                "docker run -t -v '$tmpDir/:/convertfiles/' $dockerImageName ls -al /",
//                "docker run -t -v '$tmpDir/:/convertfiles/' $dockerImageName ls -al /convertfiles/",
                "docker run -t $dockerImageName " . $logs,
//                "rm $absoluteFileDir/script.sh",
//                "cp $tmpDir/* /convertfiles/",
//                "rm -rf $tmpDir",
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
                    $paramValue = $controller->param($param->getName(), (string)$param->getType() ?? null);
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
        $baseNamespaces = [
            '\\Converter\\',
        ];
        $baseNamespaces = array_merge(config()['converterNamespaces'], $baseNamespaces);
        foreach ($baseNamespaces as $baseNamespace) {
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
