/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { SearchListControl } from '@woocommerce/editor-components/search-list-control';
import { SelectControl } from '@wordpress/components';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import type { SearchListItem as SearchListItemProps } from '../search-list-control/types';
import ProductBrandItem from './product-brand-item';
import type { ProductBrandControlProps } from './types';
import { getProductBrands } from '../utils';
import './style.scss';

/**
 * Component to handle searching and selecting product brands.
 */
const ProductBrandControl = ( {
	isCompact = false,
	onChange,
	onOperatorChange,
	operator = 'any',
	selected,
}: ProductBrandControlProps ): JSX.Element => {
	const [ list, setList ] = useState< SearchListItemProps[] >( [] );
	const [ loading, setLoading ] = useState( true );
	const [ isMounted, setIsMounted ] = useState( false );

	const selectedBrands = useMemo< SearchListItemProps[] >( () => {
		return list.filter( ( item ) => selected.includes( item.id ) );
	}, [ list, selected ] );

	const onSearch = useCallback(
		( search: string ) => {
			setLoading( true );
			getProductBrands( { selected, search } )
				.then( ( newList ) => {
					setList( newList as SearchListItemProps[] );
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
		clear: __( 'Clear all product brands', 'woocommerce' ),
		list: __( 'Product Brands', 'woocommerce' ),
		noItems: __(
			'You have not set up any product brands on your store.',
			'woocommerce'
		),
		search: __( 'Search for product brands', 'woocommerce' ),
		selected: ( n: number ) =>
			sprintf(
				/* translators: %d is the count of selected brands. */
				_n(
					'%d brand selected',
					'%d brands selected',
					n,
					'woocommerce'
				),
				n
			),
		updated: __( 'Brand search results updated.', 'woocommerce' ),
	};

	return (
		<>
			<SearchListControl
				className="woocommerce-product-brands"
				list={ list }
				isLoading={ loading }
				selected={ selectedBrands }
				onChange={ onChange }
				onSearch={ debouncedOnSearch }
				renderItem={ ProductBrandItem }
				messages={ messages }
				isCompact={ isCompact }
				isHierarchical
				isSingle={ false }
			/>
			{ !! onOperatorChange && (
				<div hidden={ selected.length < 2 }>
					<SelectControl
						className="woocommerce-product-brands__operator"
						label={ __(
							'Display products matching',
							'woocommerce'
						) }
						help={ __(
							'Pick at least two brands to use this setting.',
							'woocommerce'
						) }
						value={ operator }
						onChange={ onOperatorChange }
						options={ [
							{
								label: __( 'Any selected brands', 'woocommerce' ),
								value: 'any',
							},
							{
								label: __( 'All selected brands', 'woocommerce' ),
								value: 'all',
							},
						] }
					/>
				</div>
			) }
		</>
	);
};

export default ProductBrandControl;
