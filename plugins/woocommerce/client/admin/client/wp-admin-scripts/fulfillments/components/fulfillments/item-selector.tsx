/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FulfillmentLineItem from './fulfillment-line-item';
import { ItemSelection } from '../../utils/order-utils';
import { useFulfillmentContext } from '../../context/fulfillment-context';

type ItemSelectorProps = {
	editMode: boolean;
};

export default function ItemSelector( { editMode }: ItemSelectorProps ) {
	const { order, selectedItems, setSelectedItems } = useFulfillmentContext();

	const itemsCount = selectedItems.reduce(
		( acc, item ) => acc + item.selection.length,
		0
	);

	const selectedItemsCount = selectedItems.reduce(
		( acc, item ) =>
			acc +
			item.selection.filter( ( selection ) => selection.checked ).length,
		0
	);

	const clearSelectedItems = () => {
		setSelectedItems(
			selectedItems.map( ( item ) => ( {
				...item,
				selection: item.selection.map( ( selection ) => ( {
					...selection,
					checked: false,
				} ) ),
			} ) )
		);
	};

	const selectAllItems = () => {
		setSelectedItems(
			selectedItems.map( ( item ) => ( {
				...item,
				selection: item.selection.map( ( selection ) => ( {
					...selection,
					checked: true,
				} ) ),
			} ) )
		);
	};

	const handleToggleItem = (
		id: number,
		index: number,
		checked: boolean
	) => {
		if ( index < 0 ) {
			// If the index is negative, it means we are trying to toggle the whole item.
			// We will toggle all selections for this item.
			setSelectedItems( [
				...selectedItems.map( ( item ) => {
					if ( item.item_id === id ) {
						return {
							...item,
							selection: item.selection.map( ( selection ) => ( {
								...selection,
								checked,
							} ) ),
						};
					}
					return item;
				} ),
			] );
			return;
		}
		setSelectedItems( [
			...selectedItems.map( ( item ) => {
				if ( item.item_id === id ) {
					item.selection.map( ( selection ) => {
						if ( selection.index === index ) {
							selection.checked = checked;
						}
						return selection;
					} );
				}
				return item;
			} ),
		] );
	};

	const isChecked = ( id: number, index: number ) => {
		if ( index < 0 ) {
			// If the index is negative, it means we are trying to determine if the whole item is checked.
			return selectedItems.some(
				( item ) =>
					item.item_id === id &&
					item.selection.every( ( selection ) => selection.checked )
			);
		}
		const _item = selectedItems.find( ( item ) => item.item_id === id );
		if ( ! _item ) {
			return false;
		}
		const _selection = _item.selection.find(
			( selection ) => selection.index === index
		);
		return _selection ? _selection.checked : false;
	};

	const isIndeterminate = ( id: number ) => {
		const _item = selectedItems.find( ( item ) => item.item_id === id );
		if ( ! _item ) {
			return false;
		}
		const checkedCount = _item.selection.filter(
			( selection ) => selection.checked
		).length;
		return checkedCount > 0 && checkedCount < _item.selection.length;
	};

	return (
		<ul className="woocommerce-fulfillment-item-list">
			<li>
				<div className="woocommerce-fulfillment-item-bulk-select">
					{ editMode && (
						<CheckboxControl
							onChange={ () => {
								if ( selectedItemsCount === itemsCount ) {
									clearSelectedItems();
								} else {
									selectAllItems();
								}
							} }
							checked={ selectedItemsCount === itemsCount }
							indeterminate={
								selectedItemsCount > 0 &&
								selectedItemsCount < itemsCount
							}
							__nextHasNoMarginBottom
						/>
					) }
					<div className="woocommerce-fulfillment-item-bulk-select__label">
						{ sprintf(
							/* translators: %s: number of selected items */
							_n(
								'%s selected',
								'%s selected',
								selectedItemsCount,
								'woocommerce'
							),
							selectedItemsCount
						) }
					</div>
				</div>
			</li>
			{ selectedItems.map( ( item: ItemSelection ) => (
				<li key={ item.item_id }>
					<FulfillmentLineItem
						item={ item.item }
						quantity={ item.selection.length }
						editMode={ editMode }
						currency={ order?.currency ?? '' }
						toggleItem={ handleToggleItem }
						isChecked={ isChecked }
						isIndeterminate={ isIndeterminate }
					/>
				</li>
			) ) }
		</ul>
	);
}
