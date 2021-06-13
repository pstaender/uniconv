<?php

namespace Converter;

class MkvToFlac extends FFMpegConverter
{
    public function convertCommand(string $sourceFile, string $targetFile): ?string
    {
        $wavFile = '/tmp/convert/audio.wav';
        $cmd = [
            "mkdir -p /tmp/convert",
            "ffmpeg -i $sourceFile $wavFile",
            "ffmpeg -i $wavFile $targetFile",
        ];
        return implode("\n", $cmd);
    }
}
