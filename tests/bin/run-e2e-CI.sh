#!/usr/bin/env bash
# Setting max amount of time to wait for the Docker container to be built - 5 minutes
MAX_ATTEMPTS=30

# Setting the period in seconds after which to check if the Docker container had been built
DELAY_SEC=10

# Counter for the loop that checks if the Docker container had been built
count=0

while [[ "$(curl -s -o /dev/null -w ''%{http_code}'' localhost:8084/?page_id=4)" != "200" ]]

do
  echo "$(date) - Docker container is still being built"
  sleep ${DELAY_SEC}

  ((count++))

  if [[ $count -gt ${MAX_ATTEMPTS} ]]; then
  	echo "$(date) - Docker container couldn't be built"
  	exit
  fi
done

echo "$(date) - Docker container had been built successfully"

npm run test:e2e
