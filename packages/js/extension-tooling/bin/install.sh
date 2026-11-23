#!/usr/bin/env bash

set -euo pipefail

if [[ ${RUN_UNIT_TESTS:-} == 0 ]]; then
	exit 0
fi

if [ $# -lt 3 ]; then
	echo "Usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [wc-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
WC_VERSION=${6-latest}

TESTS_LIB_DIR="${WP_TESTS_DIR-/tmp/wordpress-tests-lib}"
TESTS_WP_DIR="${WP_CORE_DIR-/tmp/wordpress}"
TESTS_WC_DIR="${WC_CORE_DIR-/tmp/woocommerce}"

# These paths must keep pointing to the same directories after changing cwd.
for directory in TESTS_LIB_DIR TESTS_WP_DIR TESTS_WC_DIR; do
	case "${!directory}" in
		'')
			echo 'WP_TESTS_DIR, WP_CORE_DIR and WC_CORE_DIR must be nonempty when set.' >&2
			exit 1
			;;
		/*) ;;
		*) printf -v "$directory" '%s/%s' "$PWD" "${!directory}" ;;
	esac
done

TESTS_DIR="$TESTS_WP_DIR/.."

printf '%s\n' "$TESTS_LIB_DIR" "$TESTS_WP_DIR" "$TESTS_WC_DIR" "$TESTS_DIR"

downloads=$(mktemp -d)
trap 'rm -rf -- "$downloads"' EXIT

download() {
	if command -v curl >/dev/null; then
		curl --fail --silent --show-error --proto '=https' "$1" > "$2"
	elif command -v wget >/dev/null; then
		wget -nv -O "$2" "$1"
	else
		echo 'Downloads require curl or wget.' >&2
		return 1
	fi
}

if [[ -z "$WC_VERSION" || "$WC_VERSION" == 'latest' ]]; then
	echo "WooCommerce version not provided. Fetching latest tag from org..."
	download https://api.wordpress.org/plugins/info/1.0/woocommerce.json "$downloads/woocommerce.json"
	# shellcheck disable=SC2016 # The PHP process reads its own argument.
	WC_VERSION=$(php -r '
		$response = json_decode(file_get_contents($argv[1]), true);
		$version = $response["version"] ?? null;
		if (!is_string($version)) { exit(1); }
		echo $version;
	' "$downloads/woocommerce.json") || {
		echo 'Could not read the latest WooCommerce version from WordPress.org.' >&2
		exit 1
	}
	echo "WooCommerce version fetched: $WC_VERSION"
fi

# Install exactly the requested ref: a tag (x.y.z, x.y.z-rc.N, nightly) or the trunk branch.
# Never fall back to another version, so a matrix row always tests what its name says.
if [[ $WC_VERSION == 'trunk' ]]; then
	latestTag=trunk
elif [[ -n $WC_VERSION ]] && git ls-remote --exit-code --refs --tags https://github.com/woocommerce/woocommerce "refs/tags/$WC_VERSION" > /dev/null; then
	latestTag=$WC_VERSION
else
	echo "Error: WooCommerce version '$WC_VERSION' has no matching tag in woocommerce/woocommerce." >&2
	exit 1
fi

# Only need to check normal versions. Betas and rcs are not supported.
if [[ $WP_VERSION =~ ^[0-9]+(\.[0-9]+)*$ ]]; then
	WP_TESTS_TAG="tags/$WP_VERSION"
	WP_TARBALL="https://wordpress.org/wordpress-$WP_VERSION.tar.gz"
elif [[ $WP_VERSION == 'nightly' ]]; then
	WP_TESTS_TAG="trunk"
elif [[ $WP_VERSION == 'latest' ]]; then
	download https://api.wordpress.org/core/version-check/1.7/ "$downloads/wordpress.json"
	# shellcheck disable=SC2016 # The PHP process reads its own argument.
	LATEST_VERSION=$(php -r '
		$response = json_decode(file_get_contents($argv[1]), true);
		$version = $response["offers"][0]["version"] ?? null;
		if (!is_string($version)) { exit(1); }
		echo $version;
	' "$downloads/wordpress.json") || {
		echo 'Could not read the latest WordPress version from WordPress.org.' >&2
		exit 1
	}
	if [[ ! "$LATEST_VERSION" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
		echo "Latest WordPress version could not be found" >&2
		exit 1
	fi
	WP_TESTS_TAG="tags/$LATEST_VERSION"
	WP_TARBALL="https://wordpress.org/wordpress-$LATEST_VERSION.tar.gz"
else
	echo "Invalid WordPress version: $WP_VERSION (expected latest, nightly or a numeric release)" >&2
	exit 1
fi

install_wp() {
	if [ -d "$TESTS_WP_DIR" ]; then
		rm -rf -- "$TESTS_WP_DIR"
	fi

	mkdir -p -- "$TESTS_WP_DIR"

	if [[ $WP_VERSION == 'nightly' ]]; then
		download https://wordpress.org/nightly-builds/wordpress-latest.zip "$downloads/wordpress.zip"
		unzip -q "$downloads/wordpress.zip" -d "$downloads"
		cp -a "$downloads/wordpress/." "$TESTS_WP_DIR"
	else
		download "$WP_TARBALL" "$downloads/wordpress.tar.gz"
		tar --strip-components=1 -zxmf "$downloads/wordpress.tar.gz" -C "$TESTS_WP_DIR"
	fi
	mkdir -p "$TESTS_WP_DIR/wp-content/uploads"
}

# Escapes a value for a PHP single-quoted string (backslash, quote), then for a sed `s|...|...|` replacement (backslash, &, |).
sed_php_value() {
	printf '%s' "$1" | sed -e 's/\\/\\\\/g' -e "s/'/\\\\'/g" -e 's/[\\&|]/\\&/g'
}

install_test_suite() {
	if [ -d "$TESTS_LIB_DIR" ]; then
		rm -rf -- "$TESTS_LIB_DIR"
	fi

	local source="https://develop.svn.wordpress.org/$WP_TESTS_TAG"
	mkdir -p -- "$TESTS_LIB_DIR"
	svn co --quiet --ignore-externals "$source/tests/phpunit/includes/" "$TESTS_LIB_DIR/includes"
	svn co --quiet --ignore-externals "$source/tests/phpunit/data/" "$TESTS_LIB_DIR/data"
	download "$source/wp-tests-config-sample.php" "$downloads/wp-tests-config-sample.php"
	sed -e "s|dirname( __FILE__ ) . '/src/'|'$(sed_php_value "$TESTS_WP_DIR")/'|" \
		-e "/DB_NAME/s|youremptytestdbnamehere|$(sed_php_value "$DB_NAME")|" \
		-e "/DB_USER/s|yourusernamehere|$(sed_php_value "$DB_USER")|" \
		-e "/DB_PASSWORD/s|yourpasswordhere|$(sed_php_value "$DB_PASS")|" \
		-e "/DB_HOST/s|localhost|$(sed_php_value "$DB_HOST")|" \
		"$downloads/wp-tests-config-sample.php" > "$downloads/wp-tests-config.php"
}

install_db() {
	local DB_HOSTNAME DB_SOCK_OR_PORT
	if [[ "$DB_HOST" == :* ]]; then
		# It's a socket, e.g. ':/path/to/socket'.
		DB_HOSTNAME=""
		DB_SOCK_OR_PORT=${DB_HOST#:}
	elif [[ "$DB_HOST" == *:* ]]; then
		DB_HOSTNAME=${DB_HOST%:*}
		DB_SOCK_OR_PORT=${DB_HOST##*:}
	else
		DB_HOSTNAME=$DB_HOST
		DB_SOCK_OR_PORT=""
	fi

	local mysql_args=(--user="$DB_USER" --password="$DB_PASS")

	if [[ -n $DB_SOCK_OR_PORT && ! $DB_SOCK_OR_PORT =~ ^[0-9]+$ ]]; then
		mysql_args+=(--socket="$DB_SOCK_OR_PORT")
	elif [[ -n $DB_HOSTNAME && -n $DB_SOCK_OR_PORT ]]; then
		mysql_args+=(--host="$DB_HOSTNAME" --port="$DB_SOCK_OR_PORT" --protocol=tcp)
	elif [[ -n $DB_HOSTNAME ]]; then
		mysql_args+=(--host="$DB_HOSTNAME" --protocol=tcp)
	fi

	printf "drop database if exists \`%s\`\n" "${DB_NAME//\`/\`\`}" | mysql "${mysql_args[@]}"
	mysqladmin "${mysql_args[@]}" create "$DB_NAME"

	# Get the current PHP version
	php_version=$(php -r 'echo PHP_VERSION;')

	# For PHP version 7.0, change the default mysql authentication plugin to "mysql_native_password"
	# as "caching_sha2_password" is not supported
	# This is not needed if using MySQL server from LocalWP (and it will fail)
	if [[ "$php_version" == *"7.0"* ]]; then

		# Update mysql configuration file.
		echo "[mysqld]" | sudo tee -a /etc/mysql/my.cnf
		echo "default_authentication_plugin=mysql_native_password" | sudo tee -a /etc/mysql/my.cnf

		echo "Restarting MYSQL server..."
		sudo systemctl restart mysql.service

		# Update root user.
		echo "ALTER USER '${DB_USER}'@'$DB_HOSTNAME' IDENTIFIED WITH mysql_native_password BY '${DB_PASS}'" | mysql --user="${DB_USER}" --password="${DB_PASS}"

		# Debug statement
		# echo "SELECT @@GLOBAL.default_authentication_plugin" | mysql --user=${DB_USER} --password=${DB_PASS}
	fi
}

install_woocommerce() {

	if [ -d "$TESTS_WC_DIR" ]; then
		rm -rf -- "$TESTS_WC_DIR"
	fi

	cd "$TESTS_DIR"

	if [ -e wc-tmp ]; then
		rm -rf wc-tmp
	fi

	if [ -d wc-tmp-monorepo ]; then
		rm -rf wc-tmp-monorepo
	fi

	git clone --depth=1 --branch="$latestTag" https://github.com/woocommerce/woocommerce wc-tmp-monorepo
	ln -s "$TESTS_DIR/wc-tmp-monorepo/plugins/woocommerce" "$TESTS_DIR/wc-tmp"
	cd wc-tmp-monorepo/plugins/woocommerce

	composer install --no-dev --no-scripts --optimize-autoloader

	# Load WooCommerce test helpers without installing its development dependencies.
	composer dump-autoload --dev --no-scripts --optimize

	php bin/generate-feature-config.php

	cd "$TESTS_DIR"
	mv wc-tmp "$TESTS_WC_DIR"
}

print_versions() {
	local wp_version wc_version
	# shellcheck disable=SC2016 # The PHP process reads its own argument and version.
	wp_version=$(php -r 'require $argv[1]; echo $wp_version;' "$TESTS_WP_DIR/wp-includes/version.php")
	wc_version=$(awk '/^[[:space:]]*\*[[:space:]]*Version:/{print $NF; exit}' "$TESTS_WC_DIR/woocommerce.php")
	echo "Installed WordPress: $wp_version (requested: $WP_VERSION)"
	echo "Installed WooCommerce: $wc_version (requested: $WC_VERSION, ref: $latestTag)"
}

# A failed WordPress extraction can otherwise leave a stale completion marker.
rm -f -- "$TESTS_LIB_DIR/wp-tests-config.php"
install_wp
install_test_suite
install_db
install_woocommerce
print_versions
# The wp-env runner uses this file to detect a complete setup.
mv "$downloads/wp-tests-config.php" "$TESTS_LIB_DIR/wp-tests-config.php"
