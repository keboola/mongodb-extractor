# WIP - MongoDb Extractor

[![Build Status](https://travis-ci.org/keboola/mongodb-extractor.svg?branch=master)](https://travis-ci.org/keboola/mongodb-extractor)
[![Code Climate](https://codeclimate.com/github/keboola/mongodb-extractor/badges/gpa.svg)](https://codeclimate.com/github/keboola/mongodb-extractor)
[![Test Coverage](https://codeclimate.com/github/keboola/mongodb-extractor/badges/coverage.svg)](https://codeclimate.com/github/keboola/mongodb-extractor/coverage)

Docker application for exporting data from MongoDb. Basically, it's a wrapper of `mongoexport`
command.

## Configuration

This describes configuration from developer point of view. To learn more about configuration in
Keboola Connection please follow [UI page](https://github.com/keboola/mongodb-extractor/blob/master/UI.md).

Example:

```yaml
parameters:
  db:
    host: 127.0.0.1
    port: 27017
    ssh:
      enabled: true
      sshHost: mongodb
      sshPort: 22
      user: root
      localPort: 27017
      remoteHost: 127.0.0.1
      remotePort: 27017
      keys:
        public: ssh-rsa ...your public key...
        private: |
          -----BEGIN RSA PRIVATE KEY-----
          ...your private key...
          -----END RSA PRIVATE KEY-----
  exports:
    - name: bronx-bakeries
      db: 'test'
      collection: 'restaurants'
      query: '{borough: "Bronx"}'
      fields:
        - name
    - name: bronx-bakeries-westchester
      db: 'test'
      collection: 'restaurants'
      query: '{borough: "Bronx", "address.street": "Westchester Avenue"}'
      fields:
        - name
        - address.zipcode
        - address.street
        - address.building
```
For more information about SSH tunnel creation see [`createSshTunnel` function](https://github.com/keboola/db-extractor-common/blob/8e66dc9/src/Keboola/DbExtractor/Extractor/Extractor.php#L47)

## Output

After successful extraction there are several CSV files, which contains exported data.

Each output file is named after `name` parameter in export configuration.

Sample CSV from first export configuration above, named `bronx-bakeries.csv`:

| name |
| --- |
| `Mom'S Bakery` |
| `Enrico'S Pastry Shop & Caffe` |
| ... |

## Development

### Requirements

- Docker Engine `~1.10.0`
- Docker Compose `~1.6.0`

### Start

Application is prepared for run in container, you can start development same way.

1. Clone this repository: `git clone git@github.com:keboola/mongodb-extractor.git`
2. Change directory: `cd mongodb-extractor`
3. Build services: `docker-compose build`
4. Create data dir: `mkdir -p data`
5. Follow configuration sample and create `config.yml` file. Then place it to your data directory (e.g. `data/config.yml`):
6. Run service: `docker-compose run --rm php bash`
7. Run entrypoint command: `php src/run.php --data=/data`

### Tests

Environment is already prepared for running tests.

#### Inside

In running container execute `tests.sh` script which contains `phpunit` and related commands:

```bash
./tests.sh
```

#### Outside

Using `docker-compose`

```bash
docker-compose run --rm php-tests
```

## License

MIT. See license file.
