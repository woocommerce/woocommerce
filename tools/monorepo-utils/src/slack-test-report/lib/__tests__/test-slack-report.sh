#!/bin/bash

required_vars=(
    "SLACK_TOKEN"
    "DEFAULT_CHECKS_CHANNEL"
    "RELEASE_CHECKS_CHANNEL"
    "DAILY_CHECKS_CHANNEL"
)

for var in "${required_vars[@]}"; do
    if [ -z "${!var}" ]; then
        echo "Error: Required environment variable $var is not set"
        exit 1
    fi
done

export GITHUB_REF_NAME="trunk"
export GITHUB_EVENT_NAME="push"
pnpm utils slack-test-report --config ".github/workflows/slack-report-config.json" -c "failure" -r "Tests" -m "Test commit message"

export GITHUB_REF_NAME="trunk"
export GITHUB_EVENT_NAME="schedule"
pnpm utils slack-test-report --config ".github/workflows/slack-report-config.json" -c "failure" -r "daily-checks Tests" -m "Test commit message"
