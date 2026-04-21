/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { TextareaControl } from '../components/compat-controls';

import type { ProductEntityRecord } from '../types';

import { convertHtmlToPlainText } from '../utils/html';

const fieldDefinition = {
	type: 'text',
	label: __( 'Description', 'woocommerce' ),
	description: __(
		'Share the full story—include product details, features, and benefits to help customers decide.',
		'woocommerce'
	),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => convertHtmlToPlainText( item.description ),
	Edit: ( { data, onChange, field } ) => {
		return (
			<TextareaControl
				label={ field.label }
				rows={ 10 }
				value={ convertHtmlToPlainText( data.description || '' ) }
				onValueChange={ ( value ) =>
					onChange( { description: value } )
				}
				description={ field.description }
			/>
		);
	},
};
