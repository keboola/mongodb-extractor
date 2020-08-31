# MongoDB Extractor

[![Build Status](https://travis-ci.org/keboola/mongodb-extractor.svg?branch=master)](https://travis-ci.org/keboola/mongodb-extractor)
[![Code Climate](https://codeclimate.com/github/keboola/mongodb-extractor/badges/gpa.svg)](https://codeclimate.com/github/keboola/mongodb-extractor)
[![Test Coverage](https://codeclimate.com/github/keboola/mongodb-extractor/badges/coverage.svg)](https://codeclimate.com/github/keboola/mongodb-extractor/coverage)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/keboola/mongodb-extractor/blob/master/LICENSE.md)

Docker application for exporting data from MongoDB. Basically, it's a simple wrapper of `mongoexport`
command, which exports data from specified database and collection. Then those data are processed by
[php-csvmap](https://github.com/keboola/php-csvmap).

**[For documentation about configuring in Keboola Connection follow this link](https://help.keboola.com/extractors/database/mongodb/).**

## Configuration

The configuration `config.json` contains following properties in `parameters` key: 
- `db` - object (required): Configuration of the connection.
    - `protocol` - string (optional): One of `mongodb` (default), `mongodb+srv` or `custom_uri`.
    - **Additional parameters if `protocol` = `custom_uri`**:
        - `uri` - string:  
          - [MongoDB Connection String](https://docs.mongodb.com/manual/reference/connection-string/)
          - Eg. `mongodb://user@localhost,localhost:27018,localhost:27019/db?replicaSet=test&ssl=true`.
          - The password must not be a part of URI. It must be encrypted in `#password` item.
        - `#password` - string (required): Password for user specified in `uri`.
    - **Additional parameters if `protocol` = `mongodb` or `mongodb+srv`**:
        - `host` - string (required): 
            - If `protocol` = `mongodb`, then value is hostname of MongoDB server.
            - If `protocol` = `mongodb+srv`, then value is [DNS Seedlist Connection Format](https://docs.mongodb.com/manual/reference/connection-string/#dns-seedlist-connection-format). 
        - `port` - string (optional): Server port (default port is `27017`).
        - `database` - string (required):  Database to connect to.
        - `authenticationDatabase` - string (optional): [Authentication database](https://docs.mongodb.com/manual/reference/program/mongo/#authentication-options) for `user`.
        - `user` - string (optional): User with correct access rights.
        - `#password` - string (optional): Password for given `user`. Both or none of couple `user` and `#password` must be specified.
        - `ssh` - object (optional): Settings for SSH tunnel.
            - `enabled` - bool (required):  Enables SSH tunnel.
            - `sshHost` - string (required): IP address or hostname of SSH server.
            - `sshPort` - integer (optional): SSH server port (default port is `22`).
            - `localPort` - integer (required): SSH tunnel local port in Docker container (default `33006`).
            - `user` - string (optional): SSH user (default same as `db.user`).
            - `compression`  - bool (optional): Enables SSH tunnel compression (default `false`).
            - `keys` - object (optional): SSH keys.
                - `public` - string (optional): Public SSH key.
                - `#private` - string (optional): Private SSH key.
- `exports` - object[] (required): [Exports configuration](https://help.keboola.com/components/extractors/database/mongodb/#configure-exports).
    - `enabled` - boolean (optional): Default `true`.
    - `id` - scalar (required): Internal `id` of the export.
    - `name` - string (required): Name of the output CSV file.
    - `collection` - string (required): Represents the collection name in your MongoDB database.
    - `query`- string (optional): 
        - JSON string specifying a query which limits documents data in exported data. 
        - Must be specified in a [strict format](https://help.keboola.com/components/extractors/database/mongodb/#strict-format).
    - `incremental` - boolean (optional): Enables [Incremental Loading](https://help.keboola.com/storage/tables/#incremental-loading). Default `false`.
    - `incrementalFetchingColumn` - string (optional): Name of column for [Incremental Fetching](https://help.keboola.com/components/extractors/database/#incremental-fetching)
    - `sort`- string (optional): 
        - JSON string specifying the order of documents in exported data. 
        - Must be specified in a [strict format](https://help.keboola.com/components/extractors/database/mongodb/#strict-format).
    - `limit`- string (optional): Limits the number of exported documents.
    - `mode` - enum (optional)
        - `mapping` (default) - Values are exported using specified `mapping`, [read more](https://help.keboola.com/components/extractors/database/mongodb/#configure-mapping).
        - `raw` - Documents are exported as plain JSON strings, [read more](https://help.keboola.com/components/extractors/database/mongodb/#raw-export-mode).
    - `mapping` - string - required for `mode` = `mapping`, [read more](https://help.keboola.com/components/extractors/database/mongodb/#configure-mapping).
    - `includeParentInPK` - boolean (optional): Default `false`
        - Intended for `mapping` mode and ignored in `raw` mode.
        - If `false`
            - PK of sub-document depends ONLY on sub-document content,
            - ... so same PK is generated for sub-documents with same content, but from different parent document
            - this is legacy/default behaviour
        - If `true`
            - PK of sub-document depends on content AND hash of parent document
            - ... so different PK is generated for sub-documents with same content, but from different parent document
            - this is new behaviour, the UI automatically turns it on for new configs

### Protocol

#### mongodb://

When `parameters.db.protocol` is not defined or is set to `mongodb`, then extractor connects to single MongoDB node.

```json
{
  "parameters": {
    "db": {
      "host": "127.0.0.1",
      "port": 27017,
      "database": "test",
      "user": "username",
      "#password": "password"
    },
    "exports": "..."
  }
}
```

#### mongodb+srv://

When `parameters.db.protocol` = `mongodb+srv`, then extractor connects to 
MongoDB cluster using [DNS Seedlist Connection Format](https://docs.mongodb.com/manual/reference/connection-string/#dns-seedlist-connection-format).

```json
{
  "parameters": {
    "db": {
      "protocol": "mongodb+srv",
      "host": "mongodb.cluster.local",
      "database": "test",
      "user": "username",
      "#password": "password"
    },
    "exports": "..."
  }
}
```

#### Custom URI

When `parameters.db.protocol` = `custom_uri`, then extractor connects to URI defined in `parameters.db.uri`:
 - The password is not a part of URI, but it must be encrypted in `#password` item.
 - `host`, `port`, `database`, `authenticationDatabase` are included in `uri` and must not be defined in separate items.
 - Custom URI cannot be used with SSH tunnel.

```json
{
  "parameters": {
    "db": {
      "protocol": "custom_uri",
      "uri": "mongodb://user@localhost,localhost:27018,localhost:27019/db?replicaSet=test&ssl=true",
      "#password": "password"
    },
    "exports": "..."
  }
}
```

### Example
```json
{
  "parameters": {
    "db": {
      "host": "127.0.0.1",
      "port": 27017,
      "database": "test",
      "user": "username",
      "#password": "password",
      "ssh": {
        "enabled": true,
        "sshHost": "mongodb",
        "sshPort": 22,
        "user": "root",
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

## Output

After successful extraction there are several CSV files, which contains exported data. First output
file is named after `name` parameter in export configuration. Other files are named after destination
parameter in mapping section.

Also, there is manifest file for each of the export.

## Development

Requirements:

- Docker Engine + Docker Compose which support compose v2 file

Application is prepared for run in container, you can start development same way:

1. Clone this repository: `git clone git@github.com:keboola/mongodb-extractor.git`
2. Change directory: `cd mongodb-extractor`
3. Build services: `docker-compose build php`
4. Build services: `docker-compose build php-tests`
5. Run tests `docker-compose run --rm php-tests` (runs `./tests.sh` script)

After seeing all tests green, continue:

1. Create data dir: `mkdir -p data`
2. Follow configuration sample and create `config.json` file and place it to your data directory (`data/config.json`):
3. Run service: `docker-compose run --rm php` (starts container with `bash`)
4. Simulate real run: `php src/run.php --data=./data`

### Tests

In running container (`docker-compose run --rm php`) execute `tests.sh` script which contains `phpunit` and related commands:

```console
./tests.sh
```

## License

MIT. See license file.
