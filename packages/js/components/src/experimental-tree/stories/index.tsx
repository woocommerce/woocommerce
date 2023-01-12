/**
 * External dependencies
 */
import interpolate from '@automattic/interpolate-components';
import { BaseControl, TextControl } from '@wordpress/components';
import React, { createElement, useCallback, useRef, useState } from 'react';

/**
 * Internal dependencies
 */
import { Tree } from '../tree';
import { Item } from '../types';

const treeItems: Item[] = [
	{
		value: '1',
		label: 'Technology',
		children: [
			{
				value: '1.1',
				label: 'Notebooks',
				children: [],
			},
			{
				value: '1.2',
				label: 'Phones',
				children: [
					{ value: '1.2.1', label: 'iPhone', children: [] },
					{ value: '1.2.2', label: 'Samsung', children: [] },
				],
			},
			{ value: '1.3', label: 'Wearables', children: [] },
		],
	},
	{
		value: '2',
		label: 'Hardware',
		children: [ { value: '2.1', label: 'CPU', children: [] } ],
	},
	{ value: '3', label: 'Other', children: [] },
];

function getItemLabel( item: Item, text: string ) {
	return (
		<span>
			{ text
				? interpolate( {
						mixedString: item.label.replace(
							new RegExp( text, 'ig' ),
							( group ) => `{{bold}}${ group }{{/bold}}`
						),
						components: {
							bold: <b />,
						},
				  } )
				: item.label }
		</span>
	);
}

export const SingleSelection: React.FC = () => {
	const [ selected, setSelected ] = useState< Item >();

	return (
		<>
			<BaseControl
				label="Single selection"
				id="single-selection"
				help="This is a single selection tree"
			>
				<Tree
					id="single-selection"
					items={ treeItems }
					selected={ selected }
					onSelect={ ( value ) => {
						if ( ! Array.isArray( value ) ) {
							setSelected( value );
						}
					} }
					onRemove={ () => setSelected( undefined ) }
				/>
			</BaseControl>
			<pre>{ JSON.stringify( selected, null, 2 ) }</pre>
		</>
	);
};

export const MultipleSelection: React.FC = () => {
	const [ selected, setSelected ] = useState< Item[] >( [] );

	return (
		<>
			<BaseControl
				label="Multiple selection"
				id="mutiple-selection"
				help="This is a multiple selection tree"
			>
				<Tree
					id="mutiple-selection"
					multiple
					items={ treeItems }
					selected={ selected }
					onSelect={ ( value ) => {
						setSelected( ( prev ) => {
							if ( Array.isArray( value ) ) {
								const newValues = value.filter(
									( item ) =>
										! prev.some(
											( prevItem ) =>
												prevItem.value === item.value
										)
								);
								return [ ...prev, ...newValues ];
							}
							return [ ...prev, value ];
						} );
					} }
					onRemove={ ( value ) =>
						setSelected( ( prev ) =>
							prev.filter( ( curr ) =>
								Array.isArray( value )
									? ! value.some(
											( prevItem ) =>
												prevItem.value === curr.value
									  )
									: curr.value !== value.value
							)
						)
					}
				/>
			</BaseControl>

			<pre>{ JSON.stringify( selected, null, 2 ) }</pre>
		</>
	);
};

export const CustomLabel: React.FC = () => {
	return (
		<BaseControl label="Custom label" id="custom-label">
			<Tree
				id="custom-label"
				multiple
				items={ treeItems }
				getItemLabel={ ( item ) => <span>- { item.label } -</span> }
			/>
		</BaseControl>
	);
};

function isItemExpanded( item: Item, text: string ) {
	if ( ! text || ! item.children?.length ) return false;
	return item.children.some( ( child ) => {
		if ( new RegExp( text, 'ig' ).test( child.label ) ) {
			return true;
		}
		return isItemExpanded( child, text );
	} );
}

export const CustomExpandMultipleSelection: React.FC = () => {
	const [ text, setText ] = useState( '' );

	return (
		<>
			<TextControl value={ text } onChange={ setText } />

			<BaseControl
				label="Custom expand"
				id="custom-expand-mutiple-selection"
			>
				<Tree
					id="custom-expand-mutiple-selection"
					multiple
					items={ treeItems }
					getItemLabel={ ( item ) => getItemLabel( item, text ) }
					isItemExpanded={ useCallback(
						( item ) => isItemExpanded( item, text ),
						[ text ]
					) }
				/>
			</BaseControl>
		</>
	);
};

export const CustomExpandSingleSelection: React.FC = () => {
	const [ text, setText ] = useState( '' );

	return (
		<>
			<TextControl value={ text } onChange={ setText } />

			<BaseControl
				label="Custom expand"
				id="custom-expand-single-selection"
			>
				<Tree
					id="custom-expand-single-selection"
					items={ treeItems }
					getItemLabel={ ( item ) => getItemLabel( item, text ) }
					isItemExpanded={ useCallback(
						( item ) => isItemExpanded( item, text ),
						[ text ]
					) }
				/>
			</BaseControl>
		</>
	);
};

function filter( value: string, items: Item[] = treeItems ): Item[] {
	return items.reduce( ( filtered, item ) => {
		const filteredChildren = item.children?.length
			? filter( value, item.children )
			: [];
		if (
			item.label.toLowerCase().includes( value.toLowerCase() ) ||
			filteredChildren.length
		) {
			return [ ...filtered, { ...item, children: filteredChildren } ];
		}
		return filtered;
	}, [] as Item[] );
}

function getFirstMatchingItem(
	item: Item,
	text: string,
	memo: Record< string, string >
) {
	if ( ! text ) return false;
	if ( memo[ text ] === item.value ) return true;

	const matcher = new RegExp( text, 'ig' );
	if ( matcher.test( item.label ) ) {
		if ( ! memo[ text ] ) {
			memo[ text ] = item.value;
			return true;
		}
		return false;
	}

	item.children?.some( ( child ) => {
		return getFirstMatchingItem( child, text, memo );
	} );

	return false;
}

export const FilterMultipleSelection: React.FC = () => {
	const [ text, setText ] = useState( '' );
	const [ selected, setSelected ] = useState< Item[] >( [] );
	const [ items, setItems ] = useState< Item[] >( treeItems );
	const highlightedItemRef = useRef< Record< string, string > >( {} );

	return (
		<>
			<TextControl
				value={ text }
				onChange={ ( value ) => {
					setText( value );
					const result = filter( value );
					setItems( result );
				} }
			/>

			<BaseControl label="Filter" id="filter-multiple-selection">
				<Tree
					id="filter-multiple-selection"
					items={ items }
					multiple
					selected={ selected }
					onSelect={ ( value ) => {
						setSelected( ( prev ) => {
							if ( Array.isArray( value ) ) {
								const newValues = value.filter(
									( item ) =>
										! prev.some(
											( prevItem ) =>
												prevItem.value === item.value
										)
								);
								return [ ...prev, ...newValues ];
							}
							return [ ...prev, value ];
						} );
						highlightedItemRef.current = {};
					} }
					onRemove={ ( value ) => {
						setSelected( ( prev ) =>
							prev.filter( ( curr ) =>
								Array.isArray( value )
									? ! value.some(
											( prevItem ) =>
												prevItem.value === curr.value
									  )
									: curr.value !== value.value
							)
						);
						highlightedItemRef.current = {};
					} }
					getItemLabel={ ( item ) => getItemLabel( item, text ) }
					isItemExpanded={ useCallback(
						( item ) => isItemExpanded( item, text ),
						[ text ]
					) }
					isItemHighlighted={ useCallback(
						( item ) =>
							getFirstMatchingItem(
								item,
								text,
								highlightedItemRef.current
							),
						[ text, highlightedItemRef ]
					) }
				/>
			</BaseControl>
		</>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/Tree',
	component: Tree,
};
