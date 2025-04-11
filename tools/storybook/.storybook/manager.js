/**
 * External dependencies
 */
import { addons } from '@storybook/manager-api';
import { themes } from '@storybook/theming';

/**
 * Internal dependencies
 */
import logoUrl from '../woo-logo.svg';

addons.setConfig( {
	theme: { ...themes.light, brandImage: logoUrl },
	sidebar: {
		collapsedRoots: [
			'components',
			'experimental',
			'onboarding',
			'product-editor',
			'woocommerce-admin',
		],
	},
} );
