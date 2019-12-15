#!/usr/bin/env bash
while [[ "$(curl -s -o /dev/null -w ''%{http_code}'' localhost:8084/?p=5)" != "200" ]];
do
  echo "$(date) - Docker container is still being built";
  sleep 1;
done
echo "$(date) - Docker container had been built successfully";
npm run test:e2e;
