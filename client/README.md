# Uniconv client

## Install

    $ npm install -g uniconv

## Usage

Displays help:

```
$ uniconv
```

Convert a mkv file to flac:

```sh
$ uniconv flac /Users/philipp/Downloads/music.mkv
uniconv v0.0.3

🗳	11:15:39 PM: queued
⚙️	11:15:47 PM: processing
📦	11:15:49 PM: done
🦄	11:15:50 PM: Downloaded to -> /Users/philipp/Downloads/music.flac
```

Don't forget to define accesstoken and base url as environment variable before calling `uniconv`:

    export CONVERTER_ACCESSTOKEN=youraccesstoken
    export CONVERTER_BASEURL=https://yourserver
