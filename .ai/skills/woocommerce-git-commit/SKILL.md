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

Use the current session context to understand what work was done and why. If motivation is still unclear after reviewing the diff and conversation, infer the best reasonable description from the diff and commit conventions.

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

### 4. Preview

State each proposed commit before executing:

```text
Commit 1: Fix double margin-top in flex layout
  files: src/Blocks/EmailEditor/Layout.php
         src/Blocks/EmailEditor/styles.css

Commit 2: Add changelog entries for email editor fix
  files: plugins/woocommerce/changelog/fix-email-margin
```

### 5. Execute

For each confirmed commit:

```sh
git add <specific files>
git commit -m "<message>"
```

Always stage specific files — never `git add -A` or `git add .`.

After all commits, show `git log --oneline -n <number of new commits>` to confirm.

## Changelog Entry Files

Create one entry per affected package under `<package>/changelog/`. The interactive `pnpm --filter=<project> changelog add` prompts for its answers, so either run it non-interactively with its flags (`-s` significance, `-t` type, `-e` entry, `-c` comment, `-f` filename):

```sh
pnpm --filter=<project> changelog add --no-interaction -s patch -t fix -e "One user-facing sentence."
```

or write the file directly, in this format:

- `Significance:` — `patch`, `minor`, or `major`.
- `Type:` — one of the types declared in the package's `composer.json` under `extra.changelogger.types`. For WooCommerce Core: `fix`, `add`, `update`, `dev`, `tweak`, `performance`, `enhancement`.
- **Body** — one user-facing sentence, after a blank line, describing what changed for merchants. This ships in the release changelog, so keep it short and leave out issue numbers, PR links, and implementation detail.
- `Comment:` — **only** for entries that ship no body, to explain why there is no user-facing line. Almost always paired with `Type: dev`. Never use it alongside a body, and never as a place to record an issue reference.

An entry with a user-facing change:

```text
Significance: patch
Type: fix

Restore the "Browse store" link on the empty cart page.
```

An entry with nothing to tell merchants:

```text
Significance: patch
Type: dev
Comment: Remove real sleep() calls from PHP unit tests; no production change.
```

Do not confuse `Comment:` with the pull request template's "Comment required below" field. That one belongs to the PR form and is unrelated to the changelog file.

Changes that touch no package (for example `.ai/skills/` or `AGENTS.md`) need no entry at all.

## Constraints

- No Co-Authored-By lines or self-attribution
- Never push to remote
- Never use `git add -A` or `git add .`
- Do not run pre-commit lint/test checks — the `woocommerce-dev-cycle` skill handles that. Linting should be run *before* invoking this skill, not after. For PHP changes, don't commit until that skill's PHP lint has passed (warnings block CI).
