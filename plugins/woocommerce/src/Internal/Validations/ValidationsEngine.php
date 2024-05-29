<?php

namespace Automattic\WooCommerce\Internal\Validations;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

/**
 * Validations.
 */
class ValidationsEngine implements RegisterHooksInterface {

	use AccessiblePrivateMethods;

	/**
	 * Register hooks.
	 */
	public function register() {
		self::add_action( 'init', array( $this, 'add_endpoint' ), 0 );
		self::add_filter( 'query_vars', array( $this, 'handle_query_vars' ), 0 );
		self::add_action( 'parse_request', array( $this, 'handle_parse_request' ), 0 );
		self::add_action('admin_enqueue_scripts', array( $this, 'add_script_modules' ), 0 );
	}

	/**
	 * Add script modules.
	 */
	private function add_script_modules() {
		wp_register_script_module( '@woocommerce/validations', 'http://test.local/?wc-validation-schema=test' );
		wp_enqueue_script_module( '@woocommerce/validations' );
	}

	/**
	 * Handle the "init" action, add rewrite rules for the "wc/file" endpoint.
	 */
	public static function add_endpoint() {
		add_rewrite_rule( '^wc/validation/(.+)$', 'index.php?wc-validation-schema=$matches[1]', 'top' );
	}

	/**
	 * Handle the "query_vars" action, add the "wc-validation-schema" variable for the "wc/validations/<schema>" endpoint.
	 *
	 * @param array $vars The original query variables.
	 * @return array The updated query variables.
	 */
	private function handle_query_vars( $vars ) {
		$vars[] = 'wc-validation-schema';
		return $vars;
	}

	/**
	 * Handle the "parse_request" action for the "wc/validations" endpoint.
	 *
	 * If the request is not for "/wc/validations/<schema>" or "index.php?wc-validation-schema=filename",
	 * it returns without doing anything. Otherwise, it will serve the contents of the file with the provided name
	 * if it exists, is public and has not expired; or will return a "Not found" status otherwise.
	 *
	 * The file will be served with a content type header of "text/html".
	 */
	private function handle_parse_request() {
		global $wp;

		// phpcs:ignore WordPress.Security
		$query_arg = wp_unslash( $_GET['wc-validation-schema'] ?? null );
		if ( ! is_null( $query_arg ) ) {
			$wp->query_vars['wc-validation-schema'] = $query_arg;
		}

		if ( is_null( $wp->query_vars['wc-validation-schema'] ?? null ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? null ) ) {
			status_header( 405 );
			exit();
		}

		header('Content-type: application/javascript');

		$this->serve_file_contents( $wp->query_vars['wc-validation-schema'] );
	}

	/**
	 * Core method to serve the contents of a transient file.
	 *
	 * @param string $file_name Transient file id or filename.
	 */
	private function serve_file_contents( $schema ) {
		$script = <<<JS
			import { z } from "https://deno.land/x/zod/mod.ts";

			const validate = () => {
				console.log('validating');
			};

			window.wc = window.wc || {};
			window.wc.validation = {
				validate: () => console.log('validating')
			};
		JS;

		echo $script;
		exit();
	}
}
