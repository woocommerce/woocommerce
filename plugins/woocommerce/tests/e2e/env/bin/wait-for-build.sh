#!/usr/bin/env bash
# Max amount of time to wait for the Docker container to be built
# Allowing 30 polling attempts, 10 seconds delay between each attempt
MAX_ATTEMPTS=30

# Delay (in seconds) between each polling attempt
DELAY_SEC=10

# Counter for the loop that checks if the Docker container had been built
count=0
WP_BASE_URL=$(node utils/get-base-url.js)
printf "Testing URL: $WP_BASE_URL\n\n"

while [[ "$(curl -s -o /dev/null -w ''%{http_code}'' ${WP_BASE_URL}/?pagename=ready)" != "200" ]]

do
  echo "$(date) - Waiting for testing environment"
  sleep ${DELAY_SEC}

  ((count++))

  if [[ $count -gt ${MAX_ATTEMPTS} ]]; then
	echo "$(date) - Testing environment couldn't be found"
	exit 1
  fi
done

if [[ $count -gt 0 ]]; then
  echo "$(date) - Testing environment is ready"
fi
