/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { SearchListControl } from '@woocommerce/editor-components/search-list-control';
import { SelectControl } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import type { SearchListItem as SearchListItemProps } from '../search-list-control/types';
import ProductTagItem from './product-tag-item';
import type { ProductTagControlProps } from './types';
import { getProductTags } from '../utils';
import './style.scss';

/**
 * Component to handle searching and selecting product tags.
 */
const ProductTagControl = ( {
	isCompact = false,
	onChange,
	onOperatorChange,
	operator = 'any',
	selected,
}: ProductTagControlProps ): JSX.Element => {
	const [ list, setList ] = useState< SearchListItemProps[] >( [] );
	const [ loading, setLoading ] = useState( true );
	const [ isMounted, setIsMounted ] = useState( false );
	const limitTags = getSetting( 'limitTags', false );

	const selectedTags = useMemo< SearchListItemProps[] >( () => {
		return list.filter( ( item ) => selected.includes( item.id ) );
	}, [ list, selected ] );

	const onSearch = useCallback(
		( search: string ) => {
			setLoading( true );
			getProductTags( { selected, search } )
				.then( ( newList ) => {
					setList( newList );
					setLoading( false );
				} )
				.catch( () => {
					setLoading( false );
				} );
		},
		[ selected ]
	);

	// Load on mount.
	useEffect( () => {
		if ( isMounted ) {
			return;
		}
		onSearch( '' );
		setIsMounted( true );
	}, [ onSearch, isMounted ] );

	const debouncedOnSearch = useDebouncedCallback( onSearch, 400 );

	const messages = {
		clear: __( 'Clear all product tags', 'woo-gutenberg-products-block' ),
		list: __( 'Product Tags', 'woo-gutenberg-products-block' ),
		noItems: __(
			'You have not set up any product tags on your store.',
			'woo-gutenberg-products-block'
		),
		search: __( 'Search for product tags', 'woo-gutenberg-products-block' ),
		selected: ( n: number ) =>
			sprintf(
				/* translators: %d is the count of selected tags. */
				_n(
					'%d tag selected',
					'%d tags selected',
					n,
					'woo-gutenberg-products-block'
				),
				n
			),
		updated: __(
			'Tag search results updated.',
			'woo-gutenberg-products-block'
		),
	};

	return (
		<>
			<SearchListControl
				className="woocommerce-product-tags"
				list={ list }
				isLoading={ loading }
				selected={ selectedTags }
				onChange={ onChange }
				onSearch={ limitTags ? debouncedOnSearch : undefined }
				renderItem={ ProductTagItem }
				messages={ messages }
				isCompact={ isCompact }
				isHierarchical
				isSingle={ false }
			/>
			{ !! onOperatorChange && (
				<div hidden={ selected.length < 2 }>
					<SelectControl
						className="woocommerce-product-tags__operator"
						label={ __(
							'Display products matching',
							'woo-gutenberg-products-block'
						) }
						help={ __(
							'Pick at least two tags to use this setting.',
							'woo-gutenberg-products-block'
						) }
						value={ operator }
						onChange={ onOperatorChange }
						options={ [
							{
								label: __(
									'Any selected tags',
									'woo-gutenberg-products-block'
								),
								value: 'any',
							},
							{
								label: __(
									'All selected tags',
									'woo-gutenberg-products-block'
								),
								value: 'all',
							},
						] }
					/>
				</div>
			) }
		</>
	);
};

export default ProductTagControl;
