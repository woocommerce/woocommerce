/**
 * External dependencies
 */
import { DataViews, View } from '@wordpress/dataviews';
import { useState, useMemo, useCallback, useEffect } from '@wordpress/element';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import clsx from 'clsx';
import { Button, Stack, Tabs } from '@wordpress/ui';
import { privateApis as editorPrivateApis } from '@wordpress/editor';
import { Page } from '@wordpress/admin-ui';
import { addQueryArgs } from '@wordpress/url';
import { getAdminLink } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { unlock } from '../lock-unlock';
import type { ProductEntityRecord } from '../fields/types';
import {
	DEFAULT_LAYOUTS,
	DEFAULT_VIEW,
	EMPTY_ARRAY,
	PAGE_SIZE,
	PRODUCT_LIST_TABS,
} from './constants';
import { productFields } from './fields';
import { buildProductListQuery } from './query';
import {
	getItemId,
	getProductListNavigationPath,
	getProductListTab,
	getSelectionFromPostId,
	getStatusForProductListTab,
	isProductEditorAccessible,
} from './utils';
import { useProductActions } from '../dataviews-actions';

const { usePostActions } = unlock( editorPrivateApis );
const { useHistory, useLocation } = unlock( routerPrivateApis );

export type ProductListProps = {
	subTitle?: string;
	className?: string;
	hideTitleFromUI?: boolean;
	postType?: string;
};

/**
 * This function abstracts working with default & custom views by
 * providing a [ state, setState ] tuple based on the URL parameters.
 *
 * Consumers use the provided tuple to work with state
 * and don't have to deal with the specifics of default & custom views.
 *
 * @return {Array} The [ state, setState ] tuple.
 */
function useView(): [ View, ( view: View ) => void ] {
	const { query: { activeView = 'all' } = {} } = useLocation();
	const [ view, setView ] = useState< View >( DEFAULT_VIEW );

	// When activeView URL parameter changes, reset the view.
	useEffect( () => {
		setView( DEFAULT_VIEW );
	}, [ activeView ] );

	return [ view, setView ];
}

export default function ProductList( { className }: ProductListProps ) {
	const { navigate } = useHistory();
	const location = useLocation();
	const currentQuery = useMemo(
		() =>
			( location.query || {} ) as {
				postId?: string;
				activeView?: string;
				postType?: string;
			},
		[ location.query ]
	);
	const { postId, postType = 'product', activeView = 'all' } = currentQuery;
	const selectedTabFromLocation = getProductListTab( activeView );
	const [ selectedTab, setSelectedTab ] = useState( selectedTabFromLocation );
	const [ selection, setSelection ] = useState( () =>
		getSelectionFromPostId( postId )
	);
	const [ view, setView ] = useView();

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

			const nextParams = { ...currentQuery };

			if ( items.length > 0 ) {
				nextParams.postId = items.join( ',' );
			} else {
				delete nextParams.postId;
			}

			navigate(
				getProductListNavigationPath( location.path, nextParams )
			);
		},
		[ currentQuery, navigate, location.path ]
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
				...currentQuery,
				activeView: nextTab,
			};

			delete nextParams.postId;

			navigate(
				getProductListNavigationPath( location.path, nextParams )
			);
		},
		[ currentQuery, navigate, location.path, selectedTab ]
	);

	const {
		records,
		totalItems: totalCount,
		isResolving: isLoading,
		hasResolved,
	} = useSelect(
		( select ) => {
			const {
				getEntityRecords,
				isResolving,
				hasFinishedResolution,
				getEntityRecordsTotalItems,
			} = select( coreStore );
			return {
				records: getEntityRecords< ProductEntityRecord >(
					'root',
					'product',
					queryParams
				),
				totalItems: getEntityRecordsTotalItems( 'root', 'product', {
					...queryParams,
				} ),
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
				key={ activeView }
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
				isItemClickable={ isProductEditorAccessible }
				renderItemLink={ ( { item, ...props } ) => (
					<a
						{ ...props }
						href={ getAdminLink(
							addQueryArgs( 'post.php', {
								post: item.id,
								action: 'edit',
							} )
						) }
					>
						{ props.children }
					</a>
				) }
			>
				<Stack
					direction="row"
					align="center"
					justify="space-between"
					gap="sm"
					className="woocommerce-product-list__toolbar"
				>
					{ /* Tabs component should not be used: https://github.com/woocommerce/woocommerce/issues/64478 */ }
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
