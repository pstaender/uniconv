<?php

namespace Zeitpulse\DNGConverter;
use \Uniconv\ConverterInterface;

class DNGConverter implements ConverterInterface
{
    public function convertCommand(string $sourceFile, string $targetFile): ?string
    {
        $sourceFile = str_replace('/', '\\\\', $sourceFile);
        $targetFile = basename($targetFile);
        // the sleep 2 prevents a to soon closing docker... just happens in linux
        return "wine /home/wineuser/.wine/drive_c/Program\ Files/Adobe/Adobe\ DNG\ Converter/Adobe\ DNG\ Converter.exe -c -p0 -o $targetFile C:$sourceFile; sleep 3";
    }

    public function dockerFile(): ?string
    {
        return file_get_contents(__DIR__ . '/DNGConverter.Dockerfile');
    }

    public static function allowConstructParametersFromRequest(): bool
    {
        return true;
    }
}
