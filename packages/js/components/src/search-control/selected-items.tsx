/**
 * External dependencies
 */
import { createElement } from 'react';

/**
 * Internal dependencies
 */
import { ItemType } from './types';

type SelectedItemsProps = {
	items: ItemType[];
	itemToString: ( item: ItemType | null ) => string;
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore These are the types provided by Downshift.
	getSelectedItemProps: ( { selectedItem: any, index: any } ) => {
		[ key: string ]: string;
	};
	removeSelectedItem: ( item: ItemType ) => void;
};

export const SelectedItems = ( {
	items,
	itemToString,
	getSelectedItemProps,
	removeSelectedItem,
}: SelectedItemsProps ) => {
	return (
		<div className="woocommerce-search-control__selected-items">
			{ items.map( ( item, index ) => (
				<span
					key={ `selected-item-${ index }` }
					{ ...getSelectedItemProps( { selectedItem: item, index } ) }
				>
					{ itemToString( item ) }
					<button onClick={ () => removeSelectedItem( item ) }>
						&#10005;
					</button>
				</span>
			) ) }
		</div>
	);
};
