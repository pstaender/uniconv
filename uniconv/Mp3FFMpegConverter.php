<?php

namespace Uniconv;

class Mp3FFMpegConverter extends FFMpegConverter {
    public function convertCommand(string $sourceFile, string $targetFile): ?string
    {
        $quality = '-b:a ' . $this->mp3AudioQuality();
        return "ffmpeg -i $sourceFile $quality $targetFile";
    }

    protected function mp3AudioQuality()
    {
        return '192k';
    }
}