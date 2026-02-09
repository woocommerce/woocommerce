/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const attributeOptionsPreview = [
	{
		label: __( 'Blue', 'woocommerce' ),
		value: 'blue',
		rawData: {
			id: 23,
			name: __( 'Blue', 'woocommerce' ),
			slug: 'blue',
			attr_slug: 'blue',
			description: '',
			parent: 0,
			count: 4,
		},
	},
	{
		label: __( 'Gray', 'woocommerce' ),
		value: 'gray',
		selected: true,
		rawData: {
			id: 29,
			name: __( 'Gray', 'woocommerce' ),
			slug: 'gray',
			attr_slug: 'gray',
			description: '',
			parent: 0,
			count: 3,
		},
	},
	{
		label: __( 'Green', 'woocommerce' ),
		value: 'green',
		rawData: {
			id: 24,
			name: __( 'Green', 'woocommerce' ),
			slug: 'green',
			attr_slug: 'green',
			description: '',
			parent: 0,
			count: 3,
		},
	},
	{
		label: __( 'Red', 'woocommerce' ),
		value: 'red',
		selected: true,
		rawData: {
			id: 25,
			name: __( 'Red', 'woocommerce' ),
			slug: 'red',
			attr_slug: 'red',
			description: '',
			parent: 0,
			count: 4,
		},
	},
	{
		label: __( 'Yellow', 'woocommerce' ),
		value: 'yellow',
		rawData: {
			id: 30,
			name: __( 'Yellow', 'woocommerce' ),
			slug: 'yellow',
			attr_slug: 'yellow',
			description: '',
			parent: 0,
			count: 1,
		},
	},
];

export const sortOrders = {
	'name-asc': __( 'Name, A to Z', 'woocommerce' ),
	'name-desc': __( 'Name, Z to A', 'woocommerce' ),
	'count-desc': __( 'Most results first', 'woocommerce' ),
	'count-asc': __( 'Least results first', 'woocommerce' ),
};

export const sortOrderOptions = Object.entries( sortOrders ).map(
	( [ value, label ] ) => ( {
		label,
		value,
	} )
);
