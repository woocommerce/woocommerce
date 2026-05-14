/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/ui';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

function isValidVisibility( value: string ) {
	return (
		value === 'visible' ||
		value === 'catalog' ||
		value === 'search' ||
		value === 'hidden'
	);
}

const fieldDefinition = {
	label: __( 'Visibility', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
	elements: [
		{ label: __( 'Public', 'woocommerce' ), value: 'visible' },
		{ label: __( 'Catalog', 'woocommerce' ), value: 'catalog' },
		{ label: __( 'Search', 'woocommerce' ), value: 'search' },
		{ label: __( 'Hidden', 'woocommerce' ), value: 'hidden' },
	],
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	Edit: ( { data, onChange, field } ) => {
		const options = field.elements ?? [];
		const selectedOption = options.find(
			( option ) =>
				option.value === ( data.catalog_visibility ?? 'visible' )
		);

		return (
			<SelectControl
				label={ field.label }
				value={ selectedOption }
				items={ options }
				onValueChange={ ( option ) => {
					const value = option?.value;

					if (
						typeof value === 'string' &&
						isValidVisibility( value )
					) {
						onChange( {
							catalog_visibility: value,
						} );
					}
				} }
			/>
		);
	},
};
