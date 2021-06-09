<?php

declare(strict_types=1);

namespace App;
class Converter
{
    public static function commands(string $fileDir, string $from, string $to, \Converter\ConverterInterface $converter)
    {
        $dockerImageName = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($converter::class));
        $tmpDir = sys_get_temp_dir() . "/{$from}_{$to}_".time();
        $scriptFileFolder = $tmpDir . '/script';
        mkdir($scriptFileFolder, recursive: true);
        $dockerFileName = $tmpDir . '/Dockerfile_' . $dockerImageName;
        file_put_contents($dockerFileName, $converter->dockerFile());
        $source = "/convertfiles/source.$from";
        $target = "/convertfiles/target.$to";
        $baseDir = realpath(__DIR__ . '/..');
        $logs = " > $baseDir/$fileDir/stdout.log 2> $baseDir/$fileDir/stderr.log";

        $scriptContent = "#!/bin/sh\n".$converter->convertCommand($source, $target);
        file_put_contents($scriptFileFolder.'/script.sh', $scriptContent);

        $commands = [
            "docker build -t $dockerImageName - < $dockerFileName",
            "docker run -it -v '$baseDir/$fileDir:/convertfiles/' -v '$scriptFileFolder/:/convertscript/' $dockerImageName sh /convertscript/script.sh" . $logs,
        ];

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
