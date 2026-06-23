/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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

// Radix UI (used by @wordpress/ui SelectControl) rejects empty-string values.
// We use 'parent' as a UI sentinel for the '' API value ("Same as parent").
const SAME_AS_PARENT = 'parent';

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	Edit: ( { data, onChange, field } ) => {
		const { shippingClasses } = useSelect( ( select ) => {
			// eslint-disable-next-line @wordpress/data-no-store-string-literals
			const store = select(
				'experimental/wc/admin/products/shipping-classes'
			);
			return {
				shippingClasses:
					// @ts-expect-error - The store return type lives in Woo core.
					( store?.getProductShippingClasses?.() ??
						[] ) as ProductShippingClass[],
			};
		}, [] );

		const options = [
			{
				label: __( 'Same as parent', 'woocommerce' ),
				value: SAME_AS_PARENT,
			},
			...( shippingClasses?.length
				? shippingClasses.map( ( shippingClass ) => ( {
						label: shippingClass.name,
						value: shippingClass.slug,
				  } ) )
				: [] ),
		];

		const apiValue = data.shipping_class ?? '';
		const uiValue = apiValue === '' ? SAME_AS_PARENT : apiValue;
		const selectedOption = options.find( ( o ) => o.value === uiValue );

		return (
			<SelectControl
				label={ field.label }
				value={ selectedOption }
				items={ options }
				onValueChange={ ( option ) => {
					if ( option !== null && option !== undefined ) {
						const apiVal =
							option.value === SAME_AS_PARENT
								? ''
								: option.value ?? '';
						onChange( { shipping_class: apiVal } );
					}
				} }
			/>
		);
	},
};
