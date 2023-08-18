# Authenticating

A vendor admin user is required to be able to view and execute tests using QIT.

## Dashboard

For the QIT dashboard:

- Log in to WooCommerce.com with your vendor account.
- Click on `Vendor Dashboard` button to be taken to your vendor dashboard, which can be found on the My Account page once you've logged in:

![go-to-dashboard](qit-dashboard/_media/go-to-dashboard.png)

- Don't see this button? You may not be the vendor admin on the account. Reach out to someone else in the organization (usually the person that handles uploading the extension for publishing) to see if they have access.

> Even if you don't have access in the UI, you'll still be able to leverage the CLI. Please see the section below for a guide on how to do this.

## CLI

In order to be able to use the CLI tool, you'll need to be a vendor admin that can create a [WordPress application password](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/) in order to authenticate. Follow the steps below to authenticate with the QIT CLI:

- [Download](https://github.com/woocommerce/qit-cli/releases/latest/) the latest version of QIT CLI and [Install it](qit-cli/getting-started#installing-qit)
- Depending on how you've installed the QIT CLI, run `./vendor/bin/qit partner:add`
- Follow the steps to generate an application password
- Enter the application password and username in the CLI

## Giving access to other developers to use the QIT

Sometimes you want to give access to other developers in your organization to run tests using the QIT, but you might not want to give them access to the WooCommerce.com account that can manage the extension in the marketplace, as it gives developers access they don't need, such as managing your extensions in the marketplace, etc.

Luckily, you can share with them the QIT Application Password, as these application passwords are restricted to only run and view test runs. They are special application passwords with limited access that can only run and view tests using QIT.

> Our roadmap includes plans to add the ability to create and revoke QIT-specific access tokens to make this particular workflow and use-case more manageable.
