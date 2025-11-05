---
name: woocommerce-markdown
description: Guidelines for creating and modifying markdown files in WooCommerce. Use when writing documentation, README files, or any markdown content.
---

# WooCommerce Markdown Guidelines

This skill provides guidance for creating and editing markdown files in the WooCommerce project.

## Critical Rules

1. **Always lint after changes** - Run `markdownlint --fix` then `markdownlint` to verify
2. **Run from repository root** - Ensures `.markdownlint.json` config is loaded
3. **Use UTF-8 encoding** - Especially for directory trees and special characters
4. **Follow WooCommerce markdown standards** - See configuration rules below

## WooCommerce Markdown Configuration

The project uses markdownlint with these specific rules (from `.markdownlint.json`):

### Enabled Rules

- **MD003**: Heading style must be ATX (`# Heading` not `Heading\n===`)
- **MD007**: Unordered list indentation must be 4 spaces
- **MD013**: Line length limit disabled (set to 9999)
- **MD024**: Multiple headings with same content allowed (only check siblings)
- **MD031**: Fenced code blocks must be surrounded by blank lines
- **MD032**: Lists must be surrounded by blank lines
- **MD033**: HTML allowed for `<video>` elements only
- **MD036**: Emphasis (bold/italic) should not be used as headings - use proper heading tags
- **MD040**: Fenced code blocks should specify language
- **MD047**: Files must end with a single newline

### Disabled Rules

- **no-hard-tabs**: Tabs are allowed
- **whitespace**: Trailing whitespace rules disabled

## Markdown Writing Guidelines

### Headings

```markdown
# Main Title (H1) - Only one per file

## Section (H2)

### Subsection (H3)

#### Minor Section (H4)
```

- Use ATX style (`#`) not underline style
- One H1 per file (usually the title)
- Maintain heading hierarchy (don't skip levels)

### Lists

**Unordered lists:**

```markdown
- Item one
- Item two
    - Nested item (4 spaces)
    - Another nested item
- Item three
```

**Ordered lists:**

```markdown
1. First item
2. Second item
3. Third item
```

**Important:**

- Use 4 spaces for nested list items
- Add blank line before and after lists
- Use `-` for unordered lists (not `*` or `+`)

### Code Blocks

**Always specify the language:**

````markdown
```bash
pnpm test:php:env
```

```php
public function process_order( int $order_id ) {
    // code here
}
```

```javascript
const result = calculateTotal(items);
```
````

**Common language identifiers:**

- `bash` - Shell commands
- `php` - PHP code
- `javascript` or `js` - JavaScript
- `typescript` or `ts` - TypeScript
- `json` - JSON data
- `sql` - SQL queries
- `markdown` or `md` - Markdown examples

**Code block rules:**

- Add blank line before the opening fence
- Add blank line after the closing fence
- Always specify language (never use plain ` ``` `)

### Inline Code

Use backticks for inline code:

```markdown
Use the `process_order()` method to handle orders.
The `$order_id` parameter must be an integer.
```

### Links

```markdown
[Link text](https://example.com)

[Internal link](../path/to/file.md)

[Link with title](https://example.com "Optional title")
```

### Tables

```markdown
| Column 1 | Column 2 | Column 3 |
|----------|----------|----------|
| Value 1  | Value 2  | Value 3  |
| Value 4  | Value 5  | Value 6  |
```

- Use pipes (`|`) for column separators
- Header separator row required
- Alignment optional (`:---`, `:---:`, `---:`)

### Directory Trees

**Always use UTF-8 box-drawing characters:**

```markdown
src/
├── Internal/
│   ├── Admin/
│   │   └── Controller.php
│   └── Utils/
│       └── Helper.php
└── External/
    └── API.php
```

**Never use:**

- ASCII art (`+--`, `|--`)
- Spaces or tabs for tree structure
- Control characters

### Emphasis

```markdown
**Bold text** for strong emphasis
*Italic text* for regular emphasis
***Bold and italic*** for very strong emphasis
```

## Workflow for Editing Markdown

1. **Make your changes** to the markdown file
2. **Auto-fix linting issues:**

   ```bash
   markdownlint --fix path/to/file.md
   ```

3. **Check for remaining issues:**

   ```bash
   markdownlint path/to/file.md
   ```

4. **Manually fix** what remains (usually language specs for code blocks)
5. **Verify clean** - No output means success
6. **Commit changes**

## Common Linting Errors and Fixes

### MD007: List indentation

**Problem:**

```markdown
- Item
  - Nested (only 2 spaces)
```

**Fix:**

```markdown
- Item
    - Nested (4 spaces)
```

### MD031: Code blocks need blank lines

**Problem:**

````markdown
Some text
```bash
command
```
More text
````

**Fix:**

````markdown
Some text

```bash
command
```

More text
````

### MD032: Lists need blank lines

**Problem:**

````markdown
Some text
- List item
````

**Fix:**

````markdown
Some text

- List item
````

### MD036: Emphasis as heading

**Problem:**

```markdown
**Example: Using bold as a heading**

Some content here
```

**Fix:**

```markdown
#### Example: Using a proper heading

Some content here
```

### MD040: Code needs language

**Problem:**

````markdown
```
code here
```
````

**Fix:**

````markdown
```bash
code here
```
````

## Special Cases

### CLAUDE.md Files

CLAUDE.md files are AI assistant documentation:

- Must be well-formatted for optimal parsing by AI
- Follow all markdownlint rules strictly
- Use clear, hierarchical structure
- Include table of contents for long files

### README Files

- Start with H1 title
- Include brief description
- Add installation/usage sections
- Keep concise and scannable

### Changelog Files

- Follow Keep a Changelog format
- Use consistent date formatting
- Group changes by type (Added, Changed, Fixed, etc.)

## Troubleshooting

### File Shows as "data" Instead of Text

**Problem:** File is corrupted with control characters

**Fix:**

```bash
tr -d '\000-\037' < file.md > file.clean.md && mv file.clean.md file.md
file file.md  # Verify shows "UTF-8 text"
```

### Linting Shows Unexpected Errors

**Problem:** Not running from repository root

**Fix:**

```bash
# Always run from root
cd /path/to/woocommerce
markdownlint path/to/file.md

# NOT like this
markdownlint /absolute/path/to/file.md
```

### Auto-fix Doesn't Work

**Problem:** Some issues require manual intervention

**Fix:**

- Language specs for code blocks must be added manually
- Long lines may need manual rewrapping
- Some structural issues require content reorganization

## Notes

- Most markdown issues are auto-fixable with `markdownlint --fix`
- Always run markdownlint from repository root
- UTF-8 encoding is critical for special characters
- CLAUDE.md files must pass linting for optimal AI parsing
- See `woocommerce-dev-cycle` skill for markdown linting commands
