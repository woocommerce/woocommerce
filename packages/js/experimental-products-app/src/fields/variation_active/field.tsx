/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Badge, SelectControl } from '@wordpress/ui';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord, ProductStatus } from '../types';

type VariationActiveValue = 'active' | 'inactive';

const ACTIVE_VALUE: VariationActiveValue = 'active';
const INACTIVE_VALUE: VariationActiveValue = 'inactive';

function isVariationActiveValue(
	value: unknown
): value is VariationActiveValue {
	return value === ACTIVE_VALUE || value === INACTIVE_VALUE;
}

function hasPrice( item: Pick< ProductEntityRecord, 'price' > ) {
	return item.price !== undefined && item.price !== null && item.price !== '';
}

export function isVariationActive(
	item: Pick< ProductEntityRecord, 'price' | 'status' >
) {
	return item.status === 'publish' && hasPrice( item );
}

export function getVariationActiveValue(
	item: Pick< ProductEntityRecord, 'price' | 'status' >
): VariationActiveValue {
	return isVariationActive( item ) ? ACTIVE_VALUE : INACTIVE_VALUE;
}

function getVariationActiveFormValue(
	data: ProductEntityRecord & Record< string, unknown >
): VariationActiveValue | undefined {
	if ( isVariationActiveValue( data.variation_active ) ) {
		return data.variation_active;
	}

	return getVariationActiveValue( data );
}

function getVariationActiveStatus(
	value: VariationActiveValue
): ProductStatus {
	return value === ACTIVE_VALUE ? 'publish' : 'private';
}

export function getVariationActiveLabel( value: VariationActiveValue ) {
	return value === ACTIVE_VALUE
		? __( 'Active', 'woocommerce' )
		: __( 'Inactive', 'woocommerce' );
}

export function VariationActiveBadge( {
	value,
}: {
	value: VariationActiveValue;
} ) {
	return value === ACTIVE_VALUE ? (
		<Badge intent="stable">{ getVariationActiveLabel( value ) }</Badge>
	) : (
		<Badge intent="draft">{ getVariationActiveLabel( value ) }</Badge>
	);
}

const fieldDefinition = {
	type: 'text',
	label: __( 'Status', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
	elements: [
		{ value: ACTIVE_VALUE, label: __( 'Active', 'woocommerce' ) },
		{ value: INACTIVE_VALUE, label: __( 'Inactive', 'woocommerce' ) },
	],
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => getVariationActiveValue( item ),
	render: ( { item }: { item: ProductEntityRecord } ) => (
		<VariationActiveBadge value={ getVariationActiveValue( item ) } />
	),
	Edit: ( { data, onChange, field } ) => {
		const options = field.elements ?? [];
		const formData = data as ProductEntityRecord &
			Record< string, unknown >;
		const formValue =
			field.placeholder &&
			! isVariationActiveValue( formData.variation_active )
				? undefined
				: getVariationActiveFormValue( formData );
		const selectedOption = options.find(
			( option ) => option.value === formValue
		);

		return (
			<SelectControl
				label={ field.label }
				placeholder={ field.placeholder }
				value={ selectedOption }
				items={ options }
				onValueChange={ ( option ) => {
					const selectedValue = option?.value;

					if (
						selectedValue === ACTIVE_VALUE ||
						selectedValue === INACTIVE_VALUE
					) {
						onChange( {
							status: getVariationActiveStatus( selectedValue ),
						} );
					}
				} }
			/>
		);
	},
};
