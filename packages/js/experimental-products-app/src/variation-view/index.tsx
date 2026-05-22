/**
 * External dependencies
 */
import {
	DataViews,
	filterSortAndPaginate,
	type Action,
	type Field,
	type View,
} from '@wordpress/dataviews';
import { Stack } from '@wordpress/ui';
import {
	Button,
	privateApis as componentsPrivateApis,
} from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useMemo, useState, useCallback, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { privateApis as routerPrivateApis } from '@wordpress/router';

/**
 * Internal dependencies
 */
import { DEFAULT_LAYOUTS, DEFAULT_VIEW } from './constants';
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
const { Menu } = unlock( componentsPrivateApis );

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

	// Build one filterable + hideable column per variation-typed parent
	// attribute (e.g. Theme, Color). Each variation's row supplies the option
	// for that attribute via getValue. Merged with the static variationFields
	// below.
	const attributeFields = useMemo< Field< VariationEntityRecord >[] >( () => {
		const parentAttributes = parentProduct?.attributes ?? [];
		return parentAttributes
			.filter( ( attr ) => attr.variation )
			.sort( ( a, b ) => a.position - b.position )
			.map( ( attr ) => {
				const fieldId = `attribute_${ ( attr.slug || attr.name ).replace( /[^a-zA-Z0-9_-]/g, '_' ) }`;
				const options = attr.options ?? [];
				return {
					id: fieldId,
					label: attr.name,
					header: <span>{ attr.name }</span>,
					enableSorting: true,
					enableHiding: true,
					enableGlobalSearch: true,
					filterBy: {
						operators: [ 'is', 'isAny', 'isNone', 'isNotAny' ],
					},
					elements: options.map( ( option ) => ( {
						value: option,
						label: option,
					} ) ),
					getValue: ( { item }: { item: VariationEntityRecord } ) => {
						const match = item.attributes?.find(
							( a ) =>
								a.slug === attr.slug || a.name === attr.name
						);
						return match?.option ?? '';
					},
					render: ( { item }: { item: VariationEntityRecord } ) => {
						const match = item.attributes?.find(
							( a ) =>
								a.slug === attr.slug || a.name === attr.name
						);
						return <span>{ match?.option ?? '—' }</span>;
					},
				} as Field< VariationEntityRecord >;
			} );
	}, [ parentProduct ] );

	const allFields = useMemo(
		() => [ ...attributeFields, ...variationFields ],
		[ attributeFields ]
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

	// Apply the View's search / filters / sort / pagination via DataViews'
	// own utility so the filters panel works out of the box. Search keeps
	// matching across name, sku, and the dynamic attribute fields (each
	// declares enableGlobalSearch).
	const { data: variations, paginationInfo } = useMemo(
		() => filterSortAndPaginate( allVariations, view, allFields ),
		[ allVariations, view, allFields ]
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
				fields={ allFields }
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
					<Stack
						direction="row"
						align="center"
						gap="xs"
						className="woocommerce-variation-view__toolbar-search"
					>
						<DataViews.Search
							label={ __( 'Search variations', 'woocommerce' ) }
						/>
						<DataViews.FiltersToggle />
					</Stack>
					<Stack
						direction="row"
						align="center"
						gap="xs"
						className="woocommerce-variation-view__toolbar-actions"
					>
						<DataViews.ViewConfig />
						<Menu placement="bottom-end">
							<Menu.TriggerButton
								render={
									<Button
										size="compact"
										icon={ moreVertical }
										label={ __(
											'Variation actions',
											'woocommerce'
										) }
									/>
								}
							/>
							<Menu.Popover>
								<Menu.Group>
									<Menu.Item
										disabled
										onClick={ () => undefined }
									>
										<Menu.ItemLabel>
											{ __(
												'Add variations manually',
												'woocommerce'
											) }
										</Menu.ItemLabel>
									</Menu.Item>
									<Menu.Item
										disabled
										onClick={ () => undefined }
									>
										<Menu.ItemLabel>
											{ __(
												'Generate missing variations',
												'woocommerce'
											) }
										</Menu.ItemLabel>
									</Menu.Item>
								</Menu.Group>
							</Menu.Popover>
						</Menu>
					</Stack>
				</Stack>
				<DataViews.FiltersToggled />
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
