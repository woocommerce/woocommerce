# Viewing test results

To view the tests that were created and their results, navigate to the `All Tests` page under the `Quality Insights` menu:

![all-tests-menu](_media/all-tests-menu.png ":size=40%")

In the table on this page, all of the tests that you created will be shown in the list, starting with the most recent:

![all-tests-list](_media/all-tests-list.png)

## Filtering results

You can filter the test results shown in the table by selecting one of the three available filters at the top of the page:

![view-test-filters](_media/view-test-filters.png)

- Product: Filter by the product the tests were ran against.
- WooCommerce Release: Filter by the WooCommerce release version the tests were ran against.
- Test Types: Filter by the test type that was ran, such as e2e or activation.

You can also search the tests by providing keywords such as the product, the version, the test type, status or release:

![search-tests](_media/search-tests.png)

## Viewing test logs

After a test run completes, you'll be able to view a variety of reports depending on the test type, and you can also share the link to the test results:

> ![view-icon](_media/view-icon.png) Click this icon to view the test results report.

> ![share-icon](_media/share-icon.png) Click this icon to copy a link to the test results to your clipboard to share with others.

### End-to-end tests

#### Successful runs

When an end-to-end test passes, you'll be able to view the report and see the tests that were ran by clicking on the view icon:

![e2e-success-log](_media/e2e-success-log.png)

This will open a modal that you can scroll through and see the results of each test that was ran:

![e2e-results-modal](_media/e2e-results-modal.png)

#### Failed test runs

If an end-to-end test run fails, an Allure test report will be generated. Click on the view icon to see the full details of the Allure test report:

![failed-e2e-test](_media/failed-e2e.png)

### Security, Activation, and PHPStan tests

To view the test logs for these test types, click on view icon in the table for the extension test results you'd like to view:

![non-e2e-report-link](_media/non-e2e-report-link.png)

This will open a modal where you can view the test results. For example, a failed Security test would show the following in the modal:

![security-test-result](_media/security-test-result.png)

You'll also be able to view a log and share the result for successful tests as well:

![success-phpstan](_media/success-phpshan.png)

![success-phpstan-modal](_media/success-phpshan-modal.png)
