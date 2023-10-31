/**
 * External dependencies
 */
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
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
import { AttributeFilters, UseVariationsProps } from './types';

export function useVariations( { productId }: UseVariationsProps ) {
	// Variation pagination

	const [ variations, setVariations ] = useState< ProductVariation[] >( [] );
	const [ totalCount, setTotalCount ] = useState< number >( 0 );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ filters, setFilters ] = useState< AttributeFilters[] >( [] );
	const pageRef = useRef( 1 );
	const perPageRef = useRef( DEFAULT_VARIATION_PER_PAGE_OPTION );

	async function getCurrentVariationsPage(
		id: number,
		attributes: AttributeFilters[] = []
	) {
		const { getProductVariations, getProductVariationsTotalCount } =
			resolveSelect( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

		const requestParams = {
			product_id: id,
			page: pageRef.current,
			per_page: perPageRef.current,
			order: 'asc',
			orderby: 'menu_order',
			attributes,
		};

		const data = await getProductVariations< ProductVariation[] >(
			requestParams
		);

		const total = await getProductVariationsTotalCount< number >(
			requestParams
		);

		setVariations( data );
		setTotalCount( total );
	}

	useEffect( () => {
		setIsLoading( true );
		getCurrentVariationsPage( productId ).finally( () =>
			setIsLoading( false )
		);
	}, [ productId ] );

	function onPageChange( page: number ) {
		pageRef.current = page;

		setIsLoading( true );
		getCurrentVariationsPage( productId, filters ).finally( () =>
			setIsLoading( false )
		);
	}

	function onPerPageChange( perPage: number ) {
		pageRef.current = 1;
		perPageRef.current = perPage;

		setIsLoading( true );
		getCurrentVariationsPage( productId, filters ).finally( () =>
			setIsLoading( false )
		);
	}

	function onFilter( attribute: ProductAttribute ) {
		return function handleFilter( options: string[] ) {
			let isPresent = false;

			const newFilter = filters.reduce< AttributeFilters[] >(
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
				newFilter.push( {
					attribute: attribute.slug,
					terms: options,
				} );
			}

			pageRef.current = 1;

			setIsLoading( true );
			getCurrentVariationsPage( productId, newFilter ).finally( () =>
				setIsLoading( false )
			);

			setFilters( newFilter );
		};
	}

	function getFilters( attribute: ProductAttribute ) {
		return (
			filters.find( ( filter ) => filter.attribute === attribute.slug )
				?.terms ?? []
		);
	}

	function hasFilters() {
		return filters.length;
	}

	function clearFilters() {
		setFilters( [] );
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

	// Updating

	const [ isUpdating, setIsUpdating ] = useState< Record< number, boolean > >(
		{}
	);

	async function onUpdate( {
		id: variationId,
		...variation
	}: Partial< ProductVariation > ) {
		if ( isUpdating[ variationId ] ) return;

		const { invalidateResolution: coreInvalidateResolution } =
			dispatch( 'core' );

		const { updateProductVariation } = dispatch(
			EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
		);

		return updateProductVariation< Promise< ProductVariation > >(
			{ product_id: productId, id: variationId },
			variation
		).then( async ( response: ProductVariation ) => {
			await coreInvalidateResolution( 'getEntityRecord', [
				'postType',
				'product_variation',
				variationId,
			] );

			return response;
		} );
	}

	async function onDelete( variationId: number ) {
		if ( isUpdating[ variationId ] ) return;

		const { invalidateResolution: coreInvalidateResolution } =
			dispatch( 'core' );

		const { deleteProductVariation, invalidateResolutionForStore } =
			dispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

		return deleteProductVariation< Promise< ProductVariation > >( {
			product_id: productId,
			id: variationId,
		} ).then( async ( response: ProductVariation ) => {
			await coreInvalidateResolution( 'getEntityRecord', [
				'postType',
				'product',
				productId,
			] );

			await coreInvalidateResolution( 'getEntityRecord', [
				'postType',
				'product_variation',
				variationId,
			] );

			await invalidateResolutionForStore();

			return response;
		} );
	}

	async function onBatchUpdate( values: Partial< ProductVariation >[] ) {
		const { invalidateResolution: coreInvalidateResolution } =
			dispatch( 'core' );

		const { batchUpdateProductVariations, invalidateResolutionForStore } =
			dispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

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

			result.push( ...( response?.update ?? [] ) );

			for ( const variation of subset ) {
				await coreInvalidateResolution( 'getEntityRecord', [
					'postType',
					'product_variation',
					variation.id,
				] );
			}
		}

		await invalidateResolutionForStore();
		await getCurrentVariationsPage( productId, filters );

		setIsUpdating( {} );

		return { update: result };
	}

	async function onBatchDelete( values: Pick< ProductVariation, 'id' >[] ) {
		const { invalidateResolution: coreInvalidateResolution } =
			dispatch( 'core' );

		const { batchUpdateProductVariations, invalidateResolutionForStore } =
			dispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

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
					delete: subset,
				}
			);

			currentPage++;

			result.push( ...( response?.delete ?? [] ) );

			for ( const variation of subset ) {
				await coreInvalidateResolution( 'getEntityRecord', [
					'postType',
					'product_variation',
					variation.id,
				] );
			}
		}

		await coreInvalidateResolution( 'getEntityRecord', [
			'postType',
			'product',
			productId,
		] );
		await invalidateResolutionForStore();
		await getCurrentVariationsPage( productId, filters );

		setIsUpdating( {} );

		return { delete: result };
	}

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
	};
}
