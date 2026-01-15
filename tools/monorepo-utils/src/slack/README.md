# Slack Utilities

Utilities for automated posting to Slack.

**Note:** You must set the `SLACK_TOKEN` environment variable with your Slack authentication token, and `SLACK_CHANNELS` with a comma-separated list of channel IDs.

To see available commands run `pnpm utils slack --help` from the project root.

## Basic Usage

You can use the CLI to send messages or upload files to Slack channels:

### Send a message to Slack

```sh
pnpm utils slack "Hello from the CLI!"
```

This will send the message to all channels specified in the `SLACK_CHANNELS` environment variable.

#### Sending Multiline Messages

You can send multiline messages in several ways:

- **Using literal newlines in your shell:**

```sh
  pnpm utils slack "Hello from the CLI!
This is a second line.
And a third line."
```

  (Press Enter inside the quotes to create new lines.)

- **Using `echo` and command substitution:**

```sh
  pnpm utils slack "$(echo -e 'Hello from the CLI!\nThis is a second line.\nAnd a third line.')"
```

- **Using escaped newlines (`\\n`):**

```sh
  pnpm utils slack "Hello from the CLI!\\nThis is a second line.\\nAnd a third line."
```

  (The utility will convert `\\n` to real newlines in the message.)

### Upload a file to Slack

```sh
pnpm utils slack "Here is a file" --file /path/to/file.txt
```

This will upload the file to all channels specified in `SLACK_CHANNELS`, with the given message as an initial comment.

#### Additional Options

- `--reply-ts <ts>`: (Optional) If provided, the file will be uploaded as a reply in the thread with the given timestamp.

**Example:**

```sh
pnpm utils slack "Replying with a file" --file /path/to/file.txt --reply-ts 1234567890.123456
```
