/**
 * External dependencies
 */
import { DataViews, type Action, type View } from '@wordpress/dataviews';
import { Notice } from '@wordpress/components';
import { Button, Stack } from '@wordpress/ui';
import { __ } from '@wordpress/i18n';
import { useMemo, useState, useCallback, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { experimentalProductVariationsStore } from '@woocommerce/data';
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
const { useHistory, useLocation } = unlock( routerPrivateApis );

type VariationViewProps = {
	productId: number;
};

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
		() => buildVariationViewQuery( view, productId ),
		[ productId, view ]
	);

	const { records, totalItems, error, parentProduct } = useSelect(
		( select ) => {
			const store = select( experimentalProductVariationsStore );
			const coreSelect = select( coreStore );
			const product = coreSelect.getEntityRecord(
				'root',
				'product',
				productId
			) as ProductEntityRecord | false | undefined;
			const editedProduct = coreSelect.getEditedEntityRecord(
				'root',
				'product',
				productId
			) as ProductEntityRecord | false | undefined;
			let resolvedParentProduct: ProductEntityRecord | undefined;

			if ( editedProduct !== false && editedProduct !== undefined ) {
				resolvedParentProduct = editedProduct;
			} else if ( product !== false ) {
				resolvedParentProduct = product;
			}

			return {
				// @ts-expect-error missing types.
				records: store.getProductVariations( query ),
				// @ts-expect-error missing types.
				totalItems: store.getProductVariationsTotalCount( query ),
				// @ts-expect-error missing types.
				error: store.getProductVariationsError( query ),
				parentProduct: resolvedParentProduct,
			};
		},
		[ productId, query ]
	);

	const variations = useMemo< VariationEntityRecord[] >(
		() => records?.map( normalizeVariation ) || EMPTY_ARRAY,
		[ records ]
	);
	const productWithVariations = useMemo( () => {
		if ( ! parentProduct ) {
			return undefined;
		}

		return variations.reduce< ProductEntityRecord >(
			( product, variation ) =>
				getProductWithUpdatedVariation(
					product,
					variation as unknown as ProductEntityRecord
				),
			parentProduct
		);
	}, [ parentProduct, variations ] );
	const perPage = view.perPage || PAGE_SIZE;
	const paginationInfo = useMemo(
		() => ( {
			totalItems: totalItems ?? 0,
			totalPages: Math.ceil( ( totalItems ?? 0 ) / perPage ),
		} ),
		[ perPage, totalItems ]
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

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ __( 'Failed to load variations.', 'woocommerce' ) }
			</Notice>
		);
	}

	return (
		<div className="woocommerce-variation-view">
			<DataViews
				data={ variations }
				fields={ variationFields }
				view={ view }
				onClickItem={ handleEditVariation }
				onChangeView={ setView }
				isLoading={ ! records }
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
			{ showQuickEdit && productWithVariations && (
				<ProductEdit products={ [ productWithVariations ] } />
			) }
		</div>
	);
}
