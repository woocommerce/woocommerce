/**
 * External dependencies
 */
import { createElement } from 'react';

/**
 * Internal dependencies
 */
import { ItemType } from './types';
import Tag from '../tag';

type SelectedItemsProps = {
	items: ItemType[];
	itemToString: ( item: ItemType | null ) => string;
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore These are the types provided by Downshift.
	getSelectedItemProps: ( { selectedItem: any, index: any } ) => {
		[ key: string ]: string;
	};
	onRemove: ( item: ItemType ) => void;
};

export const SelectedItems = ( {
	items,
	itemToString,
	getSelectedItemProps,
	onRemove,
}: SelectedItemsProps ) => {
	return (
		<div className="woocommerce-experimental-select-control__selected-items">
			{ items.map( ( item, index ) => (
				<span
					key={ `selected-item-${ index }` }
					className="woocommerce-experimental-select-control__selected-item"
					{ ...getSelectedItemProps( {
						selectedItem: item,
						index,
					} ) }
				>
					{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
					{ /* @ts-ignore Additional props are not required. */ }
					<Tag
						id={ item.value }
						remove={ () => () => onRemove( item ) }
						label={ itemToString( item ) }
					/>
				</span>
			) ) }
		</div>
	);
};
