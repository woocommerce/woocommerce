# Scripting

QIT CLI allows you to create robust scripts that can optimize your development workflow. Here is an example of a bash script used for authentication and running tests against a development build.

## Directory Structure

For this example, we will assume the following directory structure. This can be in the same folder where you develop your plugin or in its own directory:

- .env
- bin/qit.sh
- vendor/bin/qit
- build/extension.zip _(Assuming this is created by `npm run build`)_

## Environment Variables (.env)

Create a `.env` file in the root directory of your project and add your QIT user and application password:

```bash
QIT_USER=foo
QIT_APP_PASS=bar
```

## Bash Script (bin/qit.sh)

This script authenticates the QIT_USER and then runs security tests against the extension build. If the 'partner:remove' command is not available, it adds a partner using `QIT_USER` and `QIT_APP_PASSWORD`. For more information on how authentication works with QIT, see our [documentation around authentication](https://woocommerce.github.io/qit-documentation/#/authenticating).

```bash
#!/bin/bash
set -x # Verbose mode.

# Check if QIT_USER and QIT_APP_PASSWORD are set and not empty
if [[ -z "${QIT_USER}" ]] || [[ -z "${QIT_APP_PASSWORD}" ]]; then
    echo "QIT_USER or QIT_APP_PASSWORD environment variables are not set or empty. Please set them before running the script."
    exit 1
fi

# When QIT is run for the first time, it will prompt for onboarding. This will disable that prompt.
export QIT_DISABLE_ONBOARDING=yes

# If QIT_BINARY is not set, default to ./vendor/bin/qit
QIT_BINARY=${QIT_BINARY:-./vendor/bin/qit}

# Check if 'partner:remove' command is in the list of available commands
if ! $QIT_BINARY list | grep -q 'partner:remove'; then
    echo "Adding partner with QIT_USER and QIT_APP_PASSWORD..."
    $QIT_BINARY partner:add --user="${QIT_USER}" --application_password="${QIT_APP_PASSWORD}"
    if [ $? -ne 0 ]; then
        echo "Failed to add partner. Exiting with status 1."
        exit 1
    fi
fi

# Run the security command
echo "Running security command..."
$QIT_BINARY run:security my-extension --zip=./../build/extension.zip --wait
if [ $? -ne 0 ]; then
    echo "Failed to run security command. Exiting with status 1."
    exit 1
fi
```

## Script Runner (Choose between NPM, Composer, Make)

Script runners can be used to execute our bash script `qit.sh`. You can choose the script runner that best suits your needs. Below you can find some examples we've put together for NPM, Composer, and Make.

The **build** command in this script is just an example, and should be modified to fit your actual build process that generates the plugin zip that can be installed in a WordPress site.

### NPM

Usage: `npm run qit-security`

<details>
<summary>package.json</summary>

```json
{
  "name": "Project",
  "version": "1.0.0",
  "scripts": {
    "qit-security": "npm run build && dotenv -e .env -- bash ./bin/qit.sh",
    "build": "zip -r build/extension.zip my-extension"
  },
  "devDependencies": {
    "dotenv-cli": "^7.2.1"
  }
}
```

</details>

### Composer

Usage: `composer run qit-security`

<details>
<summary>composer.json</summary>

```json
{
  "scripts": {
    "build": "zip -r build/extension.zip my-extension",
    "qit-security": "export $(cat .env | xargs) && composer run-script build && ./bin/qit.sh"
  }
}
```

</details>

### Makefile

Usage: `make qit-security`

<details>
<summary>Makefile</summary>

```shell
include ./.env
export

build:
        zip -r build/extension.zip my-extension

qit-security: build
        bash ./bin/qit.sh
```

</details>
