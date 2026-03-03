/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { TaxonomyItem } from './types';

const taxonomies = getSetting< TaxonomyItem[] >(
	'filterableProductTaxonomies',
	[]
);

export function getTaxonomyLabel( taxonomy: string ) {
	const match = taxonomies.find( ( item ) => item.name === taxonomy );
	if ( match ) {
		return match.label;
	}
	return __( 'Taxonomy', 'woocommerce' );
}
