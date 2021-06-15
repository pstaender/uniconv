# Uniconv client

The client requires a running [uniconv webserver](https://github.com/pstaender/uniconv).

## Install

    $ npm install -g uniconv

## Usage

Displays help:

```sh
$ uniconv
```

Setup your access token and Base URL:

```sh
$ export UNICONV_ACCESSTOKEN=my_secret_access_token
$ export UNICONV_BASEURL='http://whatever.my.server.is.called'
```

Convert a mkv file to flac:

```sh
$ uniconv flac /Users/philipp/Downloads/music.mkv
uniconv

🗳	11:15:39 PM: queued
⚙️	11:15:47 PM: processing
📦	11:15:49 PM: done
🦄	11:15:50 PM: Downloaded to -> /Users/philipp/Downloads/music.flac
```
