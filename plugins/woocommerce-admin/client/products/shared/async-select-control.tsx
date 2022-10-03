/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenuItem as MenuItem,
	__experimentalSelectControlMenu as Menu,
	Spinner,
} from '@woocommerce/components';

type AsyncSelectControlProps< ItemType > = {
	label: string;
	items: ItemType[];
	placeholder: string;
	onSearch: ( value: string | undefined ) => Promise< ItemType[] >;
	selected: ItemType | ItemType[] | null;
	onRemove?: ( item: ItemType ) => void;
	onSelect?: ( selected: ItemType ) => void;
	multiple?: boolean;
	disabled?: boolean;
	getItemLabel: ( item: ItemType | null ) => string;
	getItemValue: ( item: ItemType | null ) => string | number;
};

export function AsyncSelectControl< ItemType >( {
	items,
	label,
	disabled,
	placeholder,
	onSearch,
	selected,
	onRemove,
	onSelect,
	multiple,
	getItemLabel,
	getItemValue,
}: AsyncSelectControlProps< ItemType > ) {
	const [ fetchedItems, setFetchedItems ] = useState< ItemType[] >( items );
	const [ isFetching, setIsFetching ] = useState( false );

	const fetchItems = ( value: string | undefined ) => {
		setIsFetching( true );
		onSearch( value ).then(
			( results ) => {
				setFetchedItems( results );
				setIsFetching( false );
			},
			() => {
				setIsFetching( false );
			}
		);
	};

	return (
		<>
			<SelectControl< ItemType >
				label={ label }
				multiple={ multiple }
				getFilteredItems={ ( allItems ) => {
					return allItems;
				} }
				disabled={ disabled }
				items={ fetchedItems }
				onInputChange={ fetchItems }
				selected={ selected }
				onSelect={ onSelect }
				onRemove={ onRemove }
				placeholder={ placeholder }
				getItemLabel={ getItemLabel }
				getItemValue={ getItemValue }
			>
				{ ( {
					items: selectControlItems,
					isOpen,
					highlightedIndex,
					getItemProps,
					getMenuProps,
				} ) => {
					return (
						<Menu isOpen={ isOpen } getMenuProps={ getMenuProps }>
							{ isFetching ? (
								<Spinner />
							) : (
								selectControlItems.map(
									( item, index: number ) => (
										<MenuItem
											key={ `${ getItemValue( item ) }` }
											index={ index }
											isActive={
												highlightedIndex === index
											}
											item={ item }
											getItemProps={ getItemProps }
										>
											{ getItemLabel( item ) }
										</MenuItem>
									)
								)
							) }
						</Menu>
					);
				} }
			</SelectControl>
		</>
	);
}
