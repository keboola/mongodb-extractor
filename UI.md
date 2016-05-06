# Configuring MongoDB Extractor in Keboola Connection

Sample:

```json
{
    "db": {
        "host": "127.0.0.1",
        "port": 27017,
        "database": "test",
        "user": "username",
        "password": "password"
    },
    "exports": [
        {
            "name": "bronx-bakeries",
            "collection": "restaurants",
            "mapping": {
                "_id.$oid": {
                    "type": "column",
                    "mapping": {
                        "destination": "id",
                        "primaryKey": true
                    }
                },
                "name": {
                    "type": "column",
                    "mapping": {
                    "destination": "name"
                    }
                }
            }
        },
        {
            "name": "bronx-bakeries-westchester",
            "collection": "restaurants",
            "query": "{borough: \"Bronx\", \"address.street\": \"Westchester Avenue\"}",
            "mapping": {
                "_id.$oid": {
                    "type": "column",
                    "mapping": {
                        "destination": "id",
                        "primaryKey": true
                    }
                },
                "name": {
                    "type": "column",
                    "mapping": {
                    "destination": "name"
                    }
                }
            }
        }
    ]
}
```

Options description:

- `db`: *array*
    - `host`: *string* host ot connect to
    - `port`: *integer* port to use, usually 27017 for MongoDB
    - `database`: *string* database to select
    - `user`: *string* (optional) username
    - `password`: *string* (optional) password
    - `ssh`: *array* (optional), in most cases configured through UI

- `exports`: *array*
    - `name`: *string* export name, generated CSV file will be named after this
    - `collection`: *string* collection to export
    - `query`: *MongoDB Extended JSON* (optional) query to filter by
    - `sort`: *MongoDB Extended JSON* (optional) fields to sort by
    - `limit`: *integer* (optional) limit results
    - `incremental`: *boolean* (optional) incremental load of data, default `false`

Explanation:

1. First export fetches whole `restaurants` collection and produces CSV file `bronx-bakeries.csv`
with columns `id` and `name` where `id` is marked as primary key.
2. Second export filters data in collection with specified `query` and produces CSV
`bronx-bakeries-westchester.csv` file with columns `name` and 3 `address` fields.

## Tips

- Check `mongoexport` command documentation to learn more about:
    - `query`: [--query](https://docs.mongodb.org/v3.2/reference/program/mongoexport/#cmdoption--query)
    - `sort`: [--sort](https://docs.mongodb.org/v3.2/reference/program/mongoexport/#cmdoption--sort)
    - or [MongoDB Extended JSON](https://docs.mongodb.org/v3.2/reference/mongodb-extended-json/)
