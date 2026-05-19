/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
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
	filterBy: false,
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

		const parentShippingClass = useSelect(
			( select ) => {
				const parentId = data.parent_id;
				if ( ! parentId ) {
					return undefined;
				}
				const parent = select( coreStore ).getEditedEntityRecord(
					'root',
					'product',
					parentId
				) as unknown as ProductEntityRecord | undefined;
				return parent?.shipping_class || undefined;
			},
			[ data.parent_id ]
		);

		const isVariation = Boolean( data.parent_id );

		// SelectControl from @wordpress/ui treats an empty-string value as
		// "no selection" and falls back to the placeholder. Use a sentinel so
		// the inherit/no-class option is shown like any other selectable item.
		const DEFAULT_OPTION_VALUE = '__default__';
		const defaultLabel = isVariation
			? __( 'Same as parent', 'woocommerce' )
			: __( 'No shipping class', 'woocommerce' );

		const items = [
			{ label: defaultLabel, value: DEFAULT_OPTION_VALUE },
			...( shippingClasses?.length
				? shippingClasses.map( ( shippingClass ) => ( {
						label: shippingClass.name,
						value: shippingClass.slug,
				  } ) )
				: [] ),
		];

		const variationShippingClass = data.shipping_class ?? '';
		const isInheritedFromParent =
			isVariation && variationShippingClass === '';
		const selectedOption = isInheritedFromParent
			? items[ 0 ]
			: items.find(
					( item ) => item.value === variationShippingClass
			  ) ?? items[ 0 ];

		return (
			<SelectControl
				label={ field.label }
				value={ selectedOption }
				items={ items }
				onValueChange={ ( option ) =>
					onChange( {
						shipping_class:
							option?.value === DEFAULT_OPTION_VALUE
								? ''
								: option?.value ?? '',
					} )
				}
			/>
		);
	},
};
