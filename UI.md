# Configuring MongoDb Extractor in Keboola Connection

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
                "name"
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

- Description of SSH section can be found on [README page](https://github.com/keboola/mongodb-extractor#configuration)
- First export fetches whole `restaurants` collection and produces CSV file `bronx-bakeries.csv`
with one column `name`
- Second export filters data in collection with specified `query` and produces CSV
`bronx-bakeries-westchester.csv` file with columns `name` and 3 address fields

## Output Sample

Sample CSV from first export configuration above, named `bronx-bakeries.csv`:

| name |
| --- |
| `Mom'S Bakery` |
| `Enrico'S Pastry Shop & Caffe` |
| ... |

## Notes

- Most export parameters are derived from [`mongoexport` command](https://docs.mongodb.org/v2.4/reference/program/mongoexport/).
