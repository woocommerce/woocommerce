#!/bin/bash

set -eo pipefail

echo "Validating required variables for test runs against external sites."

if [ -z "$BASE_URL" ]; then
  echo "BASE_URL environment variable is required."
  exit 1
fi

if [ -z "$ADMIN_USER" ]; then
  echo "ADMIN_USER environment variable is required."
  exit 1
fi

if [ -z "$ADMIN_PASSWORD" ]; then
  echo "ADMIN_PASSWORD environment variable is required."
  exit 1
fi

#if [ -z "$ADMIN_USER_EMAIL" ]; then
#  echo "ADMIN_USER_EMAIL environment variable is required."
#  exit 1
#fi

if [ -z "$CUSTOMER_USER" ]; then
  echo "CUSTOMER_USER environment variable is required."
  exit 1
fi

if [ -z "$CUSTOMER_PASSWORD" ]; then
  echo "CUSTOMER_PASSWORD environment variable is required."
  exit 1
fi
