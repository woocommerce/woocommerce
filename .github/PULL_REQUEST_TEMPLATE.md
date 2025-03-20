### Submission Review Guidelines:

-   I have followed the [WooCommerce Contributing Guidelines](https://github.com/woocommerce/woocommerce/blob/trunk/.github/CONTRIBUTING.md) and the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/).
-   I have checked to ensure there aren't other open [Pull Requests](https://github.com/woocommerce/woocommerce/pulls) for the same update/change.
-   I have reviewed my code for [security best practices](https://developer.wordpress.org/apis/security/).
-   Following the above guidelines will result in quick merges and clear and detailed feedback when appropriate.

<!-- You can erase any parts of this template not applicable to your Pull Request. -->

### Changes proposed in this Pull Request:

<!-- If necessary, indicate if this PR is part of a bigger feature. Add a label with the format `focus: name of the feature [team:name of the team]`. -->

<!-- Describe the changes made to this Pull Request and the reason for such changes. -->

<!-- For bug fixes: If known, please provide links to help with traceability and escape analysis. -->
<!-- Please include a link to the issue of the bug being fixed, if one doesn't exist please create it. -->
<!-- If the PR that introduced the bug is known, please also add its link below. -->

Closes # .

(For Bug Fixes) Bug introduced in PR # .

### Screenshots or screen recordings:

<!-- If this PR includes UI changes, please provide screenshots or a screen recording for clarity. -->
<!-- This section can be removed if not applicable. -->

| Before | After |
| ------ | ----- |
|        |       |


<!-- Begin testing instructions -->

### How to test the changes in this Pull Request:

<!-- Include detailed instructions on how these changes can be tested. Review and follow the guide for how to write high-quality testing instructions. -->

Using the [WooCommerce Testing Instructions Guide](https://github.com/woocommerce/woocommerce/wiki/Writing-high-quality-testing-instructions), include your detailed testing instructions:

1.
2.
3.

<!-- End testing instructions -->

### Testing that has already taken place:

<!-- Detail any testing that has already been conducted. -->
<!-- Include environment details such as hosting type, plugins, theme, store size, store age, and relevant settings. -->
<!-- Mention any analysis performed, such as assessing potential impacts on environment attributes and other plugins, performance profiling, or LLM/AI-based analysis. -->
<!-- Within the testing details you provide, please ensure that no sensitive information (such as API keys, passwords, user data, etc.) is included in this public pull request. -->

### Changelog entry

<!-- You can optionally choose to enter a changelog entry by checking the box below and supplying data. -->
<!-- It will trigger the 'Add changelog to PR' CI job to create and push the entry into the branch. -->

<!-- Due to org permissions, the job might fail for PRs crated from a fork under GitHub organizations. Possible solutions: -->
<!-- * Create entry manually with `pnpm --filter='@woocommerce/plugin-woocommerce' changelog add` and push it into the branch (replace `@woocommerce/plugin-woocommerce` with package name from nearest `package.json` file) -->
<!-- * Create entry from supplied PR data and push it automatically `pnpm utils changefile pr-number-here -o github-org-name-here` -->

-   [ ] Automatically create a changelog entry from the details below.

<!-- If no changelog entry is required for this PR, you can specify that below and provide a comment explaining why. This cannot be used if you selected the option to automatically create a changelog entry above. -->

-   [ ] This Pull Request does not require a changelog entry. (Comment required below)

<details>

<summary>Changelog Entry Details</summary>

#### Significance

<!-- Choose only one -->

-   [ ] Patch
-   [ ] Minor
-   [ ] Major

#### Type

<!-- Choose only one -->

-   [ ] Fix - Fixes an existing bug
-   [ ] Add - Adds functionality
-   [ ] Update - Update existing functionality
-   [ ] Dev - Development related task
-   [ ] Tweak - A minor adjustment to the codebase
-   [ ] Performance - Address performance issues
-   [ ] Enhancement - Improvement to existing functionality

#### Message <!-- Add a changelog message here -->

</details>

<details>

<summary>Changelog Entry Comment</summary>

#### Comment <!-- If your Pull Request doesn't require a changelog entry, a comment explaining why is required instead -->

</details>
