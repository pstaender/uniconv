# Uniconv 🦄

A universal converter that converts everything that can be run inside a docker container

The service converts a file to other formats… as long as they are defined. You can define formats via PHP classes and Docker definitions.

### Example implementations

- ffmpeg
  - mkv -> mp3
  - mp4 -> mp3
- tesseract
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

### Build

```sh
  $ composer install
  $ cd client && npm install # optional
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

### Service setup

Run server:

```sh
  $ composer run start
```

Run job worker (as never terminating service):

```sh
  $ bash ./convert_jobs_daemon.sh
```
