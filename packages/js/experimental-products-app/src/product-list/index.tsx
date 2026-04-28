/**
 * External dependencies
 */
import { DataViews, View } from '@wordpress/dataviews';
import { useState, useMemo, useCallback, useEffect } from '@wordpress/element';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { store as coreStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import clsx from 'clsx';
import { Button, Stack, Tabs } from '@wordpress/ui';
import { privateApis as editorPrivateApis } from '@wordpress/editor';
import { Page } from '@wordpress/admin-ui';
import { addQueryArgs } from '@wordpress/url';
import { getAdminLink } from '@woocommerce/settings';
import type { ProductStatus } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { unlock } from '../lock-unlock';
import type { ProductEntityRecord } from '../fields/types';
import { productFields } from './fields';
import {
	DEFAULT_PRODUCT_TABLE_LAYOUT,
	DEFAULT_PRODUCT_TABLE_VIEW,
} from './layouts';
import { buildProductListQuery } from './query';
import { useProductActions } from '../dataviews-actions';

const { usePostActions } = unlock( editorPrivateApis );
const { useHistory, useLocation } = unlock( routerPrivateApis );

export type ProductListProps = {
	subTitle?: string;
	className?: string;
	hideTitleFromUI?: boolean;
	postType?: string;
};

const PAGE_SIZE = 20;
const EMPTY_ARRAY: ProductEntityRecord[] = [];
const DEFAULT_LAYOUTS = {
	table: DEFAULT_PRODUCT_TABLE_LAYOUT,
};
const DEFAULT_VIEW: View = {
	...DEFAULT_PRODUCT_TABLE_VIEW,
	page: 1,
};

const PRODUCT_LIST_TAB_VALUES = [
	'all',
	'publish',
	'draft',
	'pending',
	'trash',
] as const;

type StatusTab = ( typeof PRODUCT_LIST_TAB_VALUES )[ number ];

const PRODUCT_LIST_TABS: Array< {
	value: StatusTab;
	label: string;
} > = [
	{
		value: 'all',
		label: __( 'All', 'woocommerce' ),
	},
	{
		value: 'publish',
		label: __( 'Published', 'woocommerce' ),
	},
	{
		value: 'draft',
		label: __( 'Draft', 'woocommerce' ),
	},
	{
		value: 'trash',
		label: __( 'Trash', 'woocommerce' ),
	},
];

/**
 * This function abstracts working with default & custom views by
 * providing a [ state, setState ] tuple based on the URL parameters.
 *
 * Consumers use the provided tuple to work with state
 * and don't have to deal with the specifics of default & custom views.
 *
 * @param {string} postType Post type to retrieve default views for.
 * @return {Array} The [ state, setState ] tuple.
 */
function useView( postType: string ): [ View, ( view: View ) => void ] {
	const {
		params: { activeView = 'all', isCustom = 'false' },
	} = useLocation();
	const [ view, setView ] = useState< View >( DEFAULT_VIEW );

	// When activeView or isCustom URL parameters change, reset the view.
	useEffect( () => {
		setView( DEFAULT_VIEW );
	}, [ activeView, isCustom, postType ] );

	return [ view, setView ];
}

function getItemId( item: ProductEntityRecord ) {
	return item.id.toString();
}

function isProductListTabValue( value: string ): value is StatusTab {
	return PRODUCT_LIST_TAB_VALUES.includes( value as StatusTab );
}

function getProductListTab( value?: string ): StatusTab {
	if ( value && isProductListTabValue( value ) ) {
		return value;
	}

	return 'all';
}

function getStatusForProductListTab(
	tab: StatusTab
): ProductStatus | undefined {
	switch ( tab ) {
		case 'publish':
		case 'draft':
		case 'pending':
		case 'trash':
			return tab;
		default:
			return undefined;
	}
}

function getSelectionFromPostId( postId?: string ) {
	return postId?.split( ',' ).filter( Boolean ) ?? [];
}

export default function ProductList( { className }: ProductListProps ) {
	const history = useHistory();
	const location = useLocation();
	const {
		postId,
		postType = 'product',
		isCustom,
		activeView = 'all',
	} = location.params;
	const selectedTabFromLocation = getProductListTab( activeView );
	const [ selectedTab, setSelectedTab ] = useState( selectedTabFromLocation );
	const [ selection, setSelection ] = useState( () =>
		getSelectionFromPostId( postId )
	);
	const [ view, setView ] = useView( postType );

	useEffect( () => {
		setSelectedTab( selectedTabFromLocation );
	}, [ selectedTabFromLocation ] );

	useEffect( () => {
		setSelection( getSelectionFromPostId( postId ) );
	}, [ postId ] );

	const queryParams = useMemo( () => {
		const query = buildProductListQuery( view );
		const productStatus = getStatusForProductListTab( selectedTab );

		if ( productStatus ) {
			query.status = productStatus;
		}

		return query;
	}, [ selectedTab, view ] );

	const onChangeSelection = useCallback(
		( items: string[] ) => {
			setSelection( items );

			const nextParams = { ...location.params };

			if ( items.length > 0 ) {
				nextParams.postId = items.join( ',' );
			} else {
				delete nextParams.postId;
			}

			history.push( nextParams );
		},
		[ history, location.params ]
	);

	const onChangeTab = useCallback(
		( value: string | null ) => {
			if ( ! value ) {
				return;
			}

			const nextTab = getProductListTab( value );

			if ( nextTab === selectedTab ) {
				return;
			}

			setSelectedTab( nextTab );
			setSelection( [] );

			const nextParams = {
				...location.params,
				activeView: nextTab,
			};

			delete nextParams.postId;

			history.push( nextParams );
		},
		[ history, location.params, selectedTab ]
	);

	const {
		records,
		totalItems: totalCount,
		isResolving: isLoading,
		hasResolved,
	} = useSelect(
		( select ) => {
			const { getEntityRecords, isResolving, hasFinishedResolution } =
				select( coreStore );
			return {
				records: getEntityRecords< ProductEntityRecord >(
					'root',
					'product',
					queryParams
				),
				totalItems: getEntityRecords( 'root', 'product', {
					...queryParams,
					per_page: -1,
				} )?.length,
				isResolving: isResolving( 'getEntityRecords', [
					'root',
					'product',
					queryParams,
				] ),
				hasResolved: hasFinishedResolution( 'getEntityRecords', [
					'root',
					'product',
					queryParams,
				] ),
			};
		},
		[ queryParams ]
	);

	const paginationInfo = useMemo(
		() => ( {
			totalItems: totalCount ?? 0,
			totalPages: Math.ceil(
				( totalCount ?? 0 ) / ( view.perPage || PAGE_SIZE )
			),
		} ),
		[ totalCount, view.perPage ]
	);

	const { canCreateRecord } = useSelect(
		( select ) => {
			const { canUser } = select( coreStore );
			return {
				canCreateRecord: canUser( 'create', {
					kind: 'postType',
					name: postType,
				} ),
			};
		},
		[ postType ]
	);

	const postTypeActions = usePostActions( {
		postType,
		context: 'list',
	} );
	const productActions = useProductActions();
	const actions = useMemo(
		() => [
			...productActions,
			...postTypeActions.filter(
				( { id }: { id: string } ) =>
					! [
						'edit-post',
						'view-post',
						'duplicate-post',
						'delete-post',
						'move-to-trash',
						'permanently-delete-post',
					].includes( id )
			),
		],
		[ postTypeActions, productActions ]
	);

	const classes = clsx( 'woocommerce-product-list', className );

	const pageActions = (
		<Stack gap="lg">
			<Button
				size="compact"
				variant="outline"
				onClick={ () =>
					( window.location.href = getAdminLink(
						addQueryArgs( 'edit.php', {
							post_type: 'product',
							page: 'product_exporter',
						} )
					) )
				}
			>
				{ __( 'Export', 'woocommerce' ) }
			</Button>
			<Button
				size="compact"
				onClick={ () =>
					( window.location.href = getAdminLink(
						addQueryArgs( 'edit.php', {
							post_type: 'product',
							page: 'product_importer',
						} )
					) )
				}
				variant="outline"
			>
				{ __( 'Import', 'woocommerce' ) }
			</Button>
			<Button
				size="compact"
				disabled={ canCreateRecord === false }
				onClick={ () =>
					( window.location.href = getAdminLink(
						addQueryArgs( 'post-new.php', {
							post_type: 'product',
						} )
					) )
				}
			>
				{ __( 'Add new product', 'woocommerce' ) }
			</Button>
		</Stack>
	);

	return (
		<Page
			className={ classes }
			ariaLabel={ __( 'Products', 'woocommerce' ) }
			subTitle={ __(
				'Add, edit, and manage the products you sell in your store',
				'woocommerce'
			) }
			title={ __( 'Products', 'woocommerce' ) }
			actions={ pageActions }
		>
			<DataViews
				key={ activeView + isCustom }
				paginationInfo={ paginationInfo }
				fields={ productFields }
				data={ records || EMPTY_ARRAY }
				isLoading={ isLoading && ! hasResolved }
				view={ view }
				actions={ actions }
				onChangeView={ setView }
				onChangeSelection={ onChangeSelection }
				getItemId={ getItemId }
				selection={ selection }
				defaultLayouts={ DEFAULT_LAYOUTS }
			>
				<Stack
					direction="row"
					align="center"
					justify="space-between"
					gap="sm"
					className="woocommerce-product-list__toolbar"
				>
					<Tabs.Root
						value={ selectedTab }
						onValueChange={ onChangeTab }
					>
						<Tabs.List
							variant="minimal"
							aria-label={ __(
								'Filter products by status',
								'woocommerce'
							) }
						>
							{ PRODUCT_LIST_TABS.map( ( tab ) => (
								<Tabs.Tab key={ tab.value } value={ tab.value }>
									{ tab.label }
								</Tabs.Tab>
							) ) }
						</Tabs.List>
					</Tabs.Root>
					<Stack direction="row" align="center" gap="xs">
						<DataViews.Search
							label={ __( 'Search products', 'woocommerce' ) }
						/>
						<DataViews.FiltersToggle />
						<DataViews.LayoutSwitcher />
						<DataViews.ViewConfig />
					</Stack>
				</Stack>
				<DataViews.FiltersToggled />
				<DataViews.Layout />
				<DataViews.Footer />
			</DataViews>
		</Page>
	);
}
