/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export function getTaxCode( tax ) {
	return tax.name ? tax.name.toUpperCase() : `${ __( 'TAX', 'woocommerce' ) }-${ tax.priority } `;
}
