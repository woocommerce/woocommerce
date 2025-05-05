<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
/**
 * Throw exception if anything fails.
 */
set_error_handler( // phpcs:ignore -- This file is not used within WordPress environment.
	function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}
);

$mailpoet_root_dir = dirname( __DIR__, 4 );

$email_editor_php_dir = dirname( __DIR__, 1 );

$php_stan_dir = "$mailpoet_root_dir/mailpoet/tasks/phpstan";
$php_stan_bin = "$php_stan_dir/vendor/bin/phpstan";

$email_editor_custom_config = "$php_stan_dir/email-editor-phpstan.neon";


$extra_agr_php_version = '';
if ( $argc > 1 && isset( $argv[1] ) && stripos( $argv[1], 'php-version' ) !== false ) {
	$raw_argv              = explode( '=', escapeshellcmd( $argv[1] ) );
	$value                 = $raw_argv[1];
	$extra_agr_php_version = "ANALYSIS_PHP_VERSION=$value ";
}

$commands = array(
	"cd $php_stan_dir && ", // we run commands from the PHPStan dir because we save MailPoet-specific configuration in it.
	"$extra_agr_php_version",
	'php -d memory_limit=-1 ',
	"$php_stan_bin analyse ",
	"-c $email_editor_custom_config ",
	"$email_editor_php_dir/src ",
	"$email_editor_php_dir/tests/integration ",
	"$email_editor_php_dir/tests/unit ",
);

$all_commands = implode( ' ', $commands );

echo "[run-phpstan] Running command: $all_commands \n";  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This file is not used within WordPress environment.

$result_code = 0;
passthru( $all_commands, $result_code );  // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_passthru -- This file is not used within WordPress environment.
exit( (int) $result_code );
