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
		clear: __( 'Clear all product tags', 'woocommerce' ),
		list: __( 'Product Tags', 'woocommerce' ),
		noItems: __(
			'You have not set up any product tags on your store.',
			'woocommerce'
		),
		search: __( 'Search for product tags', 'woocommerce' ),
		selected: ( n: number ) =>
			sprintf(
				/* translators: %d is the count of selected tags. */
				_n( '%d tag selected', '%d tags selected', n, 'woocommerce' ),
				n
			),
		updated: __( 'Tag search results updated.', 'woocommerce' ),
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
							'woocommerce'
						) }
						help={ __(
							'Pick at least two tags to use this setting.',
							'woocommerce'
						) }
						value={ operator }
						onChange={ onOperatorChange }
						options={ [
							{
								label: __( 'Any selected tags', 'woocommerce' ),
								value: 'any',
							},
							{
								label: __( 'All selected tags', 'woocommerce' ),
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
