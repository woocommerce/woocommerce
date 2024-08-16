// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
/**
 * External dependencies
 */
import { DataViews, DataForm } from '@wordpress/dataviews';
import { createElement, useState, Fragment } from '@wordpress/element';
import { __experimentalVStack as VStack } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
const STATUSES = [
	{ value: 'draft', label: __( 'Draft' ) },
	{ value: 'future', label: __( 'Scheduled' ) },
	{ value: 'pending', label: __( 'Pending Review' ) },
	{ value: 'private', label: __( 'Private' ) },
	{ value: 'publish', label: __( 'Published' ) },
	{ value: 'trash', label: __( 'Trash' ) },
];
const fields = [
	{
		id: 'title',
		label: 'Title',
		enableHiding: false,
		render: ( { item } ) => {
			return item.title.rendered;
		},
	},
	{
		id: 'date',
		label: 'Date',
		render: ( { item } ) => {
			return <time>{ item.date }</time>;
		},
	},
	{
		id: 'author',
		label: __( 'Author' ),
		render: ( { item } ) => {
			return <a href="...">{ item.author }</a>;
		},
		elements: [
			{ value: 1, label: 'Admin' },
			{ value: 2, label: 'User' },
		],
		filterBy: {
			operators: [ 'is', 'isNot' ],
		},
		enableSorting: false,
	},
	{
		label: __( 'Status' ),
		id: 'status',
		getValue: ( { item } ) =>
			STATUSES.find( ( { value } ) => value === item.status )?.label ??
			item.status,
		elements: STATUSES,
		filterBy: {
			operators: [ 'isAny' ],
		},
		enableSorting: false,
	},
];

const defaultLayouts = {
	table: {
		layout: {
			primaryKey: 'my-key',
		},
	},
};

const form = {
	type: 'panel',
	fields: [ 'title', 'status', 'author' ],
};

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
		filters: [],
		fields: [ 'title', 'date', 'author', 'status' ],
		layout: {},
	} );

	const records = useSelect( ( select ) => {
		return select( 'core' ).getEntityRecords( 'postType', 'product' );
	} );

	const onChange = ( edits ) => {
		// do nothing.
	};

	if ( ! records ) {
		return null;
	}

	return (
		<>
			<VStack spacing={ 4 }>
				<DataForm
					data={ records[ 0 ] }
					fields={ fields }
					form={ form }
					onChange={ onChange }
				/>
			</VStack>
			<DataViews
				data={ records || [] }
				view={ view }
				onChangeView={ setView }
				fields={ fields }
				defaultLayouts={ defaultLayouts }
				paginationInfo={ {
					totalItems: ( records || [] ).length,
					totalPages: 1,
				} }
			/>
		</>
	);
}
