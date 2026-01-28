/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useMemo } from '@wordpress/element';
import {
	trash,
	pages,
	drafts,
	published,
	scheduled,
	notAllowed,
} from '@wordpress/icons';
import type { ColumnStyle, ViewTable } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import {
	LAYOUT_LIST,
	LAYOUT_TABLE,
	LAYOUT_GRID,
	OPERATOR_IS,
} from '../constants';

export const defaultLayouts: Record<
	string,
	{
		layout: {
			primaryField: string;
			mediaField?: string;
			styles?: Record< string, ColumnStyle >;
		};
	}
> = {
	[ LAYOUT_TABLE ]: {
		layout: {
			primaryField: 'name',
			styles: {
				name: {
					maxWidth: 300,
				},
			},
		},
	},
	[ LAYOUT_GRID ]: {
		layout: {
			mediaField: 'featured-image',
			primaryField: 'name',
		},
	},
	[ LAYOUT_LIST ]: {
		layout: {
			primaryField: 'name',
			mediaField: 'featured-image',
		},
	},
};

const DEFAULT_POST_BASE: Omit< ViewTable, 'view' | 'title' | 'slug' | 'icon' > =
	{
		type: LAYOUT_TABLE,
		search: '',
		filters: [],
		page: 1,
		perPage: 20,
		sort: {
			field: 'date',
			direction: 'desc',
		},
		fields: [ 'name', 'sku', 'status', 'date' ],
		layout: defaultLayouts[ LAYOUT_LIST ].layout,
	};

export function useDefaultViews( { postType }: { postType: string } ): Array< {
	title: string;
	slug: string;
	icon: React.JSX.Element;
	view: ViewTable;
} > {
	const labels = useSelect(
		( select ) => {
			const { getPostType } = select( coreStore );
			const postTypeData: { labels?: Record< string, string > } =
				getPostType( postType );
			return postTypeData?.labels;
		},
		[ postType ]
	);
	return useMemo( () => {
		return [
			{
				title: labels?.all_items || __( 'All items', 'woocommerce' ),
				slug: 'all',
				icon: pages,
				view: { ...DEFAULT_POST_BASE },
			},
			{
				title: __( 'Published', 'woocommerce' ),
				slug: 'published',
				icon: published,
				view: {
					...DEFAULT_POST_BASE,
					filters: [
						{
							field: 'status',
							operator: OPERATOR_IS,
							value: 'publish',
						},
					],
				},
			},
			{
				title: __( 'Scheduled', 'woocommerce' ),
				slug: 'future',
				icon: scheduled,
				view: {
					...DEFAULT_POST_BASE,
					filters: [
						{
							field: 'status',
							operator: OPERATOR_IS,
							value: 'future',
						},
					],
				},
			},
			{
				title: __( 'Drafts', 'woocommerce' ),
				slug: 'drafts',
				icon: drafts,
				view: {
					...DEFAULT_POST_BASE,
					filters: [
						{
							field: 'status',
							operator: OPERATOR_IS,
							value: 'draft',
						},
					],
				},
			},
			{
				title: __( 'Private', 'woocommerce' ),
				slug: 'private',
				icon: notAllowed,
				view: {
					...DEFAULT_POST_BASE,
					filters: [
						{
							field: 'status',
							operator: OPERATOR_IS,
							value: 'private',
						},
					],
				},
			},
			{
				title: __( 'Trash', 'woocommerce' ),
				slug: 'trash',
				icon: trash,
				view: {
					...DEFAULT_POST_BASE,
					type: LAYOUT_TABLE,
					layout: defaultLayouts[ LAYOUT_TABLE ].layout,
					filters: [
						{
							field: 'status',
							operator: OPERATOR_IS,
							value: 'trash',
						},
					],
				},
			},
		];
	}, [ labels ] );
}
