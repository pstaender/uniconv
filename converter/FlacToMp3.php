<?php

namespace Converter;

class FlacToMp3 extends FFMpegConverter
{
  public function convertCommand(string $sourceFile, string $targetFile): ?string
  {
    $quality = '-b:a ' . $this->mp3AudioQuality();
    $cmd = "ffmpeg -i $sourceFile $quality $targetFile";
    return $cmd;
  }
}