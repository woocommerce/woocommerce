/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Metadata } from '../../../../types';
import type { ValidationError } from './types';

export function validate(
	customField: Partial< Metadata< string > >
): ValidationError {
	const errors = {} as ValidationError;

	if ( ! customField.key ) {
		errors.key = __( 'The name is required.', 'woocommerce' );
	} else if ( customField.key.startsWith( '_' ) ) {
		errors.key = __(
			'The name cannot begin with the underscore (_) character.',
			'woocommerce'
		);
	}

	if ( ! customField.value ) {
		errors.value = __( 'The value is required.', 'woocommerce' );
	}

	return errors;
}
