/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextareaControl } from '@wordpress/components';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */

import type { ProductEntityRecord } from '../types';
import { convertHtmlToPlainText } from '../../utilites';

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
				onChange={ ( value ) => onChange( { description: value } ) }
			/>
		);
	},
};
