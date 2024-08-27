/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { DataViews } from '@wordpress/dataviews';

export const DataView = () => {
	const fields = [
		{
			label: 'Title',
			id: 'title',
			type: 'text' as const,
		},
	];

	const data = [
		{
			title: 'Title 1',
		},
		{
			title: 'Title 2',
		},
	];

	// Declare data, fields, etc.

	return (
		<DataViews
			data={ data }
			fields={ fields }
			paginationInfo={ {
				totalItems: 1,
				totalPages: 1,
			} }
			view={ {
				type: 'grid',
			} }
			onChangeView={ () => {} }
			defaultLayouts={ {
				list: {},
			} }
			getItemId={ ( item ) => item.title }
		/>
	);
};
