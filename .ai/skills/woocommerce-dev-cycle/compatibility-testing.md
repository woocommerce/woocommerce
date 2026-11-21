# Compatibility Testing Across PHP and Database Versions

WooCommerce runs on every PHP version from its `Requires PHP` header up to the latest release, and on every MySQL and MariaDB version WordPress supports. The local test environment runs one PHP version and MariaDB, and CI adds a few PHP versions but still only MariaDB. Passing tests says nothing about MySQL, and little about PHP versions CI doesn't run.

## Table of Contents

- [When to check](#when-to-check)
- [Supported versions](#supported-versions)
- [PHP versions](#php-versions)
- [Database engines and versions](#database-engines-and-versions)
- [Known engine differences](#known-engine-differences)
- [Keep it proportionate](#keep-it-proportionate)
- [Reporting in the PR](#reporting-in-the-pr)

## When to check

Check across versions when a change:

- Adds or changes raw SQL, especially `LIKE`, `REGEXP`, comparisons that depend on collation or charset, `ORDER BY` with `LIMIT` on large tables, or statements that write many rows at once.
- Reads, rewrites or decodes serialized data stored in the database.
- Adds a database migration (`WC_Install::$db_updates`).
- Relies on PHP built-ins whose behavior, warnings or deprecations changed between versions, such as `unserialize()`, string and multibyte functions, or new syntax.

Pure PHP that goes through WordPress APIs (`$wpdb->prepare()`, `get_post_meta()`, `WP_Query`) rarely needs it.

## Supported versions

Read the current range instead of hard-coding it:

- PHP: `Requires PHP` in `plugins/woocommerce/readme.txt` and `plugins/woocommerce/woocommerce.php`, up to the latest PHP release.
- Database: [WordPress requirements](https://wordpress.org/about/requirements/), which cover MySQL and MariaDB. In practice, check at least MySQL 5.7 (the oldest still in wide use), MySQL 8.0 and 8.4, and a MariaDB LTS.

## PHP versions

To compare how a PHP construct behaves, run a small script outside the repository on each version's official image and compare the output:

```bash
for v in 7.4 8.0 8.1 8.2 8.3 8.4 8.5; do
	printf '%s: ' "$v"
	docker run --rm -v "$PWD/check.php:/check.php:ro" "php:$v-cli" php /check.php
done
```

Print a compact result per case (a type, a hash of the output, any deprecation messages) so differences stand out. Typical differences:

- Warning levels change: `unserialize()` errors became `E_WARNING` instead of `E_NOTICE` in PHP 8.3.
- Deprecations appear: dynamic properties in PHP 8.2, implicitly nullable parameter types in PHP 8.4.
- Syntax has a floor: typed properties and arrow functions need PHP 7.4, which is the minimum.

To run the unit tests on another PHP version, set it in the gitignored `plugins/woocommerce/.wp-env.test.override.json` and start the test environment again, which rebuilds it for the new version:

```json
{
	"phpVersion": "7.4"
}
```

```bash
pnpm --filter=@woocommerce/plugin-woocommerce env:test:start
```

Remove the override when you're done, so later test runs use the default version again.

## Database engines and versions

To check SQL across engines, run each server in a throwaway container:

```bash
docker run -d --rm --name compat-mysql57 --platform=linux/amd64 -p 127.0.0.1:33901:3306 -e MYSQL_ROOT_PASSWORD=x -e MYSQL_DATABASE=t mysql:5.7
docker run -d --rm --name compat-mysql80 -p 127.0.0.1:33902:3306 -e MYSQL_ROOT_PASSWORD=x -e MYSQL_DATABASE=t mysql:8.0
docker run -d --rm --name compat-mysql84 -p 127.0.0.1:33903:3306 -e MYSQL_ROOT_PASSWORD=x -e MYSQL_DATABASE=t mysql:8.4
docker run -d --rm --name compat-mariadb -p 127.0.0.1:33904:3306 -e MARIADB_ROOT_PASSWORD=x -e MARIADB_DATABASE=t mariadb:lts
```

MySQL 5.7 has no ARM image, so it needs `--platform=linux/amd64` on Apple silicon. Then, from a PHP script outside the repository that connects with `mysqli`:

1. Create the tables the code touches with WordPress's own schema (see `wp-admin/includes/schema.php`), using `utf8mb4` and `utf8mb4_unicode_520_ci` like a WordPress install.
2. Seed realistic data, including a realistic number of unrelated rows. Query plans depend on table size.
3. Run the SQL the code actually generates, escaped the way `$wpdb` does it: `$wpdb->esc_like()` is `addcslashes( $text, '_%\\' )`, and `%s` values go through `mysqli_real_escape_string()`. Better still, extract the code under test and run it against a small `$wpdb` stand-in, so the check can't drift from the implementation.
4. Compare results and `EXPLAIN` output (`type`, `key`, `rows`, `Extra`) on every server.

Stop the containers when you're done (`docker stop compat-mysql57 compat-mysql80 compat-mysql84 compat-mariadb`); `--rm` removes them.

## Known engine differences

| Difference | Where | What to do |
|------------|-------|------------|
| `REGEXP` stops reading at a NUL byte | MySQL 5.7 | Use `LIKE` when a value can contain binary data, then check in PHP. |
| `LIKE` and `=` are case- and accent-insensitive | `_ci` collations, the WordPress default | Treat SQL matches as candidates and decide in PHP when exact bytes matter. |
| Default `max_allowed_packet` is 4 MB, 16 MB or 64 MB | MySQL 5.7, MariaDB, MySQL 8.0+ (and hosts change it) | A statement larger than the limit fails and drops the connection. Only matters when a statement can carry a lot of data. |
| The optimizer picks different plans | All versions | Run `EXPLAIN` on each server; see the `woocommerce-performance` skill. |

## Keep it proportionate

- Don't accommodate configurations WordPress doesn't support, such as the `NO_BACKSLASH_ESCAPES` SQL mode.
- Don't add code for data sizes real stores don't reach. Check evidence first, such as typical row sizes or WooCommerce Tracker data on store sizes, and only guard against limits that real data can hit.
- When a check shows no difference, that's the result: say so in the PR instead of adding defensive code.

## Reporting in the PR

In "Testing that has already taken place", list what you checked and how, for example: "Ran the generated SQL on MySQL 5.7, 8.0 and 8.4 and MariaDB 10.6–12.x; results and plans match. Decode behavior is identical on PHP 7.4–8.5." If you didn't check a version that the change could affect, say so.
