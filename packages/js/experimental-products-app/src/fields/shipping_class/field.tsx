/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';
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
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	id: 'shipping_class',
	label: __( 'Shipping Class', 'woocommerce' ),
	enableSorting: false,
	type: 'text',
	getValue: ( { item } ) => item.shipping_class,
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

		return (
			<SelectControl
				label={ field.label }
				value={ data.shipping_class }
				options={ options }
				onChange={ ( value ) =>
					onChange( {
						shipping_class: value,
					} )
				}
			/>
		);
	},
};
