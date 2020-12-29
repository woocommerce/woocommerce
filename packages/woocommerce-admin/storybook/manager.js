/**
 * External dependencies
 */
import { addons } from '@storybook/addons';
import { themes } from '@storybook/theming';

/**
 * Internal dependencies
 */
import logoUrl from './woocommerce_logo.png';

addons.setConfig( {
	theme: { ...themes.light, brandImage: logoUrl },
} );
