/**
 * External dependencies
 */
import { boolean } from '@storybook/addon-knobs';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import QuantitySelector from '../';
import './style.scss';

export default {
	title: 'WooCommerce Blocks/@base-components/QuantitySelector',
	component: QuantitySelector,
};

export const Default = () => {
	const [ quantity, changeQuantity ] = useState();

	return (
		<QuantitySelector
			disabled={ boolean( 'Disabled', false ) }
			quantity={ quantity }
			onChange={ changeQuantity }
			itemName="widgets"
		/>
	);
};
