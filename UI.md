# Configuring MongoDB Extractor in Keboola Connection

Sample:

```json
{
    "db": {
        "host": "127.0.0.1",
        "port": 27017,
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
                "public": "ssh-rsa ...your public key ...",
                "private": "-----BEGIN RSA PRIVATE KEY-----\nMIIEpAIBAA... your private key with newline characters ... lodS0y8w==\n-----END RSA PRIVATE KEY-----"
            }
        }
    },
    "exports": [
        {
            "name": "bronx-bakeries",
            "db": "test",
            "collection": "restaurants",
            "fields": [
                "_id",
                "name"
            ],
            "primaryKey": [
                "_id"
            ]
        },
        {
            "name": "bronx-bakeries-westchester",
            "db": "test",
            "collection": "restaurants",
            "query": "{borough: \"Bronx\", \"address.street\": \"Westchester Avenue\"}",
            "fields": [
                "name",
                "address.zipcode",
                "address.street",
                "address.building"
            ]
        }
    ]
}
```

Explanation:

- `db`: *array*
    - `host`: *string*
    - `port`: *integer*
    - `user`: *string*
    - `password`: *string*

- `exports`: *array*
    - `name`: *string* export name, generated CSV file will be named after this
    - `db`: *string* database to select
    - `collection`: *string* collection to export
    - `query`: *JSON* (optional) filter collection data
    - `fields`: *array* fields to export
    - `sort`: *JSON* (optional) JSON with fields to sort by
    - `limit`: *integer* (optional) limit results
    - `incremental`: *boolean* (optional) incremental load of data, default `true`
    - `primaryKey`: *array* (optional) primary keys

- First export fetches whole `restaurants` collection and produces CSV file `bronx-bakeries.csv`
with columns `_id` and `name`
- Second export filters data in collection with specified `query` and produces CSV
`bronx-bakeries-westchester.csv` file with columns `name` and 3 address fields

## Notes

- Most export parameters are derived from [`mongoexport` command](https://docs.mongodb.org/v3.2/reference/program/mongoexport/).
- Description of SSH section can be found on [README page](https://github.com/keboola/mongodb-extractor#configuration)
