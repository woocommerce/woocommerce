# Slack Test Report

A utility tool to send test reports to Slack channels. This tool is particularly useful for notifying teams about test results, especially failures, directly in Slack.

## Usage

To see available commands run `pnpm utils slack-test-report --help` from the project root.

## Command Options

-    `-c, --conclusion <conclusion>` (Required): Test run conclusion. Expected one of: success, failure, skipped, cancelled
-    `-r, --report-name <reportName>`: The name of the report (e.g., "post-merge tests", "daily e2e tests")
-    `-u, --username <username>`: The Slack username (default: "Github reporter")
-    `-n, --pr-number <prNumber>`: The PR number to include in the message (for pull_request events)
-    `-t, --pr-title <prTitle>`: The PR title to include in the message (for pull_request events)
-    `-m, --commit-message <commitMessage>`: The commit message
-    `--config <configPath>`: Path to a JSON config file containing notification rules or settings

## Environment Variables

The following environment variables are required:

-    `SLACK_TOKEN`: Slack API token for sending messages
-    `DEFAULT_CHECKS_CHANNEL`: Default Slack channel ID for notifications
-    `GITHUB_SHA`: Git commit SHA
-    `GITHUB_ACTOR`: GitHub username of the person who triggered the action
-    `GITHUB_TRIGGERING_ACTOR`: GitHub username of the person who triggered the workflow
-    `GITHUB_EVENT_NAME`: Name of the GitHub event that triggered the workflow
-    `GITHUB_RUN_ID`: Unique identifier of the workflow run
-    `GITHUB_RUN_ATTEMPT`: Attempt number of the workflow run
-    `GITHUB_SERVER_URL`: GitHub server URL
-    `GITHUB_REPOSITORY`: Repository name with owner
-    `GITHUB_REF_TYPE`: The type of ref that triggered the workflow
-    `GITHUB_REF_NAME`: The branch or tag name that triggered the workflow

## Configuration File

The configuration file is optional. If no config file is provided, the tool will simply send notifications to the channel specified in the `DEFAULT_CHECKS_CHANNEL` environment variable.

When you need more complex routing logic (e.g., sending different types of test results to different channels), you can provide a JSON configuration file. The config file should have the following structure:

```json
{
  "defaultChannel": "DEFAULT_CHANNEL_ENV_VAR",
  "routes": [
    {
      "checkType": "release-checks",
      "channels": ["CHANNEL_ENV_VAR_1"],
      "excludeDefaultChannel": false
    },
    {
      "refName": "release/**",
      "channels": ["CHANNEL_ENV_VAR_2"],
      "excludeDefaultChannel": true
    }
  ]
}
```

### Configuration Options

-    `defaultChannel`: Environment variable name for the default Slack channel
-    `routes`: Array of routing rules with the following properties:
-    `checkType`: (Optional) Type of check to match (e.g., "release-checks", "daily-checks")
-    `refName`: (Optional) Git reference pattern to match (supports glob patterns)
-    `channels`: Array of environment variable names for Slack channel IDs
-    `excludeDefaultChannel`: (Optional) If true, skips sending to the default channel for matching rules

At least one of `checkType` or `refName` must be specified in each route.

## Message Format

The tool sends messages with the following information:

-    Test result status (success/failure)
-    Report name (if provided)
-    Context information based on the event type
-    Run details: Run ID, attempt number, and triggering actor
-    Action buttons for more information

## Examples

Send a failure report for a pull request:

```bash
pnpm utils slack-test-report \
  -c failure \
  -r "E2E Tests" \
  -n "1234" \
  -t "Add new feature" \
  -m "Fix e2e tests"
```

Send a failure report with custom routing:

```bash
pnpm utils slack-test-report \
  --config ".github/workflows/slack-report-config.json" \
  -c failure \
  -r "Daily Checks" \
  -m "Test commit message"
```

Note: The tool only sends messages for test failures. Other conclusions (success, skipped, cancelled) will be logged but won't trigger Slack notifications.
