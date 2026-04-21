/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
import { TextareaControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import { convertHtmlToPlainText } from '../../utilites';

const fieldDefinition = {
	type: 'text',
	label: __( 'Summary', 'woocommerce' ),
	description: __(
		'Give customers a quick overview of your product. This appears above the full description.',
		'woocommerce'
	),
	enableSorting: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => convertHtmlToPlainText( item.short_description ),
	Edit: ( { data, onChange, field } ) => {
		return (
			<TextareaControl
				label={ field.label }
				value={ convertHtmlToPlainText( data.short_description || '' ) }
				onChange={ ( value ) =>
					onChange( { short_description: value } )
				}
			/>
		);
	},
};
