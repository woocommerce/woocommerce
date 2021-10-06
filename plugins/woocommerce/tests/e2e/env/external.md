# Using an External Container for End to End Testing

This document provides general instructions for using `@woocommerce/e2e-environment` with your hosting container. 

## Prerequisites

Complete the [setup instructions](./README.md) in each project/repository.

## Initialization Requirements

The test sequencer uses a `ready` page to determine that the testing environment is ready for testing. It will wait up to 5 minutes for this page to be created. In your initialization script use

```
wp post create --post_type=page --post_status=publish --post_title='Ready' --post_content='E2E-tests.'
```

### Project Initialization

Each project will have its own begin test state and initialization script. For example, a project might start testing expecting that the [sample products](https://github.com/woocommerce/woocommerce/tree/trunk/sample-data) have already been imported. Below is the WP CLI equivalent initialization script for WooCommerce Core E2E testing:


```
wp core install --url=http://localhost:8084 --admin_user=admin --admin_password=password --admin_email=wooadmin@example.org
wp plugin activate woocommerce
wp theme install twentynineteen --activate
wp user create customer customer@woocommercecoree2etestsuite.com \
	--user_pass=password \
	--role=subscriber \
	--first_name='Jane' \
	--last_name='Smith'
```

### Test Sequencer Setup

The test sequencer needs to know the particulars of your test install to run the tests. The sequencer reads these settings from `/tests/e2e/config/default.json`.

- The `customer` entry is not required by the sequencer but is required for the core test suite.
- The `url` value must match the URL of your testing container.

```
{
  "url": "http://localhost:8084/",
  "users": {
    "admin": {
      "username": "admin",
      "password": "password"
    },
    "customer": {
      "username": "customer",
      "password": "password"
    }
  }
}
```

### Travis CI

Add the following to the appropriate sections of your `.travis.yml` config file.

```yaml
version: ~> 1.0

  include:
    - name: "Core E2E Tests"
    php: 7.4
    env: WP_VERSION=latest WP_MULTISITE=0 RUN_E2E=1

....

script:
  - npm install jest --global
# add your initialization script here
  - npx wc-e2e test:e2e

....

after_script:
# add script to shut down your test container
```

Use `[[ ${RUN_E2E} == 1 ]]` in your Travis related bash scripts to test whether it is an E2E test run.

