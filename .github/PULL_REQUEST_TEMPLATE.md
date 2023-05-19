### Submission Review Guidelines:

- I have followed the [WooCommerce Contributing Guidelines](https://github.com/woocommerce/woocommerce/blob/trunk/.github/CONTRIBUTING.md) and the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/).
- I have checked to ensure there aren't other open [Pull Requests](https://github.com/woocommerce/woocommerce/pulls) for the same update/change.
- I have reviewed my code for [security best practices](https://developer.wordpress.org/apis/security/). 
- Following the above guidelines will result in quick merges and clear and detailed feedback when appropriate.

<!-- You can erase any parts of this template not applicable to your Pull Request. -->

### Changes proposed in this Pull Request:

Moved the WC Logger code into the "wc_caught_exception" function in the wc-deprecated-functions.php file.
So that the users get access to these logs anywhere the "wc_caught_exception" is being used.

<!-- Begin testing instructions -->

### How to test the changes in this Pull Request:

<!-- Include detailed instructions on how these changes can be tested. Review and follow the guide for how to write high-quality testing instructions. -->

Using the [WooCommerce Testing Instructions Guide](https://github.com/woocommerce/woocommerce/wiki/Writing-high-quality-testing-instructions), include your detailed testing instructions:

1. Manually create a subscription
2. Modify the _schedule_start postmeta of the subscription and change it's value into a bool
3. This should trigger the error
4. Check the log in the WP Admin Dashboard
5. The admin dashboard should log the exact exception causing the Invalid Subscription error.

<!-- End testing instructions -->