/**
 * External dependencies
 */
import { BaseControl } from '@wordpress/components';
import React, { useState } from 'react';

/**
 * Internal dependencies
 */
import { Autocomplete } from '../autocomplete';
import { AutocompleteItem } from '../types';

const treeItems: AutocompleteItem[] = [
	{
		value: '1',
		label: 'Child 1',
		children: [
			{
				value: '1.1',
				label: 'Child 1.1',
				children: [],
			},
			{
				value: '1.2',
				label: 'Child 1.2',
				children: [
					{ value: '1.2.1', label: 'Child 1.2.1', children: [] },
					{ value: '1.2.2', label: 'Child 1.2.2', children: [] },
				],
			},
			{ value: '1.3', label: 'Child 1.3', children: [] },
		],
	},
	{
		value: '2',
		label: 'Child 2',
		children: [ { value: '2.1', label: 'Child 2.1', children: [] } ],
	},
	{ value: '3', label: 'Child 3', children: [] },
];

function filter(
	value: string,
	items: AutocompleteItem[] = treeItems
): AutocompleteItem[] {
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
	}, [] as AutocompleteItem[] );
}

export const SingleSelection: React.FC = () => {
	const [ selected, setSelected ] = useState< AutocompleteItem >();
	const [ items, setItems ] = useState( treeItems );

	return (
		<BaseControl
			label="Single selection"
			id="single-selection"
			help="This is a single selection tree autocomplete"
		>
			<Autocomplete
				id="single-selection"
				items={ items }
				selected={ selected }
				onSelect={ ( value ) => {
					if ( ! Array.isArray( value ) ) {
						setSelected( value );
					}
				} }
				onRemove={ () => setSelected( undefined ) }
				onInputChange={ ( value ) => {
					const result = filter( value );
					setItems( result );
				} }
			/>
		</BaseControl>
	);
};

export const MultipleSelection: React.FC = () => {
	const [ selected, setSelected ] = useState< AutocompleteItem[] >( [] );
	const [ items, setItems ] = useState( treeItems );

	return (
		<BaseControl
			label="Multiple selection"
			id="mutiple-selection"
			help="This is a multiple selection tree autocomplete"
		>
			<Autocomplete
				id="mutiple-selection"
				multiple
				items={ items }
				selected={ selected }
				onSelect={ ( value ) => {
					setSelected( ( prev ) => {
						if ( Array.isArray( value ) ) {
							const newValues = value.filter(
								( item ) =>
									! prev.some(
										( { value } ) => value === item.value
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
										( { value } ) => value === curr.value
								  )
								: curr.value !== value.value
						)
					)
				}
				onInputChange={ ( value ) => {
					const result = filter( value );
					setItems( result );
				} }
			/>
		</BaseControl>
	);
};

export const AllowCreateSingleSelection: React.FC = () => {
	const [ selected, setSelected ] = useState< AutocompleteItem >();
	const [ items, setItems ] = useState( treeItems );

	return (
		<BaseControl
			label="Allow create"
			id="allow-create-single-selection"
			help="This is a single selection tree autocomplete with allow create"
		>
			<Autocomplete
				id="allow-create-single-selection"
				allowCreate
				items={ items }
				selected={ selected }
				onSelect={ ( value ) => {
					if ( ! Array.isArray( value ) ) {
						setSelected( value );
					}
				} }
				onRemove={ () => setSelected( undefined ) }
				onInputChange={ ( value ) => {
					const result = filter( value );
					setItems( result );
				} }
				onCreateClick={ ( value ) => {
					if ( value ) {
						const newItem = { value, label: value };
						treeItems.push( newItem );
						setItems( filter( value ) );
						setSelected( newItem );
					} else {
						alert( 'onCreateNew' );
					}
				} }
			/>
		</BaseControl>
	);
};

export const AllowCreateMultipleSelection: React.FC = () => {
	const [ selected, setSelected ] = useState< AutocompleteItem[] >( [] );
	const [ items, setItems ] = useState( treeItems );

	return (
		<BaseControl
			label="Allow create"
			id="allow-create-mutiple-selection"
			help="This is a multiple selection tree autocomplete with allow create"
		>
			<Autocomplete
				id="allow-create-mutiple-selection"
				allowCreate
				multiple
				items={ items }
				selected={ selected }
				onSelect={ ( value ) => {
					setSelected( ( prev ) => {
						if ( Array.isArray( value ) ) {
							const newValues = value.filter(
								( item ) =>
									! prev.some(
										( { value } ) => value === item.value
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
										( { value } ) => value === curr.value
								  )
								: curr.value !== value.value
						)
					)
				}
				onInputChange={ ( value ) => {
					const result = filter( value, treeItems );
					setItems( result );
				} }
				onCreateClick={ ( value ) => {
					if ( value ) {
						const newItem = { value, label: value };
						treeItems.push( newItem );
						setItems( [ ...treeItems ] );
						setSelected( ( prev ) => [ ...prev, newItem ] );
					} else {
						alert( 'onCreateNew' );
					}
				} }
			/>
		</BaseControl>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/Autocomplete',
	component: Autocomplete,
};
