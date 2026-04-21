/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { InputControl } from '../components/compat-controls';

import type { ProductEntityRecord } from '../types';

const RECOMMENDED_MAX_LENGTH = 70;

const fieldDefinition = {
	type: 'text',
	label: __( 'SEO title', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => item.seo_title,
	Edit: ( { data, onChange, field } ) => {
		const value = data.seo_title || '';
		return (
			<InputControl
				label={ field.label }
				value={ value }
				placeholder={ data.name }
				maxLength={ RECOMMENDED_MAX_LENGTH }
				onChange={ ( event ) =>
					onChange( { seo_title: event.target.value } )
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
