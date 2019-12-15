#!/usr/bin/env bash
while [[ "$(curl -s -o /dev/null -w ''%{http_code}'' localhost:8084/?page_id=4)" != "200" ]];
do
  echo "$(date) - Docker container is still being built";
  sleep 10;
done
echo "$(date) - Docker container had been built successfully";
npm run test:e2e;
