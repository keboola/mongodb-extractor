# Configuring MongoDB Extractor in Keboola Connection

## Sample configuration

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
                "name": "name"
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
                "name": "name"
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
    - `mapping`: *array* of mapping configuration for each column needed in export

Explanation:

1. First fetches whole `restaurants` collection and produces CSV file `bronx-bakeries.csv`
with columns `id` and `name` where `id` is marked as primary key.
2. Second filters data in collection with specified `query` and produces CSV
`bronx-bakeries-westchester.csv` file with same columns as previous one.

## Primary key definition

Since the MongoDB identifies each document in collection uniquely by `_id`, we recommend to set primary
key to this field by defining first item in mapping section:

```json
{
    "_id.$oid": {
        "type": "column",
        "mapping": {
            "destination": "id",
            "primaryKey": true
        }
    }
}
```
*Note: Column with primary key should be named `id` not `_id` to prevent problems with data import.*

## Handling MongoDB data types

To handle MongoDB data types correctly, define mapping similar way as following example (`MongoId`, `ISODate` and `NumberLong`):

```json
{
    "_id.$oid": "id",
    "publishedAt.$date": "publishedAt",
    "views.$numberLong": "views"
}
```

Which is shorthand definition for:

```json
{
    "_id.$oid": {
        "type": "column",
        "mapping": {
            "destination": "id"
        }
    },
    "publishedAt.$date": {
        "type": "column",
        "mapping": {
            "destination": "publishedAt"
        }
    },
    "views.$numberLong": {
        "type": "column",
        "mapping": {
            "destination": "views"
        }
    }
}
```

## Tips

- Check `mongoexport` command documentation to learn more about
[query](https://docs.mongodb.org/v3.2/reference/program/mongoexport/#cmdoption--query),
[sort](https://docs.mongodb.org/v3.2/reference/program/mongoexport/#cmdoption--sort)
or [MongoDB Extended JSON](https://docs.mongodb.org/v3.2/reference/mongodb-extended-json/)
