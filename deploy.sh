#!/bin/bash

DP_VENDOR_ID='keboola'
DP_APP_ID='keboola.ex-mongodb'

docker pull quay.io/keboola/developer-portal-cli-v2:latest \
&& export REPOSITORY=`docker run --rm \
  -e KBC_DEVELOPERPORTAL_USERNAME=$DP_USERNAME \
  -e KBC_DEVELOPERPORTAL_PASSWORD=$DP_PASSWORD \
  -e KBC_DEVELOPERPORTAL_URL=$DP_URL \
  quay.io/keboola/developer-portal-cli-v2:latest ecr:get-repository $DP_VENDOR_ID $DP_APP_ID` \
&& docker tag keboola/mongodb-extractor $REPOSITORY:$TRAVIS_TAG \
&& docker tag keboola/mongodb-extractor $REPOSITORY:latest \
&& docker images \
&& eval $(docker run --rm \
  -e KBC_DEVELOPERPORTAL_USERNAME=$DP_USERNAME \
  -e KBC_DEVELOPERPORTAL_PASSWORD=$DP_PASSWORD \
  -e KBC_DEVELOPERPORTAL_URL=$DP_URL \
  quay.io/keboola/developer-portal-cli-v2:latest ecr:get-login $DP_VENDOR_ID $DP_APP_ID) \
&& docker push $REPOSITORY:$TRAVIS_TAG \
&& docker push $REPOSITORY:latest \
&& docker run --rm \
  -e KBC_DEVELOPERPORTAL_USERNAME=$DP_USERNAME \
  -e KBC_DEVELOPERPORTAL_PASSWORD=$DP_PASSWORD \
  -e KBC_DEVELOPERPORTAL_URL=$DP_URL \
  quay.io/keboola/developer-portal-cli-v2:latest update-app-repository $DP_VENDOR_ID $DP_APP_ID $TRAVIS_TAG
