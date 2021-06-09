<?php

declare(strict_types=1);

namespace App;
class Converter
{
    public static function commands(string $fileDir, string $from, string $to, \Converter\ConverterInterface $converter)
    {
        $dockerImageName = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($converter::class));
        $dockerFileName = sys_get_temp_dir() . '/Dockerfile_' . $dockerImageName;
        file_put_contents($dockerFileName, $converter->dockerFile());
        $source = "/convertfiles/source.$from";
        $target = "/convertfiles/target.$to";
        $baseDir = realpath(__DIR__ . '/..');
        $logs = " > $baseDir/$fileDir/stdout.log 2> $baseDir/$fileDir/stderr.log";

        $commands = [
            "docker build -t $dockerImageName - < $dockerFileName",
            "docker run -it -v '$baseDir/$fileDir:/convertfiles/' $dockerImageName " . $converter->convertCommand($source, $target) . $logs,
        ];

        return $commands;
    }

    public static function createFromRequestParameters(string $fromExtension, string $toExtension, Controller $controller)
    {
        // $converterClass = self::converterClassName($fromExtension, $toExtension);
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
        $converterClass = implode('', [
            '\\Converter\\',
            ucfirst(strtolower($fromExtension)),
            'To',
            ucfirst(strtolower($toExtension)),
        ]);
        if (!class_exists($converterClass)) {
            throw new \App\ConverterClassNotFound("$converterClass class does not exists");
        }
        return $converterClass;
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
