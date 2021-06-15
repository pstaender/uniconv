<?php

declare(strict_types=1);

namespace Uniconv\Converter\Webp;

use Uniconv\ConverterInterface;

class WebpConverter implements ConverterInterface
{

    public function convertCommand(string $sourceFile, string $targetFile): ?string
    {
        return "cwebp -o $targetFile $sourceFile";
    }

    public function dockerFile(): ?string
    {
        return file_get_contents(__DIR__ . '/Dockerfile');
    }

    public static function allowConstructParametersFromRequest(): bool
    {
        return false;
    }
}
