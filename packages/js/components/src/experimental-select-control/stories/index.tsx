/**
 * External dependencies
 */
import {
	Button,
	CheckboxControl,
	Modal,
	SlotFillProvider,
	Spinner,
} from '@wordpress/components';
import React, { useCallback } from 'react';
import { createElement, useState } from '@wordpress/element';
import { tag } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { SelectedType, DefaultItemType } from '../types';
import { MenuItem } from '../menu-item';
import {
	SelectControl,
	selectControlStateChangeTypes,
	useAsyncFilter,
	useSyncFilter,
} from '../';
import { Menu, MenuSlot } from '../menu';
import { SuffixIcon } from '../suffix-icon';

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
	const [ items, setItems ] = useState< DefaultItemType[] >( sampleItems );
	const [ selected, setSelected ] = useState< DefaultItemType[] >( [] );

	const filter = useCallback(
		( inputValue = '' ) => {
			const pattern =
				'.*' + inputValue.toLowerCase().split( '' ).join( '.*' ) + '.*';
			const re = new RegExp( pattern );

			return sampleItems.filter( ( item ) => {
				if ( selected.indexOf( item ) >= 0 ) {
					return false;
				}
				return re.test( item.label.toLowerCase() );
			} );
		},
		[ selected ]
	);

	const selectProps = useSyncFilter( {
		items,
		selected,
		label: 'Fuzzy matching',
		multiple: true,
		onSelect: ( item ) => setSelected( [ ...selected, item ] ),
		onRemove: ( item ) =>
			setSelected( selected.filter( ( i ) => i !== item ) ),
		filter,
		onFilterEnd: setItems,
	} );

	return <SelectControl { ...selectProps } />;
};

export const Async: React.FC = () => {
	const [ selectedItem, setSelectedItem ] =
		useState< SelectedType< DefaultItemType > >( null );
	const [ fetchedItems, setFetchedItems ] = useState< DefaultItemType[] >(
		[]
	);
	const [ isFetching, setIsFetching ] = useState( false );

	const filter = useCallback(
		( value = '' ) =>
			new Promise< DefaultItemType[] >( ( resolve ) => {
				setTimeout( () => {
					const results = sampleItems
						.sort( () => 0.5 - Math.random() )
						.filter( ( { label } ) =>
							label.toLowerCase().includes( value )
						);
					resolve( results );
				}, 500 );
			} ),
		[]
	);

	const selectProps = useAsyncFilter( {
		label: 'Async',
		items: fetchedItems,
		selected: selectedItem,
		onSelect: ( item ) => setSelectedItem( item ),
		onRemove: () => setSelectedItem( null ),
		placeholder: 'Start typing...',
		filter,
		onFilterStart() {
			setIsFetching( true );
			setFetchedItems( [] );
		},
		onFilterEnd( filteredItems: DefaultItemType[] ) {
			setFetchedItems( filteredItems );
			setIsFetching( false );
		},
	} );

	return (
		<>
			<SelectControl { ...selectProps }>
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
	const [ items, setItems ] = useState< DefaultItemType[] >( sampleItems );
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

	const filter = useCallback( ( inputValue = '' ) => {
		const escapedInputValue = inputValue.replace(
			/[.*+?^${}()|[\]\\]/g,
			'\\$&'
		);
		const re = new RegExp( escapedInputValue, 'gi' );

		return sampleItems.filter( ( item ) => {
			return re.test( item.label.toLowerCase() );
		} );
	}, [] );

	const selectProps = useSyncFilter( {
		multiple: true,
		label: 'Custom render',
		items,
		selected,
		onSelect,
		onRemove,
		stateReducer: ( state, actionAndChanges ) => {
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
		},
		filter,
		onFilterEnd: setItems,
	} );

	return (
		<>
			<SelectControl { ...selectProps }>
				{ ( {
					items: internalItems,
					highlightedIndex,
					getItemProps,
					getMenuProps,
					isOpen,
				} ) => {
					return (
						<Menu isOpen={ isOpen } getMenuProps={ getMenuProps }>
							{ internalItems.map( ( item, index: number ) => {
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

export const SingleWithinModalUsingBodyDropdownPlacement: React.FC = () => {
	const [ isOpen, setOpen ] = useState( true );
	const [ selected, setSelected ] =
		useState< SelectedType< DefaultItemType > >();
	const [ selectedTwo, setSelectedTwo ] =
		useState< SelectedType< DefaultItemType > >();

	return (
		<SlotFillProvider>
			Selected: { JSON.stringify( selected ) }
			<Button onClick={ () => setOpen( true ) }>
				Show Dropdown in Modal
			</Button>
			{ isOpen && (
				<Modal
					title="Dropdown Modal"
					onRequestClose={ () => setOpen( false ) }
				>
					<SelectControl
						items={ sampleItems }
						label="Single value"
						selected={ selected }
						onSelect={ ( item ) => item && setSelected( item ) }
						onRemove={ () => setSelected( null ) }
					/>
					<SelectControl
						items={ sampleItems }
						label="Single value"
						selected={ selectedTwo }
						onSelect={ ( item ) => item && setSelectedTwo( item ) }
						onRemove={ () => setSelectedTwo( null ) }
					/>
				</Modal>
			) }
			<MenuSlot />
		</SlotFillProvider>
	);
};

export const DefaultSuffix: React.FC = () => {
	const [ selected, setSelected ] = useState<
		SelectedType< DefaultItemType >
	>( sampleItems[ 1 ] );

	return (
		<SelectControl
			items={ sampleItems }
			label="Default suffix"
			selected={ selected }
			onSelect={ ( item ) => item && setSelected( item ) }
			onRemove={ () => setSelected( null ) }
		/>
	);
};

export const CustomSuffixIcon: React.FC = () => {
	const [ selected, setSelected ] = useState<
		SelectedType< DefaultItemType >
	>( sampleItems[ 1 ] );

	return (
		<SelectControl
			items={ sampleItems }
			label="Custom suffix icon"
			selected={ selected }
			onSelect={ ( item ) => item && setSelected( item ) }
			onRemove={ () => setSelected( null ) }
			suffix={ <SuffixIcon icon={ tag } /> }
		/>
	);
};

export const NoSuffix: React.FC = () => {
	const [ selected, setSelected ] = useState<
		SelectedType< DefaultItemType >
	>( sampleItems[ 1 ] );

	return (
		<SelectControl
			items={ sampleItems }
			label="No suffix"
			selected={ selected }
			onSelect={ ( item ) => item && setSelected( item ) }
			onRemove={ () => setSelected( null ) }
			suffix={ null }
		/>
	);
};

export const CustomSuffix: React.FC = () => {
	const [ selected, setSelected ] = useState<
		SelectedType< DefaultItemType >
	>( sampleItems[ 1 ] );

	return (
		<SelectControl
			items={ sampleItems }
			label="Custom suffix"
			selected={ selected }
			onSelect={ ( item ) => item && setSelected( item ) }
			onRemove={ () => setSelected( null ) }
			suffix={
				<div style={ { background: 'red', height: '100%' } }>
					Suffix!
				</div>
			}
		/>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/SelectControl',
	component: SelectControl,
};
