/**
 * External dependencies
 */
import { createElement } from 'react';

/**
 * Internal dependencies
 */
import Tag from '../tag';

type SelectedItemsProps< ItemType > = {
	items: ItemType[];
	getItemLabel: ( item: ItemType | null ) => string;
	getItemValue: ( item: ItemType | null ) => string;
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore These are the types provided by Downshift.
	getSelectedItemProps: ( { selectedItem: any, index: any } ) => {
		[ key: string ]: string;
	};
	onRemove: ( item: ItemType ) => void;
};

export const SelectedItems = < ItemType, >( {
	items,
	getItemLabel,
	getItemValue,
	getSelectedItemProps,
	onRemove,
}: SelectedItemsProps< ItemType > ) => {
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
						id={ getItemValue( item ) }
						remove={ () => () => onRemove( item ) }
						label={ getItemLabel( item ) }
					/>
				</span>
			) ) }
		</div>
	);
};
