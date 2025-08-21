---
post_title: Point Release Requests in WooCommerce
sidebar_label: Point Release Requests
sidebar_position: 4
---

# Point Release Requests in WooCommerce

Point releases address critical issues discovered in already-shipped WooCommerce versions. These are patch releases (e.g., 9.9.0 → 9.9.1) that contain only essential fixes for production environments.

Note that this process applies only to ALREADY-RELEASED VERSIONS that are in customer production environments.

## Point Release Lifecycle

Point releases follow a different lifecycle than regular releases:

- **Triggered by critical issues** discovered after a regular release ships
- **Limited scope** - only critical bug fixes and security patches
- **Expedited timeline** - faster review and release cycle
- **Backward compatibility** - no breaking changes allowed

## Qualifying Changes for Point Releases

Changes qualify for point releases only if they are:

- **Critical bug fixes** that cause data loss, security vulnerabilities, or major functionality failures
- **Security patches** addressing identified vulnerabilities
- **Performance fixes** for severe performance regressions
- **Compliance fixes** required for regulatory or legal compliance

**Excluded from point releases:**

- New features or enhancements
- Non-critical bug fixes
- Code refactoring or cleanup
- Documentation updates

## Point Release Request Process

### Standard Process: Critical Bug Fixes

**When to use:** Most point release scenarios

1. **Create a pull request** against the appropriate release branch (e.g., `release/9.9` for a fix targeting 9.9.x releases)

2. **Create a point release request issue** using the [point release template](https://github.com/woocommerce/woocommerce/issues/new?template=new-prr-template.yml) in the main repository

3. **Provide detailed justification** in the issue including:
    - Impact assessment (how many customers affected)
    - Business impact (revenue, compliance, security implications)
    - Risk assessment of the proposed fix
    - Evidence and reproduction steps

4. **Wait for release lead approval** - the release lead will approve the request, which automatically adds cherry-pick labels to your PR

5. **Adjust branch targeting** by modifying the automatically-added labels to specify which additional branches need the fix:
    - Keep `cherry pick to trunk` if the fix should go to trunk
    - Keep `cherry pick to frozen release` if the fix should go to the current frozen release
    - Remove labels for branches that don't need the fix

6. **Get your pull request reviewed, tested, and merged** into the target release branch

7. **Automation creates cherry-pick PRs** to other branches based on the labels still applied to your original PR

8. **Review and merge cherry-pick PRs** as soon as possible to ensure they don't delay the next release. These cherry-pick PRs are tracked with the same milestone as the original critical fix and must be merged before the point release is published.
