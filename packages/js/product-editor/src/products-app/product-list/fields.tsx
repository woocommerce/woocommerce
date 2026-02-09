/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { __ } from '@wordpress/i18n';
import { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { OPERATOR_IS } from '../constants';

const STATUSES = [
	{ value: 'draft', label: __( 'Draft', 'woocommerce' ) },
	{ value: 'future', label: __( 'Scheduled', 'woocommerce' ) },
	{ value: 'private', label: __( 'Private', 'woocommerce' ) },
	{ value: 'publish', label: __( 'Published', 'woocommerce' ) },
	{ value: 'trash', label: __( 'Trash', 'woocommerce' ) },
];

/**
 * TODO: auto convert some of the product editor blocks ( from the blocks directory ) to this format.
 * The edit function should work relatively well with the edit from the blocks, the only difference is that the blocks rely on getEntityProp to get the value
 */
export const productFields: Field< Product >[] = [
	{
		id: 'name',
		label: __( 'Name', 'woocommerce' ),
		enableHiding: false,
		type: 'text',
		render: function nameRender( { item }: { item: Product } ) {
			return <>{ item.name }</>;
		},
	},
	{
		id: 'sku',
		label: __( 'SKU', 'woocommerce' ),
		enableHiding: false,
		enableSorting: false,
		render: ( { item }: { item: Product } ) => {
			return <>{ item.sku }</>;
		},
	},
	{
		id: 'date',
		label: __( 'Date', 'woocommerce' ),
		render: ( { item }: { item: Product } ) => {
			return <time>{ item.date_created }</time>;
		},
	},
	{
		label: __( 'Status', 'woocommerce' ),
		id: 'status',
		getValue: ( { item }: { item: Product } ) =>
			STATUSES.find( ( { value } ) => value === item.status )?.label ??
			item.status,
		elements: STATUSES,
		filterBy: {
			operators: [ OPERATOR_IS ],
		},
		enableSorting: false,
	},
];
