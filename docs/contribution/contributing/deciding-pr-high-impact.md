---
post_title: How to assess the impact of a pull request
sidebar_label: Assessing PR impact

---

# How to assess the impact of a pull request

Deciding if a Pull Request should be declared High-Impact is a complex task. To achieve it, we need to assess and estimate the impact that the changes introduced in the Pull Request have in WooCommerce, which is usually a subjective task and sometimes inaccurate, due to the huge knowledge it demands of the WooCommerce product details, technical details and even customers issues history.

On this page, we will share some guidelines to help you assess the impact degree of a Pull Request.

## You should mark a Pull Request as High-Impact if

- It adds a **new feature** to WooCommerce, except if it's behind a feature flag.
- Modifies **critical functionality** that shoppers or merchants rely on to run a store.
- It fixes a **high-priority bug** (this includes Blocks fix releases core version bumps).
- It contains a **security fix**.
- Updates **SQL queries**.
- Touches any of the **$_REQUEST** family of variables.
- Any kind of **data migration/update**.
- Changes to **emails** sent from WooCommerce.
- Changes to WooCommerce **hooks/actions/filters**.
- Changes to **REST API endpoints**.
- It's a **big PR** (i.e. adds several changes in many files).
- It has **i18n changes** (for example, any file from `woocommerce/i18n` is modified).

## You should not mark a Pull Request as High-Impact if

- It only updates automated tests, things related to infrastructure not included in the WooCommerce release package, or other projects in the monorepo not included in the release package.
- It only contains readme or changelog changes.
- Fixes a low-priority bug such as a typo etc.
- Doesn't need to be verified in multiple environment types.
- Regular scheduled (not a fix release) core version bumps for the Blocks package (as testing will already be scheduled).
- It's part of a feature that hasn't been released as a whole yet (i.e. it's behind a feature flag currently in progress).

## My PR is High-Impact. What's next?

If your PR is High-Impact, be sure to label it with `impact: high` and the WooCommerce Core team will keep special considerations for testing it.

## Review requirements

Pull requests against `trunk` require an approving review before merge. That is the default for every change, because an independent reviewer brings experience, product knowledge, and assumptions that the author does not have. AI tooling working from the author's context does not add that independence.

The impact assessment above is also the reference for when that requirement can be relaxed and when it must not be.

### When a PR may be merged without a formal approval

Contributors with permission to bypass the review requirement may use their judgment to merge a PR without a formal approval when it clearly fits the "should not mark as High-Impact" list above: documentation, changelog, or typo changes; updates to automated tests; and tooling or infrastructure changes not shipped in the release package.

Bypassing the requirement is an exception for low-risk changes, not an alternative default. If you are unsure whether your change qualifies, request a review.

### When an independent human review is always required

A PR matching anything in the High-Impact list above must receive an independent human review before merge, no matter who (or what) authored it. The same applies to any change affecting security, privacy, data integrity, backward compatibility, or performance-sensitive paths.

Two things do not count as an independent human review:

- **The author's own review of AI-generated code.** The author drives the agent, provides its context, and shapes its solution. The author and the agent are a single workflow, not two reviewers. A single workflow can produce a convincing implementation of the wrong approach and not notice.
- **An AI review requested by the author.** AI reviews are encouraged as an additional safety net, but they complement an independent human review rather than replace it.

Community PRs always require a review and should only be merged with an approval.
