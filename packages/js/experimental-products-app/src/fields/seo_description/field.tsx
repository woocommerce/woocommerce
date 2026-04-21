/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { TextareaControl } from '../components/compat-controls';

import type { ProductEntityRecord } from '../types';

import { convertHtmlToPlainText } from '../utils/html';

const RECOMMENDED_MAX_LENGTH = 156;

const fieldDefinition = {
	type: 'text',
	label: __( 'SEO description', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => item.seo_description,
	Edit: ( { data, onChange, field } ) => {
		const value = data.seo_description || '';
		const shortDescription = convertHtmlToPlainText(
			data.short_description || ''
		);
		return (
			<TextareaControl
				label={ field.label }
				value={ value }
				placeholder={ shortDescription }
				maxLength={ RECOMMENDED_MAX_LENGTH }
				onValueChange={ ( newValue ) =>
					onChange( { seo_description: newValue } )
				}
				description={ sprintf(
					/* translators: 1: current character count, 2: recommended maximum character count */
					__( '%1$d of %2$d characters used', 'woocommerce' ),
					value.length,
					RECOMMENDED_MAX_LENGTH
				) }
			/>
		);
	},
};
