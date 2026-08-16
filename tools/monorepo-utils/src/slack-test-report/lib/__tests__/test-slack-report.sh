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
pnpm utils slack-test-report --config ".github/workflows/slack-report-config.json" -c "failure" -r "daily-checks Tests" -m "Test commit message" --jobs-list "Failed:###job1,job2,job3,job4,job5,job6,job7,job8,job9,job10,job11,job12,job13,job14,job15,job16,job17,job18,job19,job20"
