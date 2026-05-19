/**
 * External dependencies
 */
import { DataViews, type Action, type View } from '@wordpress/dataviews';
import { Button, Stack } from '@wordpress/ui';
import { __ } from '@wordpress/i18n';
import { useMemo, useState, useCallback, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { privateApis as routerPrivateApis } from '@wordpress/router';

/**
 * Internal dependencies
 */
import { DEFAULT_LAYOUTS, DEFAULT_VIEW, PAGE_SIZE } from './constants';
import { buildVariationViewQuery } from './query';
import { normalizeVariation } from './normalization';
import { variationFields } from './fields';
import type { VariationEntityRecord } from './types';
import ProductEdit from '../product-edit';
import { getProductWithUpdatedVariation } from '../product-edit/utils';
import type { ProductEntityRecord } from '../fields/types';
import { unlock } from '../lock-unlock';
import {
	getProductListNavigationPath,
	getSelectionFromPostId,
} from '../product-list/utils';

const EMPTY_ARRAY: VariationEntityRecord[] = [];
const EMPTY_PRODUCT_RECORDS: ProductEntityRecord[] = [];
const { useHistory, useLocation } = unlock( routerPrivateApis );

type VariationViewProps = {
	productId: number;
};

function variationMatchesSearch(
	variation: VariationEntityRecord,
	search: string
) {
	const value = search.trim().toLowerCase();

	if ( ! value ) {
		return true;
	}

	const searchableValues = [
		variation.name,
		variation.sku,
		...( variation.attributes?.map( ( attribute ) => attribute.option ) ??
			[] ),
	];

	return searchableValues.some( ( searchableValue ) =>
		searchableValue?.toLowerCase().includes( value )
	);
}

function sortVariations( variations: VariationEntityRecord[], view: View ) {
	const { field, direction = 'asc' } = view.sort ?? {};

	if ( ! field ) {
		return variations;
	}

	const directionModifier = direction === 'desc' ? -1 : 1;

	return [ ...variations ].sort( ( first, second ) => {
		if ( field === 'name' ) {
			return first.name.localeCompare( second.name ) * directionModifier;
		}

		return (
			( ( first.menu_order ?? 0 ) - ( second.menu_order ?? 0 ) ) *
			directionModifier
		);
	} );
}

export function VariationView( { productId }: VariationViewProps ) {
	const { navigate } = useHistory();
	const location = useLocation();
	const currentQuery = useMemo(
		() =>
			( location.query || {} ) as {
				postId?: string;
				quickEdit?: string;
			},
		[ location.query ]
	);
	const { postId } = currentQuery;
	const [ view, setView ] = useState< View >( DEFAULT_VIEW );
	const [ selection, setSelection ] = useState( () =>
		getSelectionFromPostId( postId )
	);
	const showQuickEdit = currentQuery.quickEdit === 'true';

	const query = useMemo(
		() => buildVariationViewQuery( productId ),
		[ productId ]
	);

	const { records, parentProduct, hasResolved } = useSelect(
		( select ) => {
			const coreSelect = select( coreStore );
			const resolutionArgs = [ 'root', 'product', query ];
			const products = coreSelect.getEntityRecords< ProductEntityRecord >(
				'root',
				'product',
				query
			);

			return {
				hasResolved: coreSelect.hasFinishedResolution(
					'getEntityRecords',
					resolutionArgs
				),
				parentProduct: products?.[ 0 ],
				records: products
					? products[ 0 ]?._embedded?.variations ??
					  EMPTY_PRODUCT_RECORDS
					: undefined,
			};
		},
		[ productId, query ]
	);

	const allVariations = useMemo< VariationEntityRecord[] >(
		() => records?.map( normalizeVariation ) || EMPTY_ARRAY,
		[ records ]
	);
	const filteredVariations = useMemo(
		() =>
			sortVariations(
				allVariations.filter( ( variation ) =>
					variationMatchesSearch( variation, view.search ?? '' )
				),
				view
			),
		[ allVariations, view ]
	);
	const productWithVariations = useMemo( () => {
		if ( ! parentProduct ) {
			return undefined;
		}

		return allVariations.reduce< ProductEntityRecord >(
			( product, variation ) =>
				getProductWithUpdatedVariation(
					product,
					variation as unknown as ProductEntityRecord
				),
			parentProduct
		);
	}, [ allVariations, parentProduct ] );
	const perPage = view.perPage || PAGE_SIZE;
	const variations = useMemo< VariationEntityRecord[] >( () => {
		const page = view.page ?? 1;
		const offset = ( page - 1 ) * perPage;

		return filteredVariations.slice( offset, offset + perPage );
	}, [ filteredVariations, perPage, view.page ] );

	const paginationInfo = useMemo(
		() => ( {
			totalItems: filteredVariations.length,
			totalPages: Math.ceil( filteredVariations.length / perPage ),
		} ),
		[ filteredVariations.length, perPage ]
	);

	useEffect( () => {
		setSelection( getSelectionFromPostId( postId ) );
	}, [ postId ] );

	const onChangeSelection = useCallback(
		( items: string[] ) => {
			setSelection( items );

			const nextQuery = { ...currentQuery };

			if ( items.length > 0 ) {
				nextQuery.postId = items.join( ',' );
			} else {
				delete nextQuery.postId;
			}

			navigate(
				getProductListNavigationPath( location.path, nextQuery )
			);
		},
		[ currentQuery, location.path, navigate ]
	);

	const handleEditSelectedVariations = useCallback(
		( selectedIds: string[] ) => {
			if ( selectedIds.length === 0 ) {
				return;
			}

			navigate(
				getProductListNavigationPath( location.path, {
					...currentQuery,
					postId: selectedIds.join( ',' ),
					quickEdit: 'true',
				} )
			);
		},
		[ currentQuery, location.path, navigate ]
	);

	const handleEditVariation = useCallback(
		( variation: VariationEntityRecord ) => {
			handleEditSelectedVariations( [ String( variation.id ) ] );
		},
		[ handleEditSelectedVariations ]
	);

	const actions: Action< VariationEntityRecord >[] = useMemo(
		() => [
			{
				id: 'edit',
				label: __( 'Edit', 'woocommerce' ),
				isPrimary: true,
				supportsBulk: true,
				callback: ( items ) =>
					handleEditSelectedVariations(
						items.map( ( item ) => String( item.id ) )
					),
			},
			{
				id: 'delete-variation',
				label: __( 'Delete variation', 'woocommerce' ),
				supportsBulk: true,
				callback: () => {},
			},
		],
		[ handleEditSelectedVariations ]
	);

	return (
		<div className="woocommerce-variation-view">
			<DataViews
				data={ variations }
				fields={ variationFields }
				view={ view }
				onClickItem={ handleEditVariation }
				onChangeView={ setView }
				isLoading={ ! hasResolved }
				paginationInfo={ paginationInfo }
				getItemId={ ( item: VariationEntityRecord ) =>
					String( item.id )
				}
				defaultLayouts={ DEFAULT_LAYOUTS }
				actions={ actions }
				selection={ selection }
				onChangeSelection={ onChangeSelection }
			>
				<Stack
					direction="row"
					align="center"
					justify="space-between"
					className="woocommerce-variation-view__toolbar"
				>
					<DataViews.Search
						label={ __( 'Search variations', 'woocommerce' ) }
					/>
					<Stack direction="row" gap="xs">
						<DataViews.ViewConfig />
						<Button
							disabled={ selection.length === 0 }
							onClick={ () =>
								handleEditSelectedVariations( selection )
							}
						>
							{ __( 'Edit options', 'woocommerce' ) }
						</Button>
					</Stack>
				</Stack>
				<DataViews.Layout />
				<DataViews.Footer />
			</DataViews>
			{ productWithVariations && (
				<ProductEdit
					products={ [ productWithVariations ] }
					isOpen={ showQuickEdit }
				/>
			) }
		</div>
	);
}
