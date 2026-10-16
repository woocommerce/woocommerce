#!/bin/sh

set -eu

# The wp-env database is disposable, so avoid syncing the redo log after every
# transaction while retaining InnoDB's normal SQL and rollback behavior.
wp db query "SET GLOBAL innodb_flush_log_at_trx_commit=2" >/dev/null

exec php -d opcache.enable_cli=1 vendor/bin/phpunit "$@"
