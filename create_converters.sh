#!/bin/bash

for ext in FFMpeg Tesseract Webp; do
  composer run "create${ext}Converters"
done