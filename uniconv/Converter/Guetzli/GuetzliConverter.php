<?php

declare(strict_types=1);

namespace Uniconv\Converter\Guetzli;

use Uniconv\ConverterInterface;

class GuetzliConverter implements ConverterInterface
{
    public function __construct(private ?int $quality = null)
    {
    }

    public function convertCommand(string $sourceFile, string $targetFile):? string
    {
        $quality = (!empty($this->quality)) ? ' --quality '.$this->quality.' ' : '';
        return "/opt/google/guetzli/bin/Release/guetzli $quality $sourceFile $targetFile";
    }

    public function dockerFile(): ?string
    {
        return file_get_contents(__DIR__ . '/Dockerfile');
    }

    public static function allowConstructParametersFromRequest(): bool
    {
        return true;
    }
}
