---
post_title: WooCommerce Pre-releases
sidebar_label: WooCommerce Pre-releases
sidebar_position: 5
---

# WooCommerce Pre-releases

WooCommerce pre-releases provide early access to upcoming features and improvements, allowing developers, testers, and the community to prepare for future releases.

This document outlines the different types of pre-releases available, their timing, and how they fit into the overall release cycle.

| Release Name      | Estimated Timing                                     |
|-------------------|------------------------------------------------------|
| `nightly`         | Every night                                          |
| `-dev`            | At feature freeze                                    |
| `-beta.1`         | 1 week after feature freeze                          |
| `-beta.2`, ...    | At least 1 more, as needed before `-rc.1`            |
| `-rc.1`           | Shortly (~1 day) before final release                |

## Nightlies

Regenerated every night based on the current contents of `trunk`. Found [under the `nightly` tag on GitHub](https://github.com/woocommerce/woocommerce/releases/tag/nightly).

These are only available in the GitHub repository, and are neither announced publicly nor uploaded to WordPress.org.

## Release cycle pre-releases

Once the feature freeze for the upcoming main version (`X.Y.0`) happens, a release-specific branch is created (named `release/x.y`) which is used for stabilization, fixing regressions, and building all the pre-releases tied to that release cycle.

These releases are tagged at various points in time as described below. As usual, the source of truth for any specific dates is [our release calendar](https://developer.woocommerce.com/release-calendar/).

### `-dev` release

This is an auto-generated tag that is created at the same time as the feature freeze happens for the current cycle. For example, when the feature freeze for `10.1.0` happened, `10.1.0-dev` was tagged.

This is an internal development tag, available only in the GitHub repository and not on WordPress.org.

### Beta

Meant to be used by plugin authors and early adopters to test the features and functionality in an upcoming version. The first beta is usually released 1 week after the feature freeze.

Betas are versioned incrementally, starting with `-beta.1`, then `-beta.2`, and so on.

We aim to release at least 2 betas per cycle, but more are possible if any important bug fixes or functionality require additional testing or receive an important update before the final stable release.

All beta versions are available both on GitHub and WordPress.org.

For guidance on how to participate in WooCommerce beta testing and contribute valuable feedback, refer to our [beta testing documentation](/docs/contribution/testing/beta-testing/).

### Release Candidate (RC)

These are pre-release versions that are feature-complete and considered stable enough for final testing. RCs are typically released shortly before the final release and are used internally for a final round of testing and checks.

They are versioned incrementally as `-rc.1`, `-rc.2`, and so on.

We aim to release at least one RC before the final release, which will not be publicly announced but will be tagged and available for download on both GitHub and WordPress.org.
