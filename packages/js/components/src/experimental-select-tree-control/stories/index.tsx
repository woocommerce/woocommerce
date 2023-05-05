/**
 * External dependencies
 */
import React, { createElement, useState } from 'react';
import { Button, Modal, SlotFillProvider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SelectTree } from '../select-tree';
import { Item } from '../../experimental-tree-control/types';
import { SelectTreeMenuSlot } from '../select-tree-menu';

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

const filterItems = ( items: Item[], searchValue ) => {
	const filteredItems = items.filter( ( e ) =>
		e.label.includes( searchValue )
	);
	const itemsToIterate = [ ...filteredItems ];
	while ( itemsToIterate.length > 0 ) {
		// The filter should include the parents of the filtered items
		const element = itemsToIterate.pop();
		if ( element ) {
			const parent = listItems.find(
				( item ) => item.value === element.parent
			);
			if ( parent && ! filteredItems.includes( parent ) ) {
				filteredItems.push( parent );
				itemsToIterate.push( parent );
			}
		}
	}
	return filteredItems;
};

export const MultipleSelectTree: React.FC = () => {
	const [ value, setValue ] = React.useState( '' );
	const [ selected, setSelected ] = React.useState< Item[] >( [] );

	const items = filterItems( listItems, value );

	return (
		<SelectTree
			id="multiple-select-tree"
			label="Multiple Select Tree"
			multiple
			items={ items }
			selected={ selected }
			shouldNotRecursivelySelect
			shouldShowCreateButton={ ( typedValue ) =>
				! value ||
				listItems.findIndex( ( item ) => item.label === typedValue ) ===
					-1
			}
			createValue={ value }
			// eslint-disable-next-line no-alert
			onCreateNew={ () => alert( 'create new called' ) }
			onInputChange={ ( a ) => setValue( a || '' ) }
			onSelect={ ( selectedItems ) => {
				if ( Array.isArray( selectedItems ) ) {
					setSelected( [ ...selected, ...selectedItems ] );
				}
			} }
			onRemove={ ( removedItems ) => {
				const newValues = Array.isArray( removedItems )
					? selected.filter(
							( item ) =>
								! removedItems.some(
									( { value: removedValue } ) =>
										item.value === removedValue
								)
					  )
					: selected.filter(
							( item ) => item.value !== removedItems.value
					  );
				setSelected( newValues );
			} }
		/>
	);
};

export const SingleWithinModalUsingBodyDropdownPlacement: React.FC = () => {
	const [ isOpen, setOpen ] = useState( true );
	const [ value, setValue ] = useState( '' );
	const [ selected, setSelected ] = useState< Item[] >( [] );

	const items = filterItems( listItems, value );

	return (
		<SlotFillProvider>
			Selected: { JSON.stringify( selected ) }
			<Button onClick={ () => setOpen( true ) }>
				Show Dropdown in Modal
			</Button>
			{ isOpen && (
				<Modal
					title="Dropdown Modal"
					onRequestClose={ () => setOpen( false ) }
				>
					<SelectTree
						id="multiple-select-tree"
						label="Multiple Select Tree"
						multiple
						items={ items }
						selected={ selected }
						shouldNotRecursivelySelect
						shouldShowCreateButton={ ( typedValue ) =>
							! value ||
							listItems.findIndex(
								( item ) => item.label === typedValue
							) === -1
						}
						createValue={ value }
						// eslint-disable-next-line no-alert
						onCreateNew={ () => alert( 'create new called' ) }
						onInputChange={ ( a ) => setValue( a || '' ) }
						onSelect={ ( selectedItems ) => {
							if ( Array.isArray( selectedItems ) ) {
								setSelected( [
									...selected,
									...selectedItems,
								] );
							}
						} }
						onRemove={ ( removedItems ) => {
							const newValues = Array.isArray( removedItems )
								? selected.filter(
										( item ) =>
											! removedItems.some(
												( { value: removedValue } ) =>
													item.value === removedValue
											)
								  )
								: selected.filter(
										( item ) =>
											item.value !== removedItems.value
								  );
							setSelected( newValues );
						} }
					/>
				</Modal>
			) }
			<SelectTreeMenuSlot />
		</SlotFillProvider>
	);
};

export const SingleSelectTree: React.FC = () => {
	const [ value, setValue ] = React.useState( '' );
	const [ selected, setSelected ] = React.useState< Item | undefined >();

	const items = filterItems( listItems, value );

	return (
		<SelectTree
			id="single-select-tree"
			label="Single Select Tree"
			items={ items }
			selected={ selected }
			shouldNotRecursivelySelect
			shouldShowCreateButton={ ( typedValue ) =>
				! value ||
				listItems.findIndex( ( item ) => item.label === typedValue ) ===
					-1
			}
			createValue={ value }
			// eslint-disable-next-line no-alert
			onCreateNew={ () => alert( 'create new called' ) }
			onInputChange={ ( a ) => setValue( a || '' ) }
			onSelect={ ( selectedItems ) => {
				setSelected( selectedItems as Item );
			} }
		/>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/SelectTreeControl',
	component: SelectTree,
};
