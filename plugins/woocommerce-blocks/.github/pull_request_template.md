<!-- Start by describing the changes made in this Pull Request, and the reason for such changes. -->

<!-- Reference any related issues or PRs here -->

Fixes #

<!-- Don't forget to update the title with something descriptive. -->
<!-- If you can, add the appropriate labels -->

#### Accessibility

<!-- If you've changed or added any interactions, check off the appropriate items below. You can delete any that don't apply. Use this space to elaborate on anything if needed. -->

- [ ] I've tested using only a keyboard (no mouse)
- [ ] I've tested using a screen reader
- [ ] All animations respect [`prefers-reduced-motion`](https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-motion)
- [ ] All text has [at least a 4.5 color contrast with its background](https://webaim.org/resources/contrastchecker/)

#### Other Checks

- [ ] This PR has either a `[type]` label or a `[skip-changelog]` label.
- [ ] This PR adds/removes a feature flag & I've updated [this doc](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/internal-developers/blocks/feature-flags-and-experimental-interfaces.md).
- [ ] This PR adds/removes an experimental interfaces and I've updated [this doc](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/internal-developers/blocks/feature-flags-and-experimental-interfaces.md).
- [ ] I tagged two reviewers because this PR makes queries to the database or I think it might have some security impact.

### Screenshots

<!-- If your change has a visual component, add a screenshot here. A "before" screenshot would also be helpful. -->

| Before | After |
| ------ | ----- |
|        |       |

### Testing

#### Automated Tests
* [ ] Changes in this PR are covered by Automated Tests.
  * [ ] Unit tests
  * [ ] E2E tests

#### User Facing Testing

<!-- Write these steps from the perspective of a "user" (merchant) familiar with WooCommerce. No need to spell out the steps for common setup scenarios (eg. "Create a product"), but do be specific about the thing being tested. Include screenshots demonstrating expectations where that will be helpful. -->

1.
2.
3.

* [ ] Do not include in the Testing Notes <!-- Check this box if this PR can't be tested by users (ie: it doesn't include user-facing changes or it can't be tested without manually modifying the code). -->

### WooCommerce Visibility

<!-- Check this [this doc](../docs/blocks/feature-flags-and-experimental-interfaces.md) to see if the change is visible in WC core or not (part of the feature plugin or experimental)-->

* [ ] WooCommerce Core
* [ ] Feature plugin
* [ ] Experimental

### Performance Impact

<!-- Please document any known performance impact (positive or negative) here. If negative, provide some rationale for why this is an okay tradeoff or how this will be addressed. -->

### Changelog

> Add suggested changelog entry here.
