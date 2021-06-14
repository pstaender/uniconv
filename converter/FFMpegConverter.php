<?php

namespace Converter;

class FFMpegConverter implements ConverterInterface
{

    public function convertCommand(string $sourceFile, string $targetFile): ?string
    {
        $cmd = "ffmpeg -i $sourceFile $targetFile";
        return $cmd;
    }

    public function dockerFile(): ?string
    {
        return file_get_contents(__DIR__ . '/FFMpeg.Dockerfile');
    }

    public static function allowConstructParametersFromRequest(): bool
    {
        return false;
    }

//    protected function mp3AudioQuality()
//    {
//        return '192k';
//    }
}
