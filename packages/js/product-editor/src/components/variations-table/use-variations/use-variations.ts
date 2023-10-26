/**
 * External dependencies
 */
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
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
import { UseVariationsProps } from './types';

export function useVariations( { productId }: UseVariationsProps ) {
	// Variation pagination

	const [ variations, setVariations ] = useState< ProductVariation[] >( [] );
	const [ totalCount, setTotalCount ] = useState< number >( 0 );
	const [ isLoading, setIsLoading ] = useState( false );
	const pageRef = useRef( 1 );
	const perPageRef = useRef( DEFAULT_VARIATION_PER_PAGE_OPTION );

	async function getCurrentVariationsPage( productId: number ) {
		const { getProductVariations, getProductVariationsTotalCount } =
			resolveSelect( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

		const requestParams = {
			product_id: productId,
			page: pageRef.current,
			per_page: perPageRef.current,
			order: 'asc',
			orderby: 'menu_order',
		};

		const data = await getProductVariations< ProductVariation[] >(
			requestParams
		);

		const totalCount = await getProductVariationsTotalCount< number >(
			requestParams
		);

		setVariations( data );
		setTotalCount( totalCount );
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
		getCurrentVariationsPage( productId ).finally( () =>
			setIsLoading( false )
		);
	}

	function onPerPageChange( perPage: number ) {
		pageRef.current = 1;
		perPageRef.current = perPage;

		setIsLoading( true );
		getCurrentVariationsPage( productId ).finally( () =>
			setIsLoading( false )
		);
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
		[ variations, selectedCount ]
	);

	const areSomeSelected = useMemo(
		() => selectedCount > 0 && variations.some( isSelected ),
		[ variations, selectedCount ]
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
	}

	function onClearSelection() {
		selectedVariationsRef.current = {};
		setSelectedCount( 0 );
	}

	// Updating

	const [ isUpdating, setIsUpdating ] = useState< Record< number, boolean > >(
		{}
	);

	async function onBatchUpdate( values: Partial< ProductVariation >[] ) {
		const { batchUpdateProductVariations, invalidateResolutionForStore } =
			dispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

		let currentPage = 1;
		let offset = 50;

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

			await batchUpdateProductVariations< {
				update: Partial< ProductVariation >[];
			} >(
				{ product_id: productId },
				{
					update: subset,
				}
			);

			currentPage++;
		}

		await invalidateResolutionForStore();
		await getCurrentVariationsPage( productId );

		setIsUpdating( {} );
	}

	async function onBatchDelete( values: Pick< ProductVariation, 'id' >[] ) {
		const { batchUpdateProductVariations, invalidateResolutionForStore } =
			dispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );

		let currentPage = 1;
		let offset = 50;

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

			await batchUpdateProductVariations< {
				delete: Partial< ProductVariation >[];
			} >(
				{ product_id: productId },
				{
					delete: subset,
				}
			);

			currentPage++;
		}

		await invalidateResolutionForStore();
		await getCurrentVariationsPage( productId );

		setIsUpdating( {} );
	}

	return {
		isLoading,
		variations,
		totalCount,
		onPageChange,
		onPerPageChange,

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
		onBatchUpdate,
		onBatchDelete,
	};
}
