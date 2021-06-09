<?php

namespace Converter;

class Mp4ToMp3 extends FFMpegConverter
{
  public function convertCommand(string $sourceFile, string $targetFile): ?string
  {
    $quality = '-b:a 192K';
    $cmd = "ffmpeg -i $sourceFile $quality -vn $targetFile";
    return $cmd;
  }
}
