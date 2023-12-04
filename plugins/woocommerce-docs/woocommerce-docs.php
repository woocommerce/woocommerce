<?php
/**
 * Plugin Name: WooCommerce Docs
 * Plugin URI: https://woo.com/
 * Description: A plugin for consolidating Markdown documentation from remote sources.
 * Version: 1.0
 * Author: Automattic
 * Author URI: https://woo.com
 * Text Domain: woocommerce-docs
 * Domain Path: /i18n/languages/
 * Requires at least: 6.1
 * Requires PHP: 7.4
 *
 * @package WooCommerce
 */

$autoload = __DIR__ . '/vendor/autoload.php';


// Get around lint that complains upstream files autoloaded don't have file comments.
require $autoload;

define( 'WOOCOMMERCE_DOCS_ROOT_URL', plugin_dir_url( __FILE__ ) );
const WOOCOMMERCE_DOCS_PLUGIN_PATH = __DIR__;

// Require action-scheduler manually.
require_once __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

\WooCommerceDocs\App\Bootstrap::bootstrap();
