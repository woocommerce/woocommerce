// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
/**
 * External dependencies
 */
import { DataViews } from '@wordpress/dataviews';
import { useEntityRecords } from '@wordpress/core-data';
import { createElement, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const STATUSES = [
	{ value: 'draft', label: __( 'Draft' ) },
	{ value: 'future', label: __( 'Scheduled' ) },
	{ value: 'pending', label: __( 'Pending Review' ) },
	{ value: 'private', label: __( 'Private' ) },
	{ value: 'publish', label: __( 'Published' ) },
	{ value: 'trash', label: __( 'Trash' ) },
];

export function Layout() {
	const [ view, setView ] = useState( {
		type: 'table',
		perPage: 5,
		page: 1,
		sort: {
			field: 'date',
			direction: 'desc',
		},
		search: '',
		filters: [
			{ field: 'author', operator: 'is', value: 2 },
			{
				field: 'status',
				operator: 'isAny',
				value: [ 'publish', 'draft' ],
			},
		],
		fields: [ 'author', 'status' ],
		layout: {},
	} );

	const queryArgs = useMemo( () => {
		const filters = {};
		view.filters.forEach( ( filter ) => {
			if ( filter.field === 'status' && filter.operator === 'isAny' ) {
				filters.status = filter.value;
			}
			if ( filter.field === 'author' && filter.operator === 'is' ) {
				filters.author = filter.value;
			}
		} );
		return {
			per_page: view.perPage,
			page: view.page,
			_embed: 'author',
			order: view.sort?.direction,
			orderby: view.sort?.field,
			search: view.search,
			...filters,
		};
	}, [ view ] );

	const { records } = useEntityRecords( 'postType', 'page', queryArgs );

	return (
		<DataViews
			data={ records }
			view={ view }
			onChangeView={ setView }
			// ...
		/>
	);
}
