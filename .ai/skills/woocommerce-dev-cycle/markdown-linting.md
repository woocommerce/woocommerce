# Markdown Linting

## Table of Contents

- [Critical Rule](#critical-rule)
- [Installation](#installation)
- [Basic Commands](#basic-commands)
- [Important: Always Run from Repository Root](#important-always-run-from-repository-root)
- [Recommended Workflow](#recommended-workflow)
- [Common Markdown Linting Issues](#common-markdown-linting-issues)
- [Character Encoding in Markdown Files](#character-encoding-in-markdown-files)
- [Examples](#examples)
- [Notes](#notes)

## Critical Rule

**ALWAYS lint markdown files after changes.** They must pass markdownlint validation.

## Installation

If markdownlint is not available:

```bash
npm install -g markdownlint-cli
```

## Basic Commands

```bash
# Check markdown file (run from repo root)
markdownlint plugins/woocommerce/CLAUDE.md

# RECOMMENDED: Auto-fix issues first (handles most errors)
markdownlint --fix plugins/woocommerce/CLAUDE.md

# Check multiple files
markdownlint packages/js/CLAUDE.md plugins/woocommerce/CLAUDE.md

# Lint all CLAUDE.md files
markdownlint packages/js/CLAUDE.md plugins/woocommerce/CLAUDE.md \
  plugins/woocommerce/client/admin/CLAUDE.md
```

## Important: Always Run from Repository Root

**CRITICAL:** Always run markdownlint from the repository root so the `.markdownlint.json` config file is loaded.

Using absolute paths bypasses the config and may show incorrect errors.

```bash
# ✅ CORRECT - run from repo root
cd /path/to/woocommerce
markdownlint plugins/woocommerce/CLAUDE.md

# ❌ WRONG - bypasses config
markdownlint /absolute/path/to/plugins/woocommerce/CLAUDE.md
```

## Recommended Workflow

1. Make markdown changes
2. Run `markdownlint --fix path/to/file.md` (auto-fixes most issues)
3. Check remaining: `markdownlint path/to/file.md`
4. Manually fix what remains (language specs, long lines)
5. Verify clean, then commit

## Common Markdown Linting Issues

| Code | Issue | Description | Fix |
|------|-------|-------------|-----|
| **MD007** | List indentation | Wrong indentation level | Use 4 spaces for nested items |
| **MD013** | Line length limit | Line exceeds 80 chars | Break into multiple lines |
| **MD031** | Code blocks need blank lines | Missing blank lines | Add blank above/below code blocks |
| **MD032** | Lists need blank lines | Missing blank lines | Add blank before/after lists |
| **MD036** | Emphasis as heading | Using bold instead of heading | Use `###` not bold |
| **MD040** | Code needs language | Missing language spec | Add: \`\`\`bash, \`\`\`php, etc. |
| **MD047** | Need trailing newline | File doesn't end with newline | File must end with newline |

## Character Encoding in Markdown Files

### Critical: Use Proper UTF-8 Characters

**NEVER allow control characters or null bytes into markdown files.**

### Directory Trees

Use UTF-8 box-drawing characters, not spaces, tabs, or ASCII art:

```markdown
✅ CORRECT - UTF-8 box-drawing:
.ai/skills/
├── woocommerce-backend/
│   ├── SKILL.md
│   └── file-entities.md
└── woocommerce-dev-cycle/
    └── SKILL.md

❌ WRONG - ASCII art or spaces:
.ai/skills/
+-- woocommerce-backend/
|   +-- SKILL.md
    +-- file-entities.md
```

### Avoiding File Corruption

**NEVER use Edit tool after `markdownlint --fix`** if the file contains directory trees.

**Always check file encoding first:**

```bash
file path/to/file.md
# Should show: "UTF-8 text" or "ASCII text"
# NEVER: "data"
```

### Fixing Corrupted Files

If a file becomes corrupted (shows as "data" instead of text):

```bash
# Remove control characters and null bytes
tr -d '\000-\037' < file.md > file.clean.md && mv file.clean.md file.md

# Verify encoding after fix
file file.md
```

## Examples

### Adding Language Specs to Code Blocks

**Before:**

````markdown
```
pnpm test:php:env
```
````

**After:**

````markdown
```bash
pnpm test:php:env
```
````

Common language specs:

- `bash` - Shell commands
- `php` - PHP code
- `javascript` or `js` - JavaScript
- `typescript` or `ts` - TypeScript
- `json` - JSON data
- `markdown` or `md` - Markdown examples

### Breaking Long Lines

**Before:**

```markdown
This is a very long line that exceeds the 80 character limit and needs to be broken into multiple lines for better readability.
```

**After:**

```markdown
This is a very long line that exceeds the 80 character limit and needs to be
broken into multiple lines for better readability.
```

### Blank Lines Around Code Blocks

**Before:**

````markdown
Some text here
```bash
command here
```
More text
````

**After:**

````markdown
Some text here

```bash
command here
```

More text
````

## Notes

- `markdownlint --fix` automatically handles most issues
- CLAUDE.md files are AI assistant documentation and must be well-formatted for optimal parsing
- Only a few issues require manual fixing (language specs, long lines)
- Always verify encoding after edits to prevent corruption
