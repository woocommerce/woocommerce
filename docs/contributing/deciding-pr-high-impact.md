---
post_title: How to assess the impact of a pull request
menu_title: Assessing PR impact
tags: how-to
---

Deciding if a Pull Request should be declared High-Impact is a complex task. To achieve it, we need to assess and estimate the impact that the changes introduced in the Pull Request have in WooCommerce, which is usually a subjective task and sometimes inaccurate, due to the huge knowledge it demands of the WooCommerce product details, technical details and even customers issues history.

On this page, we will share some guidelines to help you assess the impact degree of a Pull Request.

## You should mark a Pull Request as High-Impact if

- It adds a **new feature** to WooCommerce, except if it's behind a feature flag.
- Modifies **critical functionality** (see the [critical flows list](https://github.com/woocommerce/woocommerce/wiki/Critical-Flows)).
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
