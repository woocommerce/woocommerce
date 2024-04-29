/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import '@wordpress/interface/src/style.scss';
import { ProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import AttributesComboboxControl from '../';
import type { AttributesComboboxControlComponent } from '../types';

export default {
	title: 'Product Editor/components/AttributesComboboxControl',
	component: AttributesComboboxControl,
};

const items = [
	{
		id: 1,
		name: 'Color',
		slug: 'pa_color',
		takenBy: 1,
	},
	{
		id: 2,
		name: 'Size',
		slug: 'pa_size',
		takenBy: 1,
	},
	{
		id: 3,
		name: 'Material',
		slug: 'pa_material',
		takenBy: 1,
	},
	{
		id: 4,
		name: 'Style',
		slug: 'pa_style',
		takenBy: 1,
	},
	{
		id: 5,
		name: 'Brand',
		slug: 'pa_brand',
		takenBy: 1,
	},
	{
		id: 6,
		name: 'Pattern',
		slug: 'pa_pattern',
		takenBy: 1,
	},
	{
		id: 7,
		name: 'Theme',
		slug: 'pa_theme',
		takenBy: 1,
	},
	{
		id: 8,
		name: 'Collection',
		slug: 'pa_collection',
		takenBy: 1,
	},
	{
		id: 9,
		name: 'Occasion',
		slug: 'pa_occasion',
		takenBy: 1,
	},
	{
		id: 10,
		name: 'Season',
		slug: 'pa_season',
		takenBy: 1,
	},
];

export const Default = ( args: AttributesComboboxControlComponent ) => {
	const [ selectedAttribute, setSelectedAttribute ] = useState<
		ProductAttribute | undefined
	>();

	function selectAttribute( item: ProductAttribute | string | undefined ) {
		if ( typeof item === 'string' ) {
			return;
		}

		setSelectedAttribute( item );
	}

	return (
		<AttributesComboboxControl
			{ ...args }
			onChange={ selectAttribute }
			current={ selectedAttribute }
		/>
	);
};

Default.args = {
	label: __( 'Attributes', 'woocommerce' ),
	items,
	help: __( 'Select or create attributes for this product.', 'woocommerce' ),

	onChange: ( newValue: ProductAttribute ) => {
		console.log( '(onChange) newValue:', newValue ); // eslint-disable-line no-console
	},
};
