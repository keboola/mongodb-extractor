#!/bin/bash

docker login -e="." -u="$QUAY_USERNAME" -p="$QUAY_PASSWORD" quay.io
docker tag keboola/mongodb-extractor quay.io/keboola/mongodb-extractor:latest
docker images
docker push quay.io/keboola/mongodb-extractor:latest
