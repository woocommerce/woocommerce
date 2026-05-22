---
name: woocommerce-git-draft-pr
description: Create a high-quality draft PR for the current branch. Use when the user says "create a PR", "draft PR", "open a PR", "make a PR", "push and create PR", or "submit PR".
---

# Create Draft PR

Create a concise, reviewer-friendly draft PR from the current branch.

## Dynamic Context

- Current branch: !`git branch --show-current`
- Commits: !`git log trunk..HEAD --format="%h %s" --reverse 2>/dev/null || echo "No commits ahead of trunk"`
- Diff stat: !`git diff trunk...HEAD --stat 2>/dev/null`
- Uncommitted changes: !`git status --short`
- Existing changelogs: !`git diff trunk...HEAD --name-only -- '*/changelog/*' 2>/dev/null`
- PR template: !`cat .github/PULL_REQUEST_TEMPLATE.md`

## Procedure

### 1. Preflight and Analyze

Verify from dynamic context: not on trunk (stop if so), commits exist ahead of trunk (stop if none), no uncommitted changes (stop if dirty).

**Base branch**: use `release/*` if the branch was created from one, otherwise `trunk`.

From the dynamic context above (read full diffs only if the stat summary is ambiguous), determine:

- **Change type**: Fix, Add, Update, Dev, Tweak, Performance, or Enhancement
- **Significance**: Patch (most common), Minor (new features), Major (breaking — rare)
- **Bug fix?** Look for issue refs in commits/branch name (e.g., `#12345`, `fix/issue-12345`)
- **UI changes?** Changes in `client/`, `templates/`, CSS/SCSS, JSX/TSX
- **Plugin-affecting?** Code shipped to users = yes. CI/CD, workflows, tooling, docs = no. This drives changelog, milestone, and PR body complexity — non-plugin PRs use a simplified body (see Step 3).

### 2. Gather Context

Extract issue/PR refs from commits and branch name:

- **Issue ref**: use what's in commits/branch if present; otherwise omit `Closes #` (Linear refs are internal — only reference GitHub issues in PRs).
- **Bug-fix origin PR**: if a bug fix and no PR ref is in the diff/commits, search history (`git log -S` on touched lines) to find the introducing PR; omit `Bug introduced in PR #XXXX.` if not found.
- **Motivation**: infer from diff and commit messages. Use the strongest summary you can; don't block on missing context.

### 3. Generate PR Title + Body

Use the PR template from the dynamic context above.

**Title** (under 70 chars, verb-first — the repo convention):

- `Fix <what was broken>`, `Add <what>`, or other verb (Restore, Bump, Prepare, etc.)
- Optional area prefix: `[Email Editor] Fix double margin-top in flex layout`
- No `fix:`/`feat:` prefixes. No Linear ticket refs — Linear is internal, PRs are public.

**Body** — depends on whether the change is plugin-affecting:

#### Non-plugin changes (CI/CD, tooling, docs, `.ai/skills/`, workflows)

Use a simplified body with only these sections:

- **Submission Review Guidelines**: Keep as-is from template.
- **Changes proposed**: 2-3 sentences. Lead with WHY, then WHAT.

Skip Screenshots, Testing instructions, Testing done, Milestone, and Changelog sections entirely.

#### Plugin-affecting changes

Use the full template:

- **Submission Review Guidelines**: Keep as-is from template.
- **Changes proposed**: 2-3 sentences. Lead with WHY, then WHAT. No filler ("This PR addresses..."). Include `Closes #1234.` if applicable. For bugs: `Bug introduced in PR #XXXX.` (omit this line entirely if not a bug fix).
- **Screenshots**: Remove section if no UI changes. For UI changes, use Chrome DevTools MCP to capture screenshots if available; otherwise remind user to add them before marking ready.
- **Testing instructions**: Concrete numbered steps with expected outcomes derived from the diff. Each step must be actionable — don't reference links that won't exist yet.
- **Testing done**: Fill with what's verifiable from the session (commits, test runs, lint runs). If nothing is verifiable, write "Author to fill in before marking ready."
- **Milestone**: Check auto-assign `[x]` if plugin-affecting.
- **Changelog**: If changelogs already in diff → "does not require" (created manually). Otherwise → "Automatically create" `[x]` with Significance, Type, and a user-facing Message.

Strip all HTML comments (`<!-- -->`) and unfilled placeholder lines (e.g., `Closes # .`, `Bug introduced in PR # .`) from output.

### 4. Preview

State the generated title and body before executing.

### 5. Push and Create

```sh
git push -u origin $(git branch --show-current)
gh pr create --draft --title "<title>" --base <base-branch> --body "$(cat <<'PRBODY'
<full PR body>
PRBODY
)"
```

Output the PR URL. If UI changes need screenshots, remind the user.

## Constraints

- No Co-Authored-By lines or self-attribution
- Never commit code — pushing is fine
- Preserve the PR template section headings exactly (for plugin-affecting PRs)
- Changelog checkboxes must match CI automation format
