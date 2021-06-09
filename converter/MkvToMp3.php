<?php

namespace Converter;

class MkvToMp3 extends FFMpegConverter
{
  public function convertCommand(string $sourceFile, string $targetFile): ?string
  {
    $quality = '-b:a ' . $this->mp3AudioQuality();
    $cmd = "ffmpeg -i $sourceFile $quality -vn $targetFile";
    return $cmd;
  }
}
