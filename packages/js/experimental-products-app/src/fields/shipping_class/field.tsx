/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { resolveSelect, useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';
import { SelectControl } from '@wordpress/ui';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */

import type { ProductEntityRecord } from '../types';

interface ProductShippingClass {
	id: number;
	slug: string;
	name: string;
	description: string;
	count: number;
}

const fieldDefinition = {
	type: 'text',
	label: __( 'Shipping Class', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: {
		operators: [ 'isAny', 'isNone' ],
	},
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	id: 'shipping_class',
	label: __( 'Shipping Class', 'woocommerce' ),
	enableSorting: false,
	type: 'text',
	getValue: ( { item } ) =>
		item.shipping_class_id ? item.shipping_class_id.toString() : '',
	render: ( { item } ) => item.shipping_class ?? '',
	getElements: async () => {
		const records = ( await resolveSelect( coreStore ).getEntityRecords(
			'taxonomy',
			'product_shipping_class',
			{ per_page: -1 }
		) ) as Array< { id: number; name: string } > | null;
		return ( records ?? [] ).map( ( { id, name } ) => ( {
			value: id.toString(),
			label: decodeEntities( name ),
		} ) );
	},
	isVisible: ( item ) => ! item.virtual,
	Edit: ( { data, onChange, field } ) => {
		const { shippingClasses } = useSelect( ( select ) => {
			// TODO: Register shipping class entity and use it instead.
			// eslint-disable-next-line @wordpress/data-no-store-string-literals
			const { getProductShippingClasses } = select(
				'experimental/wc/admin/products/shipping-classes'
			);
			return {
				shippingClasses:
					// @ts-expect-error - The store return type lives in Woo core.
					getProductShippingClasses() as ProductShippingClass[],
			};
		}, [] );

		const options = [
			{
				label: __( 'No shipping class', 'woocommerce' ),
				value: '',
			},
			...( shippingClasses?.length
				? shippingClasses.map( ( shippingClass ) => ( {
						label: shippingClass.name,
						value: shippingClass.slug,
				  } ) )
				: [] ),
		];
		const selectedOption = options.find(
			( option ) => option.value === ( data.shipping_class ?? '' )
		);

		return (
			<SelectControl
				label={ field.label }
				value={ selectedOption }
				items={ options }
				onValueChange={ ( option ) =>
					onChange( {
						shipping_class: option?.value ?? '',
					} )
				}
			/>
		);
	},
};
