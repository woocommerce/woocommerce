/**
 * External dependencies
 */

import React, { createElement } from 'react';

/**
 * Internal dependencies
 */
import { SelectTree } from '../select-tree';
import { Item } from '../../experimental-tree-control/types';

const listItems: Item[] = [
	{ id: '1', name: 'Technology' },
	{ id: '1.1', name: 'Notebooks', parent: '1' },
	{ id: '1.2', name: 'Phones', parent: '1' },
	{ id: '1.2.1', name: 'iPhone', parent: '1.2' },
	{ id: '1.2.1.1', name: 'iPhone 14 Pro', parent: '1.2.1' },
	{ id: '1.2.1.2', name: 'iPhone 14 Pro Max', parent: '1.2.1' },
	{ id: '1.2.2', name: 'Samsung', parent: '1.2' },
	{ id: '1.2.2.1', name: 'Samsung Galaxy 22 Plus', parent: '1.2.2' },
	{ id: '1.2.2.2', name: 'Samsung Galaxy 22 Ultra', parent: '1.2.2' },
	{ id: '1.3', name: 'Wearables', parent: '1' },
	{ id: '2', name: 'Hardware' },
	{ id: '2.1', name: 'CPU', parent: '2' },
	{ id: '2.2', name: 'GPU', parent: '2' },
	{ id: '2.3', name: 'Memory RAM', parent: '2' },
	{ id: '3', name: 'Other' },
];

const getFilteredItems = ( items: Item[], searchValue ) => {
	const filteredItems = items.filter( ( e ) =>
		e.name.includes( searchValue )
	);
	const itemsToIterate = [ ...filteredItems ];
	while ( itemsToIterate.length > 0 ) {
		// The filter should include the parents of the filtered items
		const element = itemsToIterate.pop();
		if ( element ) {
			const parent = listItems.find( ( x ) => x.id === element.parent );
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

	const items = getFilteredItems( listItems, value );

	return (
		<SelectTree
			label="Multiple Select Tree"
			multiple
			items={ items }
			selected={ selected }
			shouldNotRecursivelySelect
			shouldShowCreateButton={ ( typedValue ) =>
				! value ||
				listItems.findIndex( ( item ) => item.name === typedValue ) ===
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
									( { id: removedValue } ) =>
										item.id === removedValue
								)
					  )
					: selected.filter(
							( item ) => item.id !== removedItems.id
					  );
				setSelected( newValues );
			} }
		/>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/SelectTreeControl',
	component: SelectTree,
};
