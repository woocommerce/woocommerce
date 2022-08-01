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
import { SearchControl } from '../';

const sampleItems = [
	{ value: 'apple', label: 'Apple' },
	{ value: 'pear', label: 'Pear' },
	{ value: 'orange', label: 'Orange' },
	{ value: 'grape', label: 'Grape' },
	{ value: 'banana', label: 'Banana' },
];

export const Single: React.FC = () => (
	<>
		<SearchControl items={ sampleItems } label="Single value" />
	</>
);

export const Multiple: React.FC = () => (
	<>
		<SearchControl
			hasMultiple
			items={ sampleItems }
			label="Multiple values"
		/>
	</>
);

export const FuzzyMatching: React.FC = () => {
	const getFilteredItems = (
		allItems: ItemType[],
		selectedItems: ItemType[],
		inputValue: string
	) => {
		const pattern =
			'.*' + inputValue.toLowerCase().split( '' ).join( '.*' ) + '.*';
		const re = new RegExp( pattern );

		return allItems.filter( ( item ) => {
			return re.test( item.label.toLowerCase() );
		} );
	};

	return (
		<SearchControl
			hasMultiple
			getFilteredItems={ getFilteredItems }
			items={ sampleItems }
			label="Fuzzy matching"
		/>
	);
};

export const Async: React.FC = () => {
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
								<li
									style={
										highlightedIndex === index
											? { backgroundColor: '#bde4ff' }
											: {}
									}
									key={ `${ item.value }${ index }` }
									{ ...getItemProps( { item, index } ) }
								>
									{ item.label }
								</li>
							) ) }
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
