/**
 * External dependencies
 */
import { CheckboxControl, Spinner } from '@wordpress/components';
import React, { createElement } from 'react';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SelectedType, DefaultItemType } from '../types';
import { MenuItem } from '../menu-item';
import { SelectControl } from '../';
import { Menu } from '../menu';

const sampleItems = [
	{ value: 'apple', label: 'Apple' },
	{ value: 'pear', label: 'Pear' },
	{ value: 'orange', label: 'Orange' },
	{ value: 'grape', label: 'Grape' },
	{ value: 'banana', label: 'Banana' },
];

export const Single: React.FC = () => {
	const [ selected, setSelected ] =
		useState< SelectedType< DefaultItemType > >( null );

	return (
		<>
			Selected: { JSON.stringify( selected ) }
			<SelectControl
				items={ sampleItems }
				label="Single value"
				selected={ selected }
				onSelect={ ( item ) => item && setSelected( item ) }
				onRemove={ () => setSelected( null ) }
			/>
		</>
	);
};

export const Multiple: React.FC = () => {
	const [ selected, setSelected ] = useState< DefaultItemType[] >( [] );

	return (
		<>
			<SelectControl
				multiple
				items={ sampleItems }
				label="Multiple values"
				selected={ selected }
				onSelect={ ( item ) =>
					Array.isArray( selected ) &&
					setSelected( [ ...selected, item ] )
				}
				onRemove={ ( item ) =>
					setSelected( selected.filter( ( i ) => i !== item ) )
				}
			/>
		</>
	);
};

export const FuzzyMatching: React.FC = () => {
	const [ selected, setSelected ] = useState< DefaultItemType[] >( [] );

	const getFilteredItems = (
		allItems: DefaultItemType[],
		inputValue: string,
		selectedItems: DefaultItemType[]
	) => {
		const pattern =
			'.*' + inputValue.toLowerCase().split( '' ).join( '.*' ) + '.*';
		const re = new RegExp( pattern );

		return allItems.filter( ( item ) => {
			if ( selectedItems.indexOf( item ) >= 0 ) {
				return false;
			}
			return re.test( item.label.toLowerCase() );
		} );
	};

	return (
		<SelectControl
			multiple
			getFilteredItems={ getFilteredItems }
			items={ sampleItems }
			label="Fuzzy matching"
			selected={ selected }
			onSelect={ ( item ) => setSelected( [ ...selected, item ] ) }
			onRemove={ ( item ) =>
				setSelected( selected.filter( ( i ) => i !== item ) )
			}
		/>
	);
};

export const Async: React.FC = () => {
	const [ selectedItem, setSelectedItem ] =
		useState< SelectedType< DefaultItemType > >( null );
	const [ fetchedItems, setFetchedItems ] = useState< DefaultItemType[] >(
		[]
	);
	const [ isFetching, setIsFetching ] = useState( false );

	const fetchItems = ( value: string | undefined ) => {
		setIsFetching( true );
		setTimeout( () => {
			const results = sampleItems.sort( () => 0.5 - Math.random() );
			setFetchedItems( results );
			setIsFetching( false );
		}, 1500 );
	};

	return (
		<>
			<SelectControl
				label="Async"
				items={ fetchedItems }
				onInputChange={ fetchItems }
				selected={ selectedItem }
				onSelect={ ( item ) => setSelectedItem( item ) }
				onRemove={ () => setSelectedItem( null ) }
				placeholder="Start typing..."
			>
				{ ( {
					items,
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
								items.map( ( item, index: number ) => (
									<MenuItem
										key={ `${ item.value }${ index }` }
										index={ index }
										isActive={ highlightedIndex === index }
										item={ item }
										getItemProps={ getItemProps }
									>
										{ item.label }
									</MenuItem>
								) )
							) }
						</Menu>
					);
				} }
			</SelectControl>
		</>
	);
};

export const CustomRender: React.FC = () => {
	const [ selected, setSelected ] = useState< DefaultItemType[] >( [] );

	const onRemove = ( item ) => {
		setSelected( selected.filter( ( i ) => i !== item ) );
	};

	const onSelect = ( item ) => {
		const isSelected = selected.find( ( i ) => i === item );
		if ( isSelected ) {
			onRemove( item );
			return;
		}
		setSelected( [ ...selected, item ] );
	};

	return (
		<>
			<SelectControl
				multiple
				label="Custom render"
				items={ sampleItems }
				getFilteredItems={ ( allItems ) => allItems }
				selected={ selected }
				onSelect={ onSelect }
				onRemove={ onRemove }
			>
				{ ( {
					items,
					highlightedIndex,
					getItemProps,
					getMenuProps,
				} ) => {
					return (
						<Menu isOpen={ true } getMenuProps={ getMenuProps }>
							{ items.map( ( item, index: number ) => {
								const isSelected = selected.includes( item );

								return (
									<MenuItem
										key={ `${ item.value }${ index }` }
										index={ index }
										isActive={ highlightedIndex === index }
										item={ item }
										getItemProps={ getItemProps }
									>
										<>
											<CheckboxControl
												onChange={ () => null }
												checked={ isSelected }
												label={
													<span
														style={ {
															fontWeight:
																isSelected
																	? 'bold'
																	: 'normal',
														} }
													>
														{ item.label }
													</span>
												}
											/>
										</>
									</MenuItem>
								);
							} ) }
						</Menu>
					);
				} }
			</SelectControl>
		</>
	);
};

type CustomItemType = {
	itemId: number;
	user: {
		name: string;
		email?: string;
		id: number;
	};
};

const customItems: CustomItemType[] = [
	{
		itemId: 1,
		user: {
			name: 'Joe',
			email: 'joe@a8c.com',
			id: 32,
		},
	},
	{
		itemId: 2,
		user: {
			name: 'Jen',
			id: 16,
		},
	},
	{
		itemId: 3,
		user: {
			name: 'Jared',
			id: 112,
		},
	},
];

export const CustomItemType: React.FC = () => {
	const [ selected, setSelected ] =
		useState< SelectedType< Array< CustomItemType > > >( null );

	return (
		<>
			Selected: { JSON.stringify( selected ) }
			<SelectControl< CustomItemType >
				multiple
				items={ customItems }
				label="CustomItemType value"
				selected={ selected }
				onSelect={ ( item ) =>
					setSelected(
						Array.isArray( selected )
							? [ ...selected, item ]
							: [ item ]
					)
				}
				onRemove={ ( item ) =>
					setSelected( selected.filter( ( i ) => i !== item ) )
				}
				getItemLabel={ ( item ) => item?.user.name }
				getItemValue={ ( item ) => String( item?.itemId ) }
			/>
		</>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/SelectControl',
	component: SelectControl,
};
