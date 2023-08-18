# GitHub Workflows Example

The QIT GitHub Workflows are examples of integrating the Quality Insights Toolkit into GitHub. You can delegate tests to the QIT and integrate it as part of your PR approval process, when a release is created, and more.

For more information on how [GitHub Actions](https://docs.github.com/en/actions) work, please see the official GitHub documentation.

The examples below can be tweaked based on your needs, and use a fictional `woocommerce-product-feeds` extension to run the tests against. There's a few [GitHub Secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets) that need to be configured for this flow (feel free to rename these to whatever makes the most sense for you and your team):

- `PARTNER_USER`: Your WooCommerce.com username.
- `PARTNER_SECRET`: Your [WordPress Application Password](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/).

## Activation test example

```yaml
name: QIT Activation Test
on:
  workflow_dispatch:
  pull_request:
permissions:
  pull-requests: write
jobs:
  qit_activation:
    name: QIT Activation
    runs-on: ubuntu-20.04
    env:
      NO_COLOR: 1
      QIT_DISABLE_ONBOARDING: yes
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
      - name: Build your plugin zip (Example)
        run: zip -r my-extension.zip my-extension
      - name: Install QIT via composer
        run: composer require woocommerce/qit-cli
      - name: Add Partner
        run: ./vendor/bin/qit partner:add --user='${{ secrets.PARTNER_USER }}' --application_password='${{ secrets.PARTNER_SECRET }}'
      - name: Run Activation Test
        id: run-activation-test
        run: ./vendor/bin/qit run:activation my-extension --zip=my-extension.zip --wait > result.txt
      - uses: marocchino/sticky-pull-request-comment@v2
        if: failure()
        with:
          header: QIT Activation Result
          recreate: true
          path: result.txt
```

## Security test example

```yaml
name: QIT Security Test
on:
  workflow_dispatch:
  pull_request:
permissions:
  pull-requests: write
jobs:
  qit_security:
    name: QIT Security Test
    runs-on: ubuntu-20.04
    env:
      NO_COLOR: 1
      QIT_DISABLE_ONBOARDING: yes
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
      - name: Build your plugin zip (Example)
        run: zip -r my-extension.zip my-extension
      - name: Install QIT via composer
        run: composer require woocommerce/qit-cli
      - name: Add Partner
        run: ./vendor/bin/qit partner:add --user='${{ secrets.PARTNER_USER }}' --application_password='${{ secrets.PARTNER_SECRET }}'
      - name: Run Activation Test
        id: run-activation-test
        run: ./vendor/bin/qit run:security my-extension --zip=my-extension.zip --wait > result.txt
      - uses: marocchino/sticky-pull-request-comment@v2
        if: failure()
        with:
          header: QIT Security Result
          recreate: true
          path: result.txt
```

## Notes

### Required permissions

Depending on how your repository is set up, you may need to adjust the permissions step to the following:

```yaml
permissions:
  contents: read
  pull-requests: write
```

### Verify the zip is built correctly

If you see any failures in the pipeline, such as an activation test that fails without a result, it could be due to the way that the GitHub action is building the zip file. Double check that the zip file created by the workflow doesn't add in an extra folder to the file structure. To verify that the zip is valid to be used by QIT, you can test uploading it in the WordPress admin screen under `Plugins > Add new` and test uploading and activating the zip file. If it works, then it will work with QIT.
