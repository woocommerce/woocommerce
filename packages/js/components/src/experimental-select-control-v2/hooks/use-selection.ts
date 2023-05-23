/**
 * Internal dependencies
 */
import { DefaultItem, getItemLabelType } from '../types';

type useSelectionProps< Item > = {
	getItemLabel: getItemLabelType< Item >;
	onDeselect?: ( item: Item ) => void;
	multiple: boolean;
	options: Item[];
	onSelect?: ( item: Item ) => void;
	selected: Item | Item[] | null;
	setInputValue: ( value: string ) => void;
};

export function useSelection< Item = DefaultItem >( {
	getItemLabel,
	multiple,
	onDeselect = () => null,
	onSelect = () => null,
	setInputValue,
}: useSelectionProps< Item > ) {
	function deselectItem( item: Item ) {
		onDeselect( item );
	}

	function selectItem( item: Item ) {
		onSelect( item );
		if ( ! multiple ) {
			setInputValue( getItemLabel( item ) );
		}
	}

	return {
		deselectItem,
		selectItem,
	};
}
