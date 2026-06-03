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
- **Plugin-affecting?** Code shipped to users = yes. CI/CD, workflows, tooling, docs = no. This drives the Milestone, Changelog, and Release Communication decisions in Step 3.

### 2. Gather Context

Extract issue/PR refs from commits and branch name:

- **Issue ref**: use what's in commits/branch if present; otherwise omit `Closes #` (Linear refs are internal — only reference GitHub issues in PRs).
- **Bug-fix origin PR**: if a bug fix and no PR ref is in the diff/commits, search history (`git log -S` on touched lines) to find the introducing PR; omit `Bug introduced in PR #XXXX.` if not found.
- **Motivation**: infer from diff and commit messages. Use the strongest summary you can; don't block on missing context.

### 3. Generate PR Title + Body

**Title** (under 70 chars, verb-first — the repo convention):

- `Fix <what was broken>`, `Add <what>`, or other verb (Restore, Bump, Prepare, etc.)
- Optional area prefix: `[Email Editor] Fix double margin-top in flex layout`
- No `fix:`/`feat:` prefixes. No Linear ticket refs — Linear is internal, PRs are public.

**Body** — fill in `.github/PULL_REQUEST_TEMPLATE.md` (loaded in dynamic context above) section by section, in order. The template's HTML comments describe what each section is for — follow them as the per-section instructions. Repo-specific rules that the template doesn't carry, layered on top:

- **Changes proposed**: 2-3 sentences. Lead with WHY, then WHAT. No filler ("This PR addresses..."). Drop the `Closes # .` line if you don't have a GitHub issue ref. Drop the `Bug introduced in PR # .` line if not a bug fix or origin PR unknown.
- **Milestone**: tick the auto-assign box only for plugin-affecting changes. The section itself stays — the template marks it do-not-remove.
- **Changelog entry**:
    - Plugin-affecting, no changelog files in the diff → tick `Automatically create` with Significance, Type, and a user-facing Message.
    - Plugin-affecting with changelog files already in the diff → tick `does not require` with the Comment "Created manually."
    - Not plugin-affecting → tick `does not require` with a Comment explaining why (e.g., "Internal tooling, not shipped to merchants").
- **Release Communication**: tick `Feature Highlight` for user-visible features merchants will notice, or `Developer Advisory` for changes affecting extension/theme developers (hook signatures, deprecations, REST API field changes). Otherwise leave both unchecked.

After filling, keep the template's HTML comments (`<!-- -->`) — they support PR automation and GitHub tests. Remove only unfilled placeholder lines that are actual visible placeholders (e.g., `Closes # .`, `Bug introduced in PR # .`).

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
- Preserve the PR template section headings and HTML comments exactly
- Changelog checkboxes must match CI automation format
