<?php

namespace Uniconv;

use InvalidArgumentException;

class JpgToPdf extends TesseractConverter
{

    public function __construct(private ?string $language = null)
    {
        if ($language && !TesseractConverter::supportedLanguage($language)) {
            throw new InvalidArgumentException("Language $language is not supported by tesseract");
        }
    }

    public function convertCommand(string $sourceFile, string $targetFile): ?string
    {
        $targetFile = preg_replace('/\\.pdf$/i', '', $targetFile);
        $lang = $this->language;
        if (!empty($lang)) {
            $lang = "-l $lang";
        }
        $format = 'pdf';
        return "/usr/bin/tesseract $lang $sourceFile $targetFile $format";
    }

}
