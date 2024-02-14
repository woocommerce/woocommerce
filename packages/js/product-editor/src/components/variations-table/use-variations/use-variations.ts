/**
 * External dependencies
 */
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	PartialProductVariation,
	ProductAttribute,
	ProductVariation,
} from '@woocommerce/data';
import { dispatch, resolveSelect } from '@wordpress/data';
import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DEFAULT_VARIATION_PER_PAGE_OPTION } from '../../../constants';
import {
	AttributeFilters,
	GetVariationsRequest,
	UseVariationsProps,
} from './types';
import { useProductVariationsHelper } from '../../../hooks/use-product-variations-helper';

export function useVariations( { productId }: UseVariationsProps ) {
	// Variation pagination

	const [ variations, setVariations ] = useState< ProductVariation[] >( [] );
	const [ totalCount, setTotalCount ] = useState< number >( 0 );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ getVariationsError, setGetVariationsError ] = useState< unknown >();
	const [ filters, setFilters ] = useState< AttributeFilters[] >( [] );
	const perPageRef = useRef( DEFAULT_VARIATION_PER_PAGE_OPTION );

	async function getCurrentVariationsPage(
		params: GetVariationsRequest,
		invalidateResolutionBeforeRequest = false
	) {
		const requestParams: GetVariationsRequest = {
			page: 1,
			per_page: perPageRef.current,
			order: 'asc',
			orderby: 'menu_order',
			attributes: [],
			...params,
		};

		try {
			const { invalidateResolution } = dispatch(
				EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
			);

			if ( invalidateResolutionBeforeRequest ) {
				await invalidateResolution( 'getProductVariations', [
					requestParams,
				] );
				await invalidateResolution( 'getProductVariationsTotalCount', [
					requestParams,
				] );
			}

			const { getProductVariations, getProductVariationsTotalCount } =
				resolveSelect( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

			setIsLoading( true );
			setGetVariationsError( undefined );

			const data = await getProductVariations< ProductVariation[] >(
				requestParams
			);

			const total = await getProductVariationsTotalCount< number >(
				requestParams
			);

			setVariations( data );
			setTotalCount( total );
			setIsLoading( false );
		} catch ( error ) {
			setGetVariationsError( error );
			setIsLoading( false );
		}
	}

	function onPageChange( page: number ) {
		getCurrentVariationsPage( {
			product_id: productId,
			attributes: filters,
			page,
		} );
	}

	function onPerPageChange( perPage: number ) {
		perPageRef.current = perPage;

		getCurrentVariationsPage( {
			product_id: productId,
			attributes: filters,
		} );
	}

	// Variation selection

	const [ selectedCount, setSelectedCount ] = useState( 0 );
	const [ isSelectingAll, setIsSelectingAll ] = useState( false );

	const selectedVariationsRef = useRef< Record< number, ProductVariation > >(
		{}
	);

	const selected = useMemo(
		function getSelected() {
			return selectedCount > 0
				? Object.values( selectedVariationsRef.current )
				: [];
		},
		[ selectedCount ]
	);

	const isSelected = useCallback(
		function isSelected( variation: ProductVariation ) {
			return (
				selectedCount > 0 &&
				variation.id in selectedVariationsRef.current
			);
		},
		[ selectedCount ]
	);

	const areAllSelected = useMemo(
		() => selectedCount > 0 && variations.every( isSelected ),
		[ variations, selectedCount, isSelected ]
	);

	const areSomeSelected = useMemo(
		() => selectedCount > 0 && variations.some( isSelected ),
		[ variations, selectedCount, isSelected ]
	);

	function onSelect( variation: ProductVariation ) {
		return function handleChange( checked: boolean ) {
			if ( checked ) {
				selectedVariationsRef.current[ variation.id ] = variation;
				setSelectedCount( ( current ) => current + 1 );
			} else {
				delete selectedVariationsRef.current[ variation.id ];
				setSelectedCount( ( current ) => current - 1 );
			}
		};
	}

	function onSelectPage( checked: boolean ) {
		if ( checked ) {
			variations.forEach( ( variation ) => {
				selectedVariationsRef.current[ variation.id ] = variation;
			} );
		} else {
			variations.forEach( ( variation ) => {
				delete selectedVariationsRef.current[ variation.id ];
			} );
		}
		setSelectedCount( Object.keys( selectedVariationsRef.current ).length );
	}

	async function onSelectAll() {
		setIsSelectingAll( true );

		const { getProductVariations } = resolveSelect(
			EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
		);

		let currentPage = 1;
		let fetchedCount = 0;

		while ( fetchedCount < totalCount ) {
			const chunk = await getProductVariations< ProductVariation[] >( {
				product_id: productId,
				page: currentPage++,
				per_page: 50,
				order: 'asc',
				orderby: 'menu_order',
				attributes: filters,
			} );

			fetchedCount += chunk.length;

			chunk.forEach( ( variation ) => {
				selectedVariationsRef.current[ variation.id ] = variation;
			} );
		}

		setSelectedCount( fetchedCount );

		setIsSelectingAll( false );

		return fetchedCount;
	}

	function onClearSelection() {
		selectedVariationsRef.current = {};
		setSelectedCount( 0 );
	}

	// Filters

	function onFilter( attribute: ProductAttribute ) {
		return function handleFilter( options: string[] ) {
			let isPresent = false;

			const newFilters = filters.reduce< AttributeFilters[] >(
				( prev, item ) => {
					if ( item.attribute === attribute.slug ) {
						isPresent = true;
						if ( options.length === 0 ) {
							return prev;
						}
						return [ ...prev, { ...item, terms: options } ];
					}
					return [ ...prev, item ];
				},
				[]
			);

			if ( ! isPresent ) {
				newFilters.push( {
					attribute: attribute.slug,
					terms: options,
				} );
			}

			onClearSelection();

			getCurrentVariationsPage( {
				product_id: productId,
				attributes: newFilters,
			} );

			setFilters( newFilters );
		};
	}

	function getFilters( attribute: ProductAttribute ) {
		return (
			filters.find( ( filter ) => filter.attribute === attribute.slug )
				?.terms ?? []
		);
	}

	function hasFilters() {
		return Boolean( filters.length );
	}

	async function clearFilters() {
		setFilters( [] );

		return getCurrentVariationsPage( {
			product_id: productId,
		} );
	}

	// Updating

	const [ isUpdating, setIsUpdating ] = useState< Record< number, boolean > >(
		{}
	);

	async function onUpdate( {
		id: variationId,
		...variation
	}: PartialProductVariation ) {
		if ( isUpdating[ variationId ] ) return;

		const { updateProductVariation } = dispatch(
			EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
		);

		return updateProductVariation< Promise< ProductVariation > >(
			{ product_id: productId, id: variationId },
			variation
		).then( async ( response: ProductVariation ) => {
			// @ts-expect-error There are no types for this.
			await dispatch( 'core' ).invalidateResolution( 'getEntityRecord', [
				'postType',
				'product_variation',
				variationId,
			] );

			await getCurrentVariationsPage( {
				product_id: productId,
				attributes: filters,
			} );

			return response;
		} );
	}

	async function onDelete( variationId: number ) {
		if ( isUpdating[ variationId ] ) return;

		const { deleteProductVariation, invalidateResolutionForStore } =
			dispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

		return deleteProductVariation< Promise< ProductVariation > >( {
			product_id: productId,
			id: variationId,
		} ).then( async ( response: ProductVariation ) => {
			onSelect( response )( false );

			// @ts-expect-error There are no types for this.
			await dispatch( 'core' ).invalidateResolution( 'getEntityRecord', [
				'postType',
				'product',
				productId,
			] );

			// @ts-expect-error There are no types for this.
			await dispatch( 'core' ).invalidateResolution( 'getEntityRecord', [
				'postType',
				'product_variation',
				variationId,
			] );

			await invalidateResolutionForStore();

			await getCurrentVariationsPage( {
				product_id: productId,
				attributes: filters,
			} );

			return response;
		} );
	}

	async function onBatchUpdate( values: PartialProductVariation[] ) {
		// @ts-expect-error There are no types for this.
		const { invalidateResolution: coreInvalidateResolution } =
			dispatch( 'core' );

		const { batchUpdateProductVariations, invalidateResolutionForStore } =
			dispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

		selectedVariationsRef.current = {};
		setSelectedCount( 0 );

		let currentPage = 1;
		const offset = 50;

		const result: ProductVariation[] = [];

		while ( ( currentPage - 1 ) * offset < values.length ) {
			const fromIndex = ( currentPage - 1 ) * offset;
			const toIndex = fromIndex + offset;
			const subset = values.slice( fromIndex, toIndex );

			setIsUpdating( ( current ) =>
				subset.reduce(
					( prev, variation ) => ( {
						...prev,
						[ variation.id ]: true,
					} ),
					fromIndex === 0 ? {} : current
				)
			);

			const response = await batchUpdateProductVariations< {
				update: ProductVariation[];
			} >(
				{ product_id: productId },
				{
					update: subset,
				}
			);

			currentPage++;

			const updatedVariations = response?.update ?? [];
			result.push( ...updatedVariations );

			for ( const variation of updatedVariations ) {
				await coreInvalidateResolution( 'getEntityRecord', [
					'postType',
					'product_variation',
					variation.id,
				] );

				selectedVariationsRef.current[ variation.id ] = variation;
				setSelectedCount( ( current ) => current + 1 );
			}
		}

		setIsUpdating( {} );

		await invalidateResolutionForStore();
		await getCurrentVariationsPage( {
			product_id: productId,
			attributes: filters,
		} );

		return { update: result };
	}

	async function onBatchDelete( values: PartialProductVariation[] ) {
		// @ts-expect-error There are no types for this.
		const { invalidateResolution: coreInvalidateResolution } =
			dispatch( 'core' );

		const { batchUpdateProductVariations, invalidateResolutionForStore } =
			dispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

		selectedVariationsRef.current = {};
		setSelectedCount( 0 );

		let currentPage = 1;
		const offset = 50;

		const result: ProductVariation[] = [];

		while ( ( currentPage - 1 ) * offset < values.length ) {
			const fromIndex = ( currentPage - 1 ) * offset;
			const toIndex = fromIndex + offset;
			const subset = values.slice( fromIndex, toIndex );

			setIsUpdating( ( current ) =>
				subset.reduce(
					( prev, variation ) => ( {
						...prev,
						[ variation.id ]: true,
					} ),
					fromIndex === 0 ? {} : current
				)
			);

			const response = await batchUpdateProductVariations< {
				delete: ProductVariation[];
			} >(
				{ product_id: productId },
				{
					delete: subset.map( ( { id } ) => id ),
				}
			);

			currentPage++;

			const deletedVariations = response?.delete ?? [];
			result.push( ...( response?.delete ?? [] ) );

			for ( const variation of deletedVariations ) {
				await coreInvalidateResolution( 'getEntityRecord', [
					'postType',
					'product_variation',
					variation.id,
				] );

				delete selectedVariationsRef.current[ variation.id ];
				setSelectedCount( ( current ) => current - 1 );
			}
		}

		setIsUpdating( {} );

		await coreInvalidateResolution( 'getEntityRecord', [
			'postType',
			'product',
			productId,
		] );
		await invalidateResolutionForStore();
		await getCurrentVariationsPage( {
			product_id: productId,
			attributes: filters,
		} );

		return { delete: result };
	}

	// Generation

	const {
		isGenerating,
		generateProductVariations: onGenerate,
		generateError,
	} = useProductVariationsHelper();

	const wasGenerating = useRef( false );

	useEffect( () => {
		if ( isGenerating ) {
			setFilters( [] );
			onClearSelection();
		}

		const didMount =
			wasGenerating.current === false && isGenerating === false;
		const didGenerate =
			wasGenerating.current === true && isGenerating === false;

		if ( didMount || didGenerate ) {
			getCurrentVariationsPage(
				{
					product_id: productId,
				},
				true
			);
		}

		wasGenerating.current = Boolean( isGenerating );
	}, [ productId, isGenerating ] );

	return {
		isLoading,
		variations,
		totalCount,
		onPageChange,
		onPerPageChange,
		onFilter,
		getFilters,
		hasFilters,
		clearFilters,

		selected,
		isSelectingAll,
		selectedCount,
		areAllSelected,
		areSomeSelected,
		isSelected,
		onSelect,
		onSelectPage,
		onSelectAll,
		onClearSelection,

		isUpdating,
		onUpdate,
		onDelete,
		onBatchUpdate,
		onBatchDelete,

		isGenerating,
		onGenerate,
		variationsError: generateError ?? getVariationsError,
	};
}
