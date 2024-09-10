/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import type { ProductAttribute } from '@woocommerce/data';
import '@wordpress/interface/src/style.scss';

/**
 * Internal dependencies
 */
import AttributesComboboxControl from '../';
import type {
	AttributesComboboxControlComponent,
	AttributesComboboxControlItem,
} from '../types';

export default {
	title: 'Product Editor/components/AttributesComboboxControl',
	component: AttributesComboboxControl,
};

const items: AttributesComboboxControlItem[] = [
	{
		id: 1,
		name: 'Color',
	},
	{
		id: 2,
		name: 'Size',
	},
	{
		id: 3,
		name: 'Material',
		isDisabled: true,
	},
	{
		id: 4,
		name: 'Style',
	},
	{
		id: 5,
		name: 'Brand',
	},
	{
		id: 6,
		name: 'Pattern',
	},
	{
		id: 7,
		name: 'Theme',
		isDisabled: true,
	},
	{
		id: 8,
		name: 'Collection',
		isDisabled: true,
	},
	{
		id: 9,
		name: 'Occasion',
	},
	{
		id: 10,
		name: 'Season',
	},
];

export const Default = ( args: AttributesComboboxControlComponent ) => {
	const [ selectedAttribute, setSelectedAttribute ] =
		useState< AttributesComboboxControlItem | null >( null );

	function selectAttribute( item: AttributesComboboxControlItem ) {
		if ( typeof item === 'string' ) {
			return;
		}

		setSelectedAttribute( item );
		args.onChange( item );
	}

	return (
		<AttributesComboboxControl
			{ ...args }
			label={ __( 'Attributes', 'woocommerce' ) }
			items={ items }
			help={ __(
				'Select or create attributes for this product.',
				'woocommerce'
			) }
			onChange={ selectAttribute }
			current={ selectedAttribute }
		/>
	);
};

Default.args = {
	onChange: ( newValue: ProductAttribute ) => {
		console.log( '(onChange) newValue:', newValue ); // eslint-disable-line no-console
	},
};

export const MultipleInstances = (
	args: AttributesComboboxControlComponent
) => {
	return (
		<>
			<AttributesComboboxControl
				{ ...args }
				label={ __( 'Attributes 1', 'woocommerce' ) }
				items={ items }
				instanceNumber={ 1 }
			/>

			<AttributesComboboxControl
				{ ...args }
				label={ __( 'Attributes 2', 'woocommerce' ) }
				items={ items }
				instanceNumber={ 2 }
			/>
		</>
	);
};

MultipleInstances.args = Default.args;
