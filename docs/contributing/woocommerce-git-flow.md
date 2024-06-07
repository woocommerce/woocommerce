---
post_title: WooCommerce Git flow
menu_title: WooCommerce Git flow
tags: reference
---

For core development, we use the following structure and flow.

![Git Flow WooCommerce uses for core development](https://developer.woocommerce.com/wp-content/uploads/2023/12/flow-1.png)

## Branches

* **Trunk** is the branch for all development and should always be the target of pull requests.
* Each major or minor release has a release branch e.g. `release/3.0` or `release/3.2`. There are no release branches for patch releases.
* Fixes are applied to trunk, and then **cherry picked into the release branch if needed**.
* Tags get created from release branches when ready to deploy.

## Branch naming

Prefixes determine the type of branch, and include:

* fix/
* feature/
* add/
* update/
* release/

When creating a **fix branch**, use the correct prefix and the issue number. Example:

```text
fix/12345
```

Alternatively you can summarise the change:

```text
fix/shipping-tax-rate-saving
```
