#!/bin/bash

set -e

DEVPORTAL_VENDOR_ID="keboola"
DEVPORTAL_APP_ID="keboola.ex-mongodb"

docker pull quay.io/keboola/developer-portal-cli-v2:latest

export REPOSITORY=`docker run --rm \
  -e KBC_DEVELOPERPORTAL_USERNAME \
  -e KBC_DEVELOPERPORTAL_PASSWORD \
  quay.io/keboola/developer-portal-cli-v2:latest ecr:get-repository $DEVPORTAL_VENDOR_ID $DEVPORTAL_APP_ID`

docker tag keboola/mongodb-extractor $REPOSITORY:$TRAVIS_TAG
docker tag keboola/mongodb-extractor $REPOSITORY:latest
docker images

eval $(docker run --rm \
  -e KBC_DEVELOPERPORTAL_USERNAME \
  -e KBC_DEVELOPERPORTAL_PASSWORD \
  quay.io/keboola/developer-portal-cli-v2:latest ecr:get-login $DEVPORTAL_VENDOR_ID $DEVPORTAL_APP_ID)

docker push $REPOSITORY:$TRAVIS_TAG
docker push $REPOSITORY:latest

docker run --rm \
  -e KBC_DEVELOPERPORTAL_USERNAME \
  -e KBC_DEVELOPERPORTAL_PASSWORD \
  quay.io/keboola/developer-portal-cli-v2:latest update-app-repository $DEVPORTAL_VENDOR_ID $DEVPORTAL_APP_ID $TRAVIS_TAG
