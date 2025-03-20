---
post_title: WooCommerce Git flow
menu_title: WooCommerce Git flow
tags: reference
---

For core development, we use the following structure and flow.

**1.** Five weeks before the release we tag an alpha. We publish it for community testing, but this milestone doesn't imply a feature freeze. Issues found in the alpha are fixed with pull requests targetting `trunk` as usual.

![Git Flow WooCommerce uses for core development - tagging alpha](https://developer.woocommerce.com/wp-content/uploads/sites/2/2025/02/woo-git-flow-1.png)

**2.** Three weeks before the release we create a `release/x.y` branch off trunk and declare feature freeze: no additional pull requests will be accepted for the `x.y` release. We also tag a beta from the `release/x.y` branch.

![Git Flow WooCommerce uses for core development - tagging beta](https://developer.woocommerce.com/wp-content/uploads/sites/2/2025/02/woo-git-flow-2.png)

**3.** If issues are found in the beta they are fixed with pull requests targeting the `release/x.y` branch.

![Git Flow WooCommerce uses for core development - fixing issues in the beta](https://developer.woocommerce.com/wp-content/uploads/sites/2/2025/02/woo-git-flow-3.png)

**4.** At the end of every week during the beta cycle (at least, optionally more often) the release branch is merged back to `trunk` so that any fixed applied to the beta are present in future releases too:

![Git Flow WooCommerce uses for core development - merging beta to trunk](https://developer.woocommerce.com/wp-content/uploads/sites/2/2025/02/woo-git-flow-4.png)

**5.** One week before the release we tag a Release Candidate. As with the beta, fixes for further issues discovered are handled with pull requests to the release branch.

![Git Flow WooCommerce uses for core development - tagging RC](https://developer.woocommerce.com/wp-content/uploads/sites/2/2025/02/woo-git-flow-5.png)

**6.** Finally, on the release day the release branch is merged one more time to `trunk`.

## Point releases

Point releases are those tagged `x.y.z` where `z` is not zero. These are handled similarly to the beta and release candidate of the main release:

* Pull requests are merged to the `release/x.y` branch (no dedicated branch is created).
* The release branch is merged back to `trunk` when the development for a point release is in progress, and also when the release happens.

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
