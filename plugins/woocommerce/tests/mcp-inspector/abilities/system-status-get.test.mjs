import assert from 'node:assert/strict';
import { describe, test } from 'node:test';
import { execute } from '../lib/abilities.mjs';

const ABILITY = 'woocommerce/system-status-get';

describe( ABILITY, () => {
	test( 'returns the store status without server details', async () => {
		const { system_status: status } = await execute( ABILITY, {} );

		assert.deepEqual( Object.keys( status ).sort(), [
			'active_plugins',
			'dropins',
			'enabled_features',
			'environment',
			'inactive_plugins',
			'mu_plugins',
			'order_storage',
			'pages',
			'post_type_counts',
			'security',
			'theme',
			'woocommerce_db_version',
			'woocommerce_version',
		] );
		assert.deepEqual( Object.keys( status.environment ).sort(), [
			'external_object_cache',
			'log_directory_writable',
			'max_upload_size',
			'memory_limit',
			'wp_cron',
			'wp_debug_mode',
		] );
	} );

	test( 'lists the active plugins with their name, version and author only', async () => {
		const { system_status: status } = await execute( ABILITY, {} );
		const woocommerce = status.active_plugins.find(
			( { plugin } ) => plugin === 'woocommerce/woocommerce.php'
		);

		assert.deepEqual( woocommerce, {
			plugin: 'woocommerce/woocommerce.php',
			name: 'WooCommerce',
			version: status.woocommerce_version,
			author: 'Automattic',
		} );
		assert.ok(
			status.active_plugins.some(
				( { plugin } ) => plugin === 'mcp-adapter/mcp-adapter.php'
			)
		);
	} );

	test( 'lists the store pages it checks', async () => {
		const { system_status: status } = await execute( ABILITY, {} );

		assert.deepEqual(
			status.pages.map( ( { name } ) => name ),
			[
				'Shop base',
				'Cart',
				'Checkout',
				'My account',
				'Terms and conditions',
			]
		);
	} );
} );
