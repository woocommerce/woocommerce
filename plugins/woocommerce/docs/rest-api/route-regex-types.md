# Route Parameter Regex Types

This file maps route parameter regex patterns to their inferred types.
Any pattern not listed here will be marked as "exotic" and displayed inline.

## Pattern Mappings

| Pattern | Type |
|---------|------|
| `[\d]+` | integer |
| `\d+` | integer |
| `[\w-]+` | string |
| `[\w\-]+` | string |
| `\w[\w\s\-]*` | string |
| `[\S]+` | string |
| `[a-z0-9_\-]+` | string |
| `[a-z0-9_-]+` | string |
| `[a-zA-Z0-9_-]+` | string |
| `[a-zA-Z0-9_\-]+` | string |
| `[a-z]+` | string |
| `[\w]+` | string |
| `\w+` | string |
| `[\w\d\-]+` | string |
