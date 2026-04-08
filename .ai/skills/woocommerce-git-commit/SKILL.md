---
name: woocommerce-git-commit
description: Commit uncommitted changes with well-crafted messages following WooCommerce repo conventions. Use when the user says "commit", "commit changes", "commit this", "git commit", or "save my work".
---

# Commit Changes

Commit uncommitted work with clear, conventional messages. Groups unrelated changes into separate commits when appropriate.

## Dynamic Context

- Current branch: !`git branch --show-current`
- Uncommitted changes: !`git status --short`
- Diff stat: !`git diff --stat`
- Staged diff stat: !`git diff --cached --stat`

## Procedure

### 1. Analyze Changes

From the dynamic context above, determine what changed:

- **No changes?** Stop — tell the user there's nothing to commit.
- **Already staged?** Respect what the user staged. Only consider unstaged files for additional commits.
- Read full diffs only for files where the stat summary is ambiguous.

### 2. Decide Commit Grouping

Default to a **single commit** unless changes are clearly unrelated. Split only when:

- Changelog entries exist alongside source changes (changelog = separate commit)
- Obviously independent changes are mixed (e.g., a bug fix AND an unrelated config change)

Two files changed for the same reason = one commit. Don't over-split.

### 3. Draft Commit Message(s)

Use the current session context to understand what work was done and why. If motivation is still unclear after reviewing the diff and conversation, ask the user in a single question.

**Format** — verb-first imperative, under 72 chars:

- `Fix get_item_downloads() not always returning an array`
- `Add cache priming to AJAX downloadable search`
- `Update email editor reset action label for consistency`
- `Refactor order count cache refresh logic`
- `Remove deprecated payment gateway fallback`

**Verbs**: Fix, Add, Update, Refactor, Remove, Restore, Bump, Prepare, Simplify, Improve, Replace

For changelog-only commits: `Add changelog entries for [description]`

Do NOT include issue/PR refs — GitHub adds those on squash-merge.

If $ARGUMENTS is provided, use it as guidance for the commit message.

### 4. Preview and Confirm

Show the user each proposed commit:

```text
Commit 1: Fix double margin-top in flex layout
  files: src/Blocks/EmailEditor/Layout.php
         src/Blocks/EmailEditor/styles.css

Commit 2: Add changelog entries for email editor fix
  files: plugins/woocommerce/changelog/fix-email-margin
```

Wait for user approval or corrections before executing.

### 5. Execute

For each confirmed commit:

```sh
git add <specific files>
git commit -m "<message>"
```

Always stage specific files — never `git add -A` or `git add .`.

After all commits, show `git log --oneline -n <number of new commits>` to confirm.

## Constraints

- No Co-Authored-By lines or self-attribution
- Never push to remote
- Never use `git add -A` or `git add .`
- Do not run pre-commit lint/test checks — the `woocommerce-dev-cycle` skill handles that. Linting should be run *before* invoking this skill, not after.
