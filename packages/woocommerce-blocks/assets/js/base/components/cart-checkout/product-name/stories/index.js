/**
 * External dependencies
 */
import { boolean } from '@storybook/addon-knobs';

/**
 * Internal dependencies
 */
import ProductName from '../';

export default {
	title: 'WooCommerce Blocks/@base-components/cart-checkout/ProductName',
	component: ProductName,
};

export const Default = () => {
	const disabled = boolean( 'disabled', false );

	return (
		<ProductName
			disabled={ disabled }
			name={ 'Test product' }
			permalink={ '/' }
		/>
	);
};
