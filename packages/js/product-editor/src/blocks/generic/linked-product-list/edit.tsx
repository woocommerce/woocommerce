/**
 * External dependencies
 */
import {
	createElement,
	useCallback,
	useEffect,
	useReducer,
	useRef,
	useState,
} from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { resolveSelect } from '@wordpress/data';
import { PRODUCTS_STORE_NAME, Product } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { reusableBlock } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';
import { useDebounce } from '@wordpress/compose';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { ProductList, Skeleton } from '../../../components/product-list';
import { ProductSelect } from '../../../components/product-select';
import { AdviceCard } from '../../../components/advice-card';
import { TRACKS_SOURCE } from '../../../constants';
import { ShoppingBags } from '../../../images/shopping-bags';
import { CashRegister } from '../../../images/cash-register';
import { ProductEditorBlockEditProps } from '../../../types';
import {
	getLoadLinkedProductsDispatcher,
	getRemoveLinkedProductDispatcher,
	getSelectSearchedProductDispatcher,
	reducer,
} from './reducer';
import {
	LinkedProductListBlockAttributes,
	LinkedProductListBlockEmptyState,
} from './types';
import { getSuggestedProductsFor } from '../../../utils/get-related-products';
import { SectionActions } from '../../../components/block-slot-fill';

export function EmptyStateImage( {
	image,
	tip: description,
}: LinkedProductListBlockEmptyState ) {
	switch ( image ) {
		case 'CashRegister':
			return <CashRegister />;
		case 'ShoppingBags':
			return <ShoppingBags />;
		default:
			if ( /^https?:\/\//.test( image ) ) {
				return (
					<img
						src={ image }
						alt={ description }
						height={ 88 }
						width={ 88 }
					/>
				);
			}
			return null;
	}
}

async function getProductsBySearchValue(
	searchValue = '',
	excludedIds: number[] = []
): Promise< Product[] > {
	return resolveSelect( PRODUCTS_STORE_NAME ).getProducts< Product[] >( {
		search: searchValue,
		orderby: 'title',
		order: 'asc',
		per_page: 5,
		exclude: excludedIds,
	} );
}

export function LinkedProductListBlockEdit( {
	attributes,
	context: { postType, isInSelectedTab },
}: ProductEditorBlockEditProps< LinkedProductListBlockAttributes > ) {
	const { property, emptyState } = attributes;
	const loadInitialSearchResults = useRef( false );
	const [ , setSearchValue ] = useState( '' );
	const [ searchedProducts, setSearchedProducts ] = useState< Product[] >(
		[]
	);
	const [ isSearching, setIsSearching ] = useState( false );
	const blockProps = useWooBlockProps( attributes );
	const [ state, dispatch ] = useReducer( reducer, {
		linkedProducts: [],
	} );

	const productId = useEntityId( 'postType', postType );

	const loadLinkedProductsDispatcher =
		getLoadLinkedProductsDispatcher( dispatch );
	const selectSearchedProductDispatcher =
		getSelectSearchedProductDispatcher( dispatch );
	const removeLinkedProductDispatcher =
		getRemoveLinkedProductDispatcher( dispatch );
	const [ linkedProductIds, setLinkedProductIds ] = useProductEntityProp<
		number[]
	>( property, { postType } );

	useEffect( () => {
		if (
			! state.selectedProduct &&
			linkedProductIds &&
			linkedProductIds.length > 0
		) {
			loadLinkedProductsDispatcher( linkedProductIds );
		}
	}, [ linkedProductIds, state.selectedProduct ] );

	function searchProducts( search = '', excludedIds: number[] = [] ) {
		setSearchValue( search );
		setIsSearching( true );
		return getProductsBySearchValue( search, excludedIds )
			.then( ( products ) => {
				setSearchedProducts( products );
			} )
			.finally( () => {
				setIsSearching( false );
			} );
	}

	const debouncedFilter = useDebounce( function filter( search = '' ) {
		searchProducts( search, [ ...( linkedProductIds || [] ), productId ] );
	}, 300 );

	useEffect( () => {
		// Only filter when the tab is selected and initial search results haven't been loaded yet.
		if ( ! isInSelectedTab || loadInitialSearchResults.current ) {
			return;
		}

		loadInitialSearchResults.current = true;
		searchProducts( '', [ ...( linkedProductIds || [] ), productId ] );
	}, [
		isInSelectedTab,
		loadInitialSearchResults,
		linkedProductIds,
		productId,
	] );

	const handleSelect = useCallback(
		( product: Product ) => {
			const isAlreadySelected = ( linkedProductIds || [] ).includes(
				product.id
			);
			if ( isAlreadySelected ) {
				return;
			}
			const newLinkedProductIds = selectSearchedProductDispatcher(
				product,
				state.linkedProducts
			);

			setLinkedProductIds( newLinkedProductIds );
			searchProducts( '', [
				...( newLinkedProductIds || [] ),
				productId,
			] );

			recordEvent( 'linked_products_product_add', {
				source: TRACKS_SOURCE,
				field: property,
				product_id: productId,
				linked_product_id: product.id,
			} );
		},
		[ linkedProductIds, state.linkedProducts ]
	);

	function handleProductListRemove( product: Product ) {
		const newLinkedProductIds = removeLinkedProductDispatcher(
			product,
			state.linkedProducts
		);

		setLinkedProductIds( newLinkedProductIds );
		searchProducts( '', [ ...( newLinkedProductIds || [] ), productId ] );

		recordEvent( 'linked_products_product_remove', {
			source: TRACKS_SOURCE,
			field: property,
			product_id: productId,
			linked_product_id: product.id,
		} );
	}

	function handleProductListEdit( product: Product ) {
		recordEvent( 'linked_products_product_select', {
			source: TRACKS_SOURCE,
			field: property,
			product_id: productId,
			linked_product_id: product.id,
		} );
	}

	function handleProductListPreview( product: Product ) {
		recordEvent( 'linked_products_product_preview_click', {
			source: TRACKS_SOURCE,
			field: property,
			product_id: productId,
			linked_product_id: product.id,
		} );
	}

	const [ isChoosingProducts, setIsChoosingProducts ] = useState( false );

	async function chooseProductsForMe() {
		recordEvent( 'linked_products_choose_related_click', {
			source: TRACKS_SOURCE,
			field: property,
		} );

		dispatch( {
			type: 'LOADING_LINKED_PRODUCTS',
			payload: {
				isLoading: true,
			},
		} );

		setIsChoosingProducts( true );

		const linkedProducts = ( await getSuggestedProductsFor( {
			postId: productId,
			forceRequest: true,
		} ) ) as Product[];

		dispatch( {
			type: 'LOADING_LINKED_PRODUCTS',
			payload: {
				isLoading: false,
			},
		} );

		setIsChoosingProducts( false );

		if ( ! linkedProducts ) {
			return;
		}

		const newLinkedProducts = selectSearchedProductDispatcher(
			linkedProducts,
			[]
		);

		setLinkedProductIds( newLinkedProducts );
	}

	function handleAdviceCardDismiss() {
		recordEvent( 'linked_products_placeholder_dismiss', {
			source: TRACKS_SOURCE,
			field: property,
		} );
	}

	return (
		<div { ...blockProps }>
			<SectionActions>
				<Button
					variant="tertiary"
					icon={ reusableBlock }
					onClick={ chooseProductsForMe }
					isBusy={ isChoosingProducts }
					disabled={ isChoosingProducts }
				>
					{ __( 'Choose products for me', 'woocommerce' ) }
				</Button>
			</SectionActions>

			<div className="wp-block-woocommerce-product-linked-list-field__form-group-content">
				<ProductSelect
					items={ searchedProducts }
					filter={ debouncedFilter }
					onSelect={ handleSelect }
					isLoading={ isSearching }
					selected={ null }
				/>
			</div>

			{ state.isLoading && <Skeleton /> }

			{ ! state.isLoading && state.linkedProducts.length === 0 && (
				<AdviceCard
					tip={ emptyState.tip }
					dismissPreferenceId={ `woocommerce-product-${ property }-advice-card-dismissed` }
					isDismissible={ emptyState.isDismissible }
					onDismiss={ handleAdviceCardDismiss }
				>
					<EmptyStateImage { ...emptyState } />
				</AdviceCard>
			) }

			{ ! state.isLoading && state.linkedProducts.length > 0 && (
				<ProductList
					products={ state.linkedProducts }
					onRemove={ handleProductListRemove }
					onEdit={ handleProductListEdit }
					onPreview={ handleProductListPreview }
				/>
			) }
		</div>
	);
}
