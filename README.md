# Uniconv 🦄

A universal converter that converts everything that can be run inside a docker container

The service converts a file to other formats… as long as they are defined. You can define formats via PHP classes and Docker definitions.

### Supported Conversions

- via ffmpeg
  - mp4 -> mp3
  - mkv -> mp3
  - mkv -> wav
  - mkv -> flac
  - mp3 -> flac
  - wav -> flac
- via tesseract
  - jpg -> pdf

### Features

- every conversion process runs isolated in a separated docker container
- source and target format conversion are defined in a class and can be therefore extended with custom converters, e.g.:
  - mp4 to mp3 will look for a class called `Mp4ToMp3` having a `ConverterInterface` (see converter/ConverterInterface.php)
- no database required (is that a feature?)

### Requirements

- PHP v8
- Docker
- optional: nodejs (for the terminal client)

### Setup

```sh
  $ git clone git@github.com:pstaender/uniconv.git
  $ cd uniconv
  $ composer install
```

### Define your accesstoken(s)

By default, a simple accesstoken lookup is used.

Create a `config/config.local.yml` file which has the following data:

```yaml
users:
  by_accesstoken:
    933704aabab8314e7cd2385428591eda737fecec:
      email: apiuser
```

You can now use the accestoken in the header like: `Authorization: Bearer 933704aabab8314e7cd2385428591eda737fecec`

### Uniconv FTW

You can use directly the restful api of the conversion service (open api specs will follow soon).

But the easiest way for now is to install and use the uniconv cli tool (see https://github.com/pstaender/uniconv/tree/main/client). The usage is pretty straightforward:

```sh
$ uniconv flac /Users/philipp/Downloads/music.mkv
uniconv

🗳	11:15:39 PM: queued
⚙️	11:15:47 PM: processing
📦	11:15:49 PM: done
🦄	11:15:50 PM: Downloaded to -> /Users/philipp/Downloads/music.flac
```

### TODO

  * open api specs (inluding generating class constructor)
  * make delete file after download to optout instead of optin
  * change to POST /convert/FROM/TO
  * handle multipe files input and output (via tar or zip?)

### Start service and job worker

Run server:

```sh
  $ composer run start
```

Run job worker (as never terminating service):

```sh
  $ bash ./convert_jobs_daemon.sh
```
