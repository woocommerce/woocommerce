/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import type { Page } from '@wordpress/core-data';
import { resolveSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_shop_page_id',
	label: __( 'Shop page', 'woocommerce' ),
	type: 'text' as const,
	description: __(
		"This sets your shop's base page — where your product archive lives. You can also use it in your product permalinks.",
		'woocommerce'
	),
	Edit: 'select',
	getElements: async () => {
		const pages: Pick< Page, 'id' | 'title' >[] | null =
			await resolveSelect( coreStore ).getEntityRecords(
				'postType',
				'page',
				{
					per_page: -1,
					orderby: 'title',
					order: 'asc',
					_fields: [ 'id', 'title' ],
				}
			);

		return ( pages ?? [] ).map( ( page ) => ( {
			value: String( page.id ),
			label:
				decodeEntities( page.title.rendered ) ||
				__( '(no title)', 'woocommerce' ),
		} ) );
	},
};
