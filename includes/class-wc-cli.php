<?php

/**
 * Manage WooCommerce from CLI.
 *
 * @class    WC_CLI
 * @version  2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */
class WC_CLI extends WP_CLI_Command {
}

WP_CLI::add_command( 'wc',                  'WC_CLI' );
WP_CLI::add_command( 'wc coupon',           'WC_CLI_Coupon' );
WP_CLI::add_command( 'wc customer',         'WC_CLI_Customer' );
WP_CLI::add_command( 'wc order',            'WC_CLI_Order' );
WP_CLI::add_command( 'wc product',          'WC_CLI_Product' );
WP_CLI::add_command( 'wc product category', 'WC_CLI_Product_Category' );
WP_CLI::add_command( 'wc report',           'WC_CLI_Report' );
WP_CLI::add_command( 'wc tax',              'WC_CLI_Tax' );
WP_CLI::add_command( 'wc tool',             'WC_CLI_Tool' );
