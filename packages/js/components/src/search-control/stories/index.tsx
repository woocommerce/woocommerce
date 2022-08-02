/**
 * External dependencies
 */
import React, { createElement } from 'react';
import { Spinner } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ItemType } from '../types';
import { MenuItem } from '../menu-item';
import { SearchControl } from '../';

const sampleItems = [
	{ value: 'apple', label: 'Apple' },
	{ value: 'pear', label: 'Pear' },
	{ value: 'orange', label: 'Orange' },
	{ value: 'grape', label: 'Grape' },
	{ value: 'banana', label: 'Banana' },
];

export const Single: React.FC = () => {
	const [ selected, setSelected ] = useState< ItemType >( null );

	return (
		<>
			Selected: { JSON.stringify( selected ) }
			<SearchControl
				items={ sampleItems }
				label="Single value"
				selected={ selected }
				onSelect={ ( item ) => setSelected( item ) }
				onRemove={ () => setSelected( null ) }
			/>
		</>
	);
};

export const Multiple: React.FC = () => {
	const [ selected, setSelected ] = useState< ItemType[] >( [] );

	return (
		<>
			<SearchControl
				hasMultiple
				items={ sampleItems }
				label="Multiple values"
				selected={ selected }
				onSelect={ ( item ) => setSelected( [ ...selected, item ] ) }
				onRemove={ ( item ) =>
					setSelected( selected.filter( ( i ) => i !== item ) )
				}
			/>
		</>
	);
};

export const FuzzyMatching: React.FC = () => {
	const [ selected, setSelected ] = useState< ItemType[] >( [] );

	const getFilteredItems = (
		allItems: ItemType[],
		inputValue: string,
		selectedItems: ItemType[]
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
		<SearchControl
			hasMultiple
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
	const [ selectedItem, setSelectedItem ] = useState< ItemType >( null );
	const [ fetchedItems, setFetchedItems ] = useState< ItemType[] >( [] );
	const [ isFetching, setIsFetching ] = useState( false );

	const fetchItems = ( value: string ) => {
		setIsFetching( true );
		setTimeout( () => {
			const results = sampleItems.sort( () => 0.5 - Math.random() );
			setFetchedItems( results );
			setIsFetching( false );
		}, 1500 );
	};

	return (
		<>
			<SearchControl
				label="Async"
				items={ fetchedItems }
				onInputChange={ fetchItems }
				selected={ selectedItem }
				onSelect={ ( item ) => setSelectedItem( item ) }
				onRemove={ () => setSelectedItem( null ) }
			>
				{ ( { items, isOpen, highlightedIndex, getItemProps } ) => {
					if ( ! isOpen ) {
						return null;
					}

					if ( isFetching ) {
						return <Spinner />;
					}

					return (
						<>
							{ items.map( ( item, index: number ) => (
								<MenuItem
									key={ `${ item.value }${ index }` }
									index={ index }
									isActive={ highlightedIndex === index }
									item={ item }
									getItemProps={ getItemProps }
								>
									{ item.label }
								</MenuItem>
							) ) }
						</>
					);
				} }
			</SearchControl>
		</>
	);
};

export const CustomRender: React.FC = () => {
	const [ selected, setSelected ] = useState( [] );

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
			<SearchControl
				hasMultiple
				label="Async"
				items={ sampleItems }
				getFilteredItems={ ( allItems ) => allItems }
				selected={ selected }
				onSelect={ onSelect }
				onRemove={ onRemove }
			>
				{ ( { items, highlightedIndex, getItemProps } ) => {
					return (
						<>
							{ items.map( ( item, index: number ) => {
								const isSelected = selected.includes( item );
								return (
									<li
										key={ `${ item.value }${ index }` }
										style={
											highlightedIndex === index
												? { backgroundColor: '#bde4ff' }
												: {}
										}
										{ ...getItemProps( {
											item,
											index,
										} ) }
									>
										<input
											type="checkbox"
											checked={ isSelected }
											value={ item.value }
											onChange={ () => null }
										/>
										<span
											style={ {
												fontWeight: isSelected
													? 'bold'
													: 'normal',
											} }
										>
											{ item.label }
										</span>
									</li>
								);
							} ) }
						</>
					);
				} }
			</SearchControl>
		</>
	);
};

export default {
	title: 'WooCommerce Admin/components/SearchControl',
	component: SearchControl,
};
