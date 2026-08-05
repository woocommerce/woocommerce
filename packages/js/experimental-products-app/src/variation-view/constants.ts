/**
 * External dependencies
 */
import type { SupportedLayouts, ViewTable } from '@wordpress/dataviews';

export const PAGE_SIZE = 20;

export const DEFAULT_VARIATION_TABLE_TITLE_FIELD = 'name';
export const DEFAULT_VARIATION_TABLE_MEDIA_FIELD = 'images';

export const DEFAULT_VARIATION_TABLE_FIELDS = [
	'variation_options',
	'variation_active',
	'price',
	'stock',
] as const;

export const DEFAULT_LAYOUTS = {
	table: {
		fields: [ ...DEFAULT_VARIATION_TABLE_FIELDS ],
		layout: {
			density: 'compact',
			styles: {
				price: {
					align: 'end',
				},
			},
		},
	} satisfies NonNullable< SupportedLayouts[ 'table' ] >,
};

export const DEFAULT_VIEW: ViewTable = {
	type: 'table',
	page: 1,
	perPage: PAGE_SIZE,
	search: '',
	filters: [],
	titleField: DEFAULT_VARIATION_TABLE_TITLE_FIELD,
	mediaField: DEFAULT_VARIATION_TABLE_MEDIA_FIELD,
	fields: [ ...DEFAULT_VARIATION_TABLE_FIELDS ],
	showMedia: true,
	layout: DEFAULT_LAYOUTS.table.layout,
};
