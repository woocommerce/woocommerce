---
name: woocommerce-git
description: Guidelines for git and GitHub operations in the WooCommerce repository.
---

# WooCommerce Git Guidelines

This skill provides guidance for git and GitHub operations in the WooCommerce repository, including creating pull requests and managing changelogs.

## Pull Request Template

When creating PRs, use the template at `.github/PULL_REQUEST_TEMPLATE.md`. The PR body must include these sections:

- **Changes proposed in this Pull Request** — describe what changed and why
- **How to test the changes in this Pull Request** — step-by-step testing instructions
- **Milestone** — check the box to auto-assign the next WooCommerce version milestone
- **Changelog entry** — check "This Pull Request does not require a changelog entry" and add a comment explaining why, OR check "Automatically create a changelog entry" and fill in the significance, type, and message (only if a changelog file was not already committed to the branch)

Omit sections that don't apply (e.g. screenshots for non-UI changes, bug fix links for features). Pass the body via a HEREDOC to `gh pr create --body`.
