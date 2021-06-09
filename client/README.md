# Uniconv client

## Install

  $ npm install -g uniconv

## Usage

Displays help:

  $ uniconv

Convert file from jpg to pdf:

  $ uniconv pdf ~/your.jpg

Don't forget to define accesstoken and base url as environment variable before calling `uniconv`:

  export CONVERTER_ACCESSTOKEN=youraccesstoken
  export CONVERTER_BASEURL=https://yourserver
