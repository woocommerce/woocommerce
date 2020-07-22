/**
 * External dependencies
 */
import { boolean } from '@storybook/addon-knobs';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import QuantitySelector from '../';

export default {
	title: 'WooCommerce Blocks/@base-components/QuantitySelector',
	component: QuantitySelector,
};

export const Default = () => {
	const [ quantity, changeQuantity ] = useState();

	return (
		<div style={ { width: 100 } }>
			<QuantitySelector
				disabled={ boolean( 'Disabled', false ) }
				quantity={ quantity }
				onChange={ changeQuantity }
				itemName="widgets"
			/>
		</div>
	);
};
