<?php

declare(strict_types=1);

namespace Uniconv\Converter;

use Uniconv\ConverterInterface;

class FFMpegConverter implements ConverterInterface
{

    public function convertCommand(string $sourceFile, string $targetFile): ?string
    {
        return "ffmpeg -i $sourceFile $targetFile";
    }

    public function dockerFile(): ?string
    {
        return file_get_contents(__DIR__ . '/FFMpeg.Dockerfile');
    }

    public static function allowConstructParametersFromRequest(): bool
    {
        return false;
    }
}
