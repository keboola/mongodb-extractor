#!/bin/bash

docker login -u="$QUAY_USERNAME" -p="$QUAY_PASSWORD" quay.io
docker tag keboola/mongodb-extractor quay.io/keboola/mongodb-extractor:$TRAVIS_TAG
docker images
docker push quay.io/keboola/mongodb-extractor:$TRAVIS_TAG
