/**
 * External dependencies
 */
import { CheckboxControl, Spinner } from '@wordpress/components';
import React from 'react';
import { createElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SelectedType, DefaultItemType, getItemLabelType } from '../types';
import { MenuItem } from '../menu-item';
import { SelectControl, selectControlStateChangeTypes } from '../';
import { AsyncSelectControl } from '../async-select-control';
import { Menu } from '../menu';

const sampleItems = [
	{ value: 'apple', label: 'Apple' },
	{ value: 'pear', label: 'Pear' },
	{ value: 'orange', label: 'Orange' },
	{ value: 'grape', label: 'Grape' },
	{ value: 'banana', label: 'Banana' },
];

export const Single: React.FC = () => {
	const [ selected, setSelected ] = useState<
		SelectedType< DefaultItemType >
	>( sampleItems[ 1 ] );

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
	const [ selected, setSelected ] = useState< DefaultItemType[] >( [
		sampleItems[ 0 ],
		sampleItems[ 2 ],
	] );

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

export const ExternalTags: React.FC = () => {
	const [ selected, setSelected ] = useState< DefaultItemType[] >( [] );

	return (
		<>
			<SelectControl
				multiple
				hasExternalTags
				items={ sampleItems }
				label="External tags"
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

	const fetchItems = ( value: string | undefined ) => {
		return new Promise< DefaultItemType[] >( ( resolve ) => {
			setTimeout( () => {
				const results = sampleItems
					.filter( ( item ) =>
						item.label
							.toLowerCase()
							.includes( value?.toLowerCase() || '' )
					)
					.sort( () => 0.5 - Math.random() );
				resolve( results );
			}, 1500 );
		} );
	};

	return (
		<>
			<AsyncSelectControl
				label="Async"
				onSearch={ fetchItems }
				selected={ selectedItem }
				onSelect={ ( item ) => setSelectedItem( item ) }
				onRemove={ () => setSelectedItem( null ) }
				placeholder="Start typing..."
			></AsyncSelectControl>
		</>
	);
};

export const AsyncWithClientSideFiltering: React.FC = () => {
	const [ selectedItem, setSelectedItem ] =
		useState< SelectedType< DefaultItemType > >( null );
	const [ total, setTotal ] = useState< number | undefined >( undefined );

	const fetchItems = ( value: string | undefined ) => {
		return new Promise< DefaultItemType[] >( ( resolve ) => {
			setTimeout( () => {
				const results = sampleItems
					.filter( ( item ) =>
						item.label
							.toLowerCase()
							.includes( value?.toLowerCase() || '' )
					)
					.sort( () => 0.5 - Math.random() );
				if ( ! value ) {
					setTotal( results.length );
				}
				resolve( results );
			}, 1500 );
		} );
	};

	return (
		<>
			<AsyncSelectControl
				label="Async"
				pageSize={ 50 }
				total={ total }
				onSearch={ fetchItems }
				selected={ selectedItem }
				onSelect={ ( item ) => setSelectedItem( item ) }
				onRemove={ () => setSelectedItem( null ) }
				placeholder="Start typing..."
			></AsyncSelectControl>
		</>
	);
};

export const CustomRender: React.FC = () => {
	const [ selected, setSelected ] = useState< DefaultItemType[] >( [
		sampleItems[ 0 ],
	] );

	const onRemove = ( item ) => {
		setSelected( selected.filter( ( i ) => i !== item ) );
	};

	const onSelect = ( item ) => {
		const isSelected = selected.find( ( i ) => i.value === item.value );
		if ( isSelected ) {
			onRemove( item );
			return;
		}
		setSelected( [ ...selected, item ] );
	};

	const getFilteredItems = (
		allItems: DefaultItemType[],
		inputValue: string,
		selectedItems: DefaultItemType[],
		getItemLabel: getItemLabelType< DefaultItemType >
	) => {
		const escapedInputValue = inputValue.replace(
			/[.*+?^${}()|[\]\\]/g,
			'\\$&'
		);
		const re = new RegExp( escapedInputValue, 'gi' );

		return allItems.filter( ( item ) => {
			return re.test( getItemLabel( item ).toLowerCase() );
		} );
	};

	return (
		<>
			<SelectControl
				multiple
				label="Custom render"
				items={ sampleItems }
				selected={ selected }
				onSelect={ onSelect }
				onRemove={ onRemove }
				getFilteredItems={ getFilteredItems }
				stateReducer={ ( state, actionAndChanges ) => {
					const { changes, type } = actionAndChanges;
					switch ( type ) {
						case selectControlStateChangeTypes.ControlledPropUpdatedSelectedItem:
							return {
								...changes,
								inputValue: state.inputValue,
							};
						case selectControlStateChangeTypes.ItemClick:
							return {
								...changes,
								isOpen: true,
								inputValue: state.inputValue,
								highlightedIndex: state.highlightedIndex,
							};
						default:
							return changes;
					}
				} }
			>
				{ ( {
					items,
					highlightedIndex,
					getItemProps,
					getMenuProps,
					isOpen,
				} ) => {
					return (
						<Menu isOpen={ isOpen } getMenuProps={ getMenuProps }>
							{ items.map( ( item, index: number ) => {
								const isSelected = selected.includes( item );

								return (
									<MenuItem
										key={ `${ item.value }` }
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
	const [ selected, setSelected ] = useState<
		SelectedType< Array< CustomItemType > >
	>( [] );

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
					setSelected( selected?.filter( ( i ) => i !== item ) || [] )
				}
				getItemLabel={ ( item ) => item?.user.name || '' }
				getItemValue={ ( item ) => String( item?.itemId ) }
			/>
		</>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/SelectControl',
	component: SelectControl,
};
