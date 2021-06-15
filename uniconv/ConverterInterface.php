<?php

declare(strict_types=1);

namespace Uniconv;

interface ConverterInterface
{
    public function convertCommand(string $sourceFile, string $targetFile): ?string;

    public function dockerFile(): ?string;

    public static function allowConstructParametersFromRequest(): bool;
}
