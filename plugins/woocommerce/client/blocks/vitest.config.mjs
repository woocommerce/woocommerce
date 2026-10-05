import { fileURLToPath } from 'node:url';
import { createTestConfig } from '../../../../packages/js/internal-js-tests/vitest.config.mjs';

export default createTestConfig(
	fileURLToPath( new URL( '.', import.meta.url ) ),
	{
		resolve: {
			alias: [
				{
					find: new RegExp( '@woocommerce/product-elements' ),
					replacement: new URL(
						'./assets/js/blocks/product-elements-blocks',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/icons' ),
					replacement: new URL( './assets/js/icons', import.meta.url )
						.pathname,
				},
				{
					find: new RegExp( '^@woocommerce/settings/(.*)$' ),
					replacement: new URL(
						'./packages/public-api/settings/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/settings$' ),
					replacement: new URL(
						'./packages/public-api/settings',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/blocks/(.*)$' ),
					replacement: new URL(
						'./assets/js/blocks/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/block-settings' ),
					replacement: new URL(
						'./assets/js/settings/blocks',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/editor-components(.*)$' ),
					replacement: new URL(
						'./assets/js/editor-components/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/blocks-registry/(.*)$' ),
					replacement: new URL(
						'./packages/public-api/blocks-registry/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/blocks-registry$' ),
					replacement: new URL(
						'./packages/public-api/blocks-registry',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/blocks-checkout/(.*)$' ),
					replacement: new URL(
						'./packages/public-api/blocks-checkout/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/blocks-checkout$' ),
					replacement: new URL(
						'./packages/public-api/blocks-checkout',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp(
						'^@woocommerce/blocks-checkout-events/(.*)$'
					),
					replacement: new URL(
						'./packages/public-api/blocks-checkout-events/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/blocks-checkout-events$' ),
					replacement: new URL(
						'./packages/public-api/blocks-checkout-events',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/blocks-components/(.*)$' ),
					replacement: new URL(
						'./packages/public-api/blocks-components/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/blocks-components$' ),
					replacement: new URL(
						'./packages/public-api/blocks-components',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/price-format/(.*)$' ),
					replacement: new URL(
						'./packages/public-api/price-format/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/price-format$' ),
					replacement: new URL(
						'./packages/public-api/price-format',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/block-hocs(.*)$' ),
					replacement: new URL(
						'./assets/js/hocs/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/base-components(.*)$' ),
					replacement: new URL(
						'./assets/js/base/components/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/base-context(.*)$' ),
					replacement: new URL(
						'./assets/js/base/context/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/base-hocs(.*)$' ),
					replacement: new URL(
						'./assets/js/base/hocs/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/base-hooks(.*)$' ),
					replacement: new URL(
						'./assets/js/base/hooks/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/base-utils(.*)$' ),
					replacement: new URL(
						'./assets/js/base/utils',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/block-data/(.*)$' ),
					replacement: new URL(
						'./packages/public-api/block-data/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/block-data$' ),
					replacement: new URL(
						'./packages/public-api/block-data',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/resource-previews' ),
					replacement: new URL(
						'./assets/js/previews',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/shared-context/(.*)$' ),
					replacement: new URL(
						'./packages/public-api/shared-context/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/shared-context$' ),
					replacement: new URL(
						'./packages/public-api/shared-context',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/shared-hocs/(.*)$' ),
					replacement: new URL(
						'./packages/public-api/shared-hocs/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/shared-hocs$' ),
					replacement: new URL(
						'./packages/public-api/shared-hocs',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/blocks-test-utils/(.*)$' ),
					replacement: new URL( './tests/utils/$1', import.meta.url )
						.pathname,
				},
				{
					find: new RegExp( '@woocommerce/blocks-test-utils' ),
					replacement: new URL( './tests/utils', import.meta.url )
						.pathname,
				},
				{
					find: new RegExp( '^@woocommerce/types/(.*)$' ),
					replacement: new URL(
						'./packages/public-api/types/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/types$' ),
					replacement: new URL(
						'./packages/public-api/types',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/utils/(.*)$' ),
					replacement: new URL(
						'./assets/js/utils/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/utils' ),
					replacement: new URL( './assets/js/utils', import.meta.url )
						.pathname,
				},
				{
					find: new RegExp( '@woocommerce/test-utils/msw' ),
					replacement: new URL(
						'./tests/js/config/msw-setup.js',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/entities/(.*)$' ),
					replacement: new URL(
						'./packages/internal/entities/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/entities$' ),
					replacement: new URL(
						'./packages/internal/entities',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '@woocommerce/stores/(.*)$' ),
					replacement: new URL(
						'./assets/js/base/stores/$1',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp(
						'^@woocommerce/([^/]+)/(?:src|build|build-module|build-types)/(.+)$'
					),
					replacement: new URL(
						'../../../../packages/js/$1/src/$2',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/([^/]+)/(.+)$' ),
					replacement: new URL(
						'../../../../packages/js/$1/src/$2',
						import.meta.url
					).pathname,
				},
				{
					find: new RegExp( '^@woocommerce/([^/]+)$' ),
					replacement: new URL(
						'../../../../packages/js/$1/src',
						import.meta.url
					).pathname,
				},
			],
		},
		test: {
			setupFiles: [
				new URL( './tests/js/config/global-mocks.js', import.meta.url )
					.pathname,
				new URL(
					'./tests/js/config/testing-library.js',
					import.meta.url
				).pathname,
				new URL( './tests/js/config/msw-setup.js', import.meta.url )
					.pathname,
			],
			exclude: [
				'**/tests/**',
				'**/node_modules/**',
				'**/build/**',
				'**/vendor/**',
				'**/*.d.ts',
			],
		},
	}
);
