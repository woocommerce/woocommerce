/**
 * External dependencies
 */
import interpolate from '@automattic/interpolate-components';
import { BaseControl, TextControl } from '@wordpress/components';
import React, { createElement, useCallback, useRef, useState } from 'react';

/**
 * Internal dependencies
 */
import { TreeControl } from '../tree-control';
import { Item, LinkedTree } from '../types';
import '../tree.scss';

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
					shouldItemBeExpanded={ ( item ) =>
						shouldItemBeExpanded( item, filter )
					}
				/>
			</BaseControl>
		</>
	);
};

export const CustomItemLabel: React.FC = () => {
	function renderCustomItemLabel( item: LinkedTree ) {
		return (
			<div style={ { display: 'flex', gap: 8 } }>
				<div
					style={ {
						width: 36,
						height: 36,
						backgroundColor: '#ccc',
						borderRadius: 2,
					} }
				/>
				<div
					style={ {
						display: 'flex',
						flexDirection: 'column',
					} }
				>
					<strong>{ item.data.label }</strong>
					<small>Some item description</small>
				</div>
			</div>
		);
	}

	return (
		<BaseControl label="Custom item label" id="custom-item-label">
			<TreeControl
				id="custom-item-label"
				items={ listItems }
				getItemLabel={ renderCustomItemLabel }
			/>
		</BaseControl>
	);
};

function getItemLabel( item: LinkedTree, text: string ) {
	return (
		<span>
			{ text
				? interpolate( {
						mixedString: item.data.label.replace(
							new RegExp( text, 'ig' ),
							( group ) => `{{bold}}${ group }{{/bold}}`
						),
						components: {
							bold: <b />,
						},
				  } )
				: item.data.label }
		</span>
	);
}

export const CustomItemLabelOnSearch: React.FC = () => {
	const [ text, setText ] = useState( '' );

	return (
		<>
			<TextControl value={ text } onChange={ setText } />
			<BaseControl
				label="Custom item label on search"
				id="custom-item-label-on-search"
			>
				<TreeControl
					id="custom-item-label-on-search"
					items={ listItems }
					getItemLabel={ ( item ) => getItemLabel( item, text ) }
					shouldItemBeExpanded={ useCallback(
						( item ) => shouldItemBeExpanded( item, text ),
						[ text ]
					) }
				/>
			</BaseControl>
		</>
	);
};

export const SelectionSingle: React.FC = () => {
	const [ selected, setSelected ] = useState( listItems[ 1 ] );

	return (
		<>
			<BaseControl label="Single selection" id="single-selection">
				<TreeControl
					id="single-selection"
					items={ listItems }
					selected={ selected }
					onSelect={ ( value: Item ) => setSelected( value ) }
				/>
			</BaseControl>

			<pre>{ JSON.stringify( selected, null, 2 ) }</pre>
		</>
	);
};

export const SelectionMultiple: React.FC = () => {
	const [ selected, setSelected ] = useState( [
		listItems[ 0 ],
		listItems[ 1 ],
	] );

	function handleSelect( values: Item[] ) {
		setSelected( ( items ) => {
			const newItems = values.filter(
				( { value } ) =>
					! items.some( ( item ) => item.value === value )
			);
			return [ ...items, ...newItems ];
		} );
	}

	function handleRemove( values: Item[] ) {
		setSelected( ( items ) =>
			items.filter(
				( item ) =>
					! values.some( ( { value } ) => item.value === value )
			)
		);
	}

	return (
		<>
			<BaseControl label="Multiple selection" id="multiple-selection">
				<TreeControl
					id="multiple-selection"
					items={ listItems }
					multiple
					selected={ selected }
					onSelect={ handleSelect }
					onRemove={ handleRemove }
				/>
			</BaseControl>

			<pre>{ JSON.stringify( selected, null, 2 ) }</pre>
		</>
	);
};

function getFirstMatchingItem(
	item: LinkedTree,
	text: string,
	memo: Record< string, string >
) {
	if ( ! text ) return false;
	if ( memo[ text ] === item.data.value ) return true;

	const matcher = new RegExp( text, 'ig' );
	if ( matcher.test( item.data.label ) ) {
		if ( ! memo[ text ] ) {
			memo[ text ] = item.data.value;
			return true;
		}
	}

	return false;
}

export const HighlightFirstMatchingItem: React.FC = () => {
	const [ text, setText ] = useState( '' );
	const memo = useRef< Record< string, string > >( {} );

	return (
		<>
			<TextControl value={ text } onChange={ setText } />
			<BaseControl
				label="Highlight first matching item"
				id="highlight-first-matching-item"
			>
				<TreeControl
					id="highlight-first-matching-item"
					items={ listItems }
					getItemLabel={ ( item ) => getItemLabel( item, text ) }
					shouldItemBeExpanded={ useCallback(
						( item ) => shouldItemBeExpanded( item, text ),
						[ text ]
					) }
					shouldItemBeHighlighted={ ( item ) =>
						getFirstMatchingItem( item, text, memo.current )
					}
				/>
			</BaseControl>
		</>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/TreeControl',
	component: TreeControl,
};
