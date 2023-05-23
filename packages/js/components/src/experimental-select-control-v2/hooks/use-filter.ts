/**
 * External dependencies
 */
import { useMemo } from 'react';

/**
 * Internal dependencies
 */
import { DefaultItem, getItemLabelType } from '../types';

type useFilterProps< Item > = {
	getItemLabel: getItemLabelType< Item >;
	inputValue: string;
	options: Item[];
	selected: Item[];
};

export function useFilter< Item = DefaultItem >( {
	getItemLabel,
	inputValue,
	options,
	selected,
}: useFilterProps< Item > ) {
	function getFilteredOptions(): Item[] {
		const escapedInputValue = inputValue.replace(
			/[.*+?^${}()|[\]\\]/g,
			'\\$&'
		);
		const re = new RegExp( escapedInputValue, 'gi' );

		return options.filter( ( item ) => {
			return (
				selected.indexOf( item ) < 0 &&
				re.test( getItemLabel( item ).toLowerCase() )
			);
		} );
	}

	return {
		filteredOptions: useMemo( getFilteredOptions, [
			getItemLabel,
			inputValue,
			options,
			selected,
		] ),
	};
}
