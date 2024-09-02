/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import metadata from './block.json';
import save from './save';
import type { ProductCollectionQuery } from './types';

const v1 = {
	attributes: {
		...metadata.attributes,
		query: {
			type: 'object',
			properties: {
				woocommerceOnSale: {
					type: 'boolean',
					default: false,
				},
			},
		},
	},
	/**
	 * Force this deprecation to run whenever `woocommerceOnSale` has a boolean value.
	 *
	 * @param attributes                         The old attributes.
	 * @param attributes.query                   The old query.
	 * @param attributes.query.woocommerceOnSale The old woocommerceOnSale.
	 *
	 * @return True when the woocommerceOnSale attribute is boolean.
	 */
	isEligible( attributes: { query: { woocommerceOnSale: boolean } } ) {
		const { query } = attributes;
		return (
			query &&
			( query.woocommerceOnSale === true ||
				query.woocommerceOnSale === false )
		);
	},
	/**
	 * Migrate the old `attributes.query.woocommerceOnSale` attibute, to the current
	 * definition from ProductCollectionQuery.
	 *
	 * @param attributes                         The old attributes.
	 * @param attributes.query                   The old query.
	 * @param attributes.query.woocommerceOnSale The old woocommerceOnSale.
	 *
	 * @return The migrated version of the old attributes.
	 */
	migrate( attributes: { query: { woocommerceOnSale: boolean } } ) {
		const { query } = attributes;

		const newQuery = {
			...query,
			woocommerceOnSale:
				query.woocommerceOnSale === false ? undefined : 'show-only',
		} as ProductCollectionQuery;

		return {
			...attributes,
			query: newQuery,
		};
	},
	save,
};

const deprecated = [ v1 ];

export default deprecated;
