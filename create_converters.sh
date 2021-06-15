#!/bin/bash

for ext in FFMpeg Tesseract Webp Guetzli; do
  composer run "create${ext}Converters"
done