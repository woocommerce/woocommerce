/**
 * External dependencies
 */
import { BaseControl, TextControl } from '@wordpress/components';
import React, { createElement, useCallback, useState } from 'react';

/**
 * Internal dependencies
 */
import { TreeControl } from '../tree-control';
import { Item, LinkedTree } from '../types';

const listItems: Item[] = [
	{ value: '1', label: 'Technology' },
	{ value: '1.1', label: 'Notebooks', parent: '1' },
	{ value: '1.2', label: 'Phones', parent: '1' },
	{ value: '1.2.1', label: 'iPhone', parent: '1.2' },
	{ value: '1.2.1.1', label: 'iPhone 14 Pro', parent: '1.2.1' },
	{ value: '1.2.1.2', label: 'iPhone 14 Pro Max', parent: '1.2.1' },
	{ value: '1.2.2', label: 'Samsung', parent: '1.2' },
	{ value: '1.2.2.1', label: 'Samsung Galaxy 22 Plus', parent: '1.2.2' },
	{ value: '1.2.2.2', label: 'Samsung Galaxy 22 Ultra', parent: '1.2.2' },
	{ value: '1.3', label: 'Wearables', parent: '1' },
	{ value: '2', label: 'Hardware' },
	{ value: '2.1', label: 'CPU', parent: '2' },
	{ value: '2.2', label: 'GPU', parent: '2' },
	{ value: '2.3', label: 'Memory RAM', parent: '2' },
	{ value: '3', label: 'Other' },
];

export const SimpleTree: React.FC = () => {
	return (
		<BaseControl label="Simple tree" id="simple-tree">
			<TreeControl id="simple-tree" items={ listItems } />
		</BaseControl>
	);
};

function shouldItemBeExpanded( item: LinkedTree, filter: string ) {
	if ( ! filter || ! item.children?.length ) return false;
	return item.children.some( ( child ) => {
		if ( new RegExp( filter, 'ig' ).test( child.data.label ) ) {
			return true;
		}
		return shouldItemBeExpanded( child, filter );
	} );
}

export const ExpandOnFilter: React.FC = () => {
	const [ filter, setFilter ] = useState( '' );

	return (
		<>
			<TextControl value={ filter } onChange={ setFilter } />
			<BaseControl label="Expand on filter" id="expand-on-filter">
				<TreeControl
					id="expand-on-filter"
					items={ listItems }
					shouldItemBeExpanded={ useCallback(
						( item ) => shouldItemBeExpanded( item, filter ),
						[ filter ]
					) }
				/>
			</BaseControl>
		</>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/TreeControl',
	component: TreeControl,
};
