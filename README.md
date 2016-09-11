# MongoDB Extractor

[![Build Status](https://travis-ci.org/keboola/mongodb-extractor.svg?branch=master)](https://travis-ci.org/keboola/mongodb-extractor)
[![Code Climate](https://codeclimate.com/github/keboola/mongodb-extractor/badges/gpa.svg)](https://codeclimate.com/github/keboola/mongodb-extractor)
[![Test Coverage](https://codeclimate.com/github/keboola/mongodb-extractor/badges/coverage.svg)](https://codeclimate.com/github/keboola/mongodb-extractor/coverage)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/keboola/mongodb-extractor/blob/master/LICENSE.md)

Docker application for exporting data from MongoDB. Basically, it's a simple wrapper of `mongoexport`
command, which exports data from specified database and collection. Then those data are processed by
[php-csvmap](https://github.com/keboola/php-csvmap).

**[For documentation about configuring in Keboola Connection follow this link](https://help.keboola.com/extractors/mongodb/).**

## Configuration

```json
{
  "parameters": {
    "db": {
      "host": "127.0.0.1",
      "port": 27017,
      "database": "test",
      "user": "username",
      "password": "password",
      "ssh": {
        "enabled": true,
        "sshHost": "mongodb",
        "sshPort": 22,
        "user": "root",
        "localPort": 27017,
        "remoteHost": "127.0.0.1",
        "remotePort": 27017,
        "keys": {
          "public": "ssh-rsa ...your public key...",
          "private": "-----BEGIN RSA PRIVATE KEY-----\n...your private key...\n-----END RSA PRIVATE KEY-----\n"
        }
      }
    },
    "exports": [
      {
        "name": "bronx-bakeries-westchester",
        "collection": "restaurants",
        "query": "{borough: \"Bronx\", \"address.street\": \"Westchester Avenue\"}",
        "incremental": true,
        "mapping": {
          "_id.$oid": {
            "type": "column",
            "mapping": {
              "destination": "id",
              "primaryKey": true
            }
          },
          "name": "name",
          "address": {
            "type": "table",
            "destination": "bakeries-coords",
            "parentKey": {
              "destination": "bakeries_id"
            },
            "tableMapping": {
              "coord.0": "w",
              "coord.1": "n",
              "zipcode": {
                "type": "column",
                "mapping": {
                  "destination": "zipcode",
                  "primaryKey": true
                }
              },
              "street": "street"
            }
          }
        }
      }
    ]
  }
}
```
For more information about SSH tunnel creation see [`createSshTunnel` function](https://github.com/keboola/db-extractor-common/blob/8e66dc9/src/Keboola/DbExtractor/Extractor/Extractor.php#L47)

## Output

After successful extraction there are several CSV files, which contains exported data. First output
file is named after `name` parameter in export configuration. Other files are named after destination
parameter in mapping section.

Also, there is manifest file for each of the export.

## Development

Requirements:

- Docker Engine `~1.10.0`
- Docker Compose `~1.6.0`

Application is prepared for run in container, you can start development same way:

1. Clone this repository: `git clone git@github.com:keboola/mongodb-extractor.git`
2. Change directory: `cd mongodb-extractor`
3. Build services: `docker-compose build`
4. Run tests `docker-compose run --rm php-tests` (runs `./tests.sh` script)

After seeing all tests green, continue:

1. Create data dir: `mkdir -p data`
2. Follow configuration sample and create `config.yml` file and place it to your data directory (`data/config.yml`):
3. Run service: `docker-compose run --rm php` (starts container with `bash`)
4. Simulate real run: `php src/run.php --data=./data`

### Tests

In running container execute `tests.sh` script which contains `phpunit` and related commands:

```console
./tests.sh
```

## License

MIT. See license file.
