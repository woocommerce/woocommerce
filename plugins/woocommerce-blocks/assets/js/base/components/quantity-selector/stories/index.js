/**
 * External dependencies
 */
import { boolean } from '@storybook/addon-knobs';

/**
 * Internal dependencies
 */
import QuantitySelector from '../';
import './style.scss';

export default {
	title: 'WooCommerce Blocks/@base-components/QuantitySelector',
	component: QuantitySelector,
};

export const Default = () => (
	<QuantitySelector
		disabled={ boolean( 'Disabled', false ) }
		itemName='widgets'
	/>
);
