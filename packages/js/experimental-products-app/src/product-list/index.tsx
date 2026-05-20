/**
 * External dependencies
 */
import { DataViews, View } from '@wordpress/dataviews';
import { useState, useMemo, useCallback, useEffect } from '@wordpress/element';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import clsx from 'clsx';
import { Button, Icon, Stack, Tabs } from '@wordpress/ui';
import { privateApis as componentsPrivateApis } from '@wordpress/components';
import { privateApis as editorPrivateApis } from '@wordpress/editor';
import { addQueryArgs } from '@wordpress/url';
import { getAdminLink } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import {
	tag,
	alignNone,
	category,
	link,
	chevronDown,
	chevronUp,
} from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { unlock } from '../lock-unlock';
import type { ProductEntityRecord } from '../fields/types';
import {
	DEFAULT_LAYOUTS,
	EMPTY_ARRAY,
	PAGE_SIZE,
	PRODUCT_LIST_TABS,
	type StatusTab,
} from './constants';
import { productFields } from './fields';
import {
	getItemId,
	getProductListNavigationPath,
	getProductListTab,
	getProductsWithEmbeddedVariations,
	getSelectionFromPostId,
	isProductEditorAccessible,
} from './utils';
import { useProductActions } from '../dataviews-actions';
import { ProductListEmptyState } from './empty-state';
import { ProductListPage, ProductListPageHeader } from './page';

const { Menu } = unlock( componentsPrivateApis );
const { usePostActions } = unlock( editorPrivateApis );
const { useHistory, useLocation } = unlock( routerPrivateApis );

const PRODUCT_TYPE_MENU_ITEMS = [
	{
		key: 'simple',
		icon: tag,
		label: __( 'Simple product', 'woocommerce' ),
		info: __( 'A standalone item with no variations.', 'woocommerce' ),
		queryArgs: {},
	},
	{
		key: 'variable',
		icon: alignNone,
		label: __( 'Variable product', 'woocommerce' ),
		info: __(
			'An item with variations like color or size.',
			'woocommerce'
		),
		queryArgs: { product_type: 'variable' },
	},
	{
		key: 'grouped',
		icon: category,
		label: __( 'Grouped product', 'woocommerce' ),
		info: __( 'A collection of related products.', 'woocommerce' ),
		queryArgs: { product_type: 'grouped' },
	},
	{
		key: 'external',
		icon: link,
		label: __( 'Affiliate product', 'woocommerce' ),
		info: __(
			'A product you promote and earn commission on.',
			'woocommerce'
		),
		queryArgs: { product_type: 'external' },
	},
] as const;

export type ProductListProps = {
	subTitle?: string;
	className?: string;
	hideTitleFromUI?: boolean;
	postType?: string;
	hasResolved: boolean;
	isLoading: boolean;
	records?: ProductEntityRecord[] | null;
	selectedTab: StatusTab;
	setSelectedTab: ( selectedTab: StatusTab ) => void;
	setView: ( view: View ) => void;
	totalCount?: number | null;
	view: View;
};

export default function ProductList( {
	className,
	hasResolved,
	isLoading,
	records,
	selectedTab,
	setSelectedTab,
	setView,
	totalCount,
	view,
	postType = 'product',
}: ProductListProps ) {
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
	const { postId, activeView = 'all' } = currentQuery;
	const [ selection, setSelection ] = useState( () =>
		getSelectionFromPostId( postId )
	);
	const [ isMenuOpen, setIsMenuOpen ] = useState( false );

	useEffect( () => {
		setSelection( getSelectionFromPostId( postId ) );
	}, [ postId ] );

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
		[ currentQuery, navigate, location.path, selectedTab, setSelectedTab ]
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

	const data = useMemo(
		() => getProductsWithEmbeddedVariations( records || EMPTY_ARRAY ),
		[ records ]
	);
	const getItemParentId = useCallback(
		( item: ProductEntityRecord ) =>
			item.parent_id && item.parent_id > 0 ? item.parent_id : undefined,
		[]
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
			<Menu onOpenChange={ setIsMenuOpen } placement="bottom-end">
				<Menu.TriggerButton
					disabled={ canCreateRecord === false }
					render={ <Button variant="solid" size="compact" /> }
				>
					{ __( 'Add new', 'woocommerce' ) }
					<Button.Icon
						icon={ isMenuOpen ? chevronUp : chevronDown }
					/>
				</Menu.TriggerButton>
				<Menu.Popover>
					<Menu.Group>
						{ PRODUCT_TYPE_MENU_ITEMS.map( ( item ) => (
							<Menu.Item
								key={ item.key }
								prefix={ <Icon icon={ item.icon } /> }
								onClick={ () => {
									window.location.href = getAdminLink(
										addQueryArgs( 'post-new.php', {
											post_type: 'product',
											...item.queryArgs,
										} )
									);
								} }
							>
								<Menu.ItemLabel>{ item.label }</Menu.ItemLabel>
								<Menu.ItemHelpText>
									{ item.info }
								</Menu.ItemHelpText>
							</Menu.Item>
						) ) }
					</Menu.Group>
				</Menu.Popover>
			</Menu>
		</Stack>
	);

	const toolbar = (
		<Stack
			direction="row"
			align="center"
			justify="space-between"
			gap="sm"
			className="woocommerce-product-list__toolbar"
		>
			{ /* Tabs component should not be used: https://github.com/woocommerce/woocommerce/issues/64478 */ }
			<Tabs.Root value={ selectedTab } onValueChange={ onChangeTab }>
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
				<DataViews.Search label={ __( 'Search', 'woocommerce' ) } />
				<DataViews.FiltersToggle />
				<DataViews.LayoutSwitcher />
				<DataViews.ViewConfig />
			</Stack>
		</Stack>
	);

	return (
		<ProductListPage
			className={ classes }
			ariaLabel={ __( 'Products', 'woocommerce' ) }
		>
			<DataViews
				key={ activeView }
				paginationInfo={ paginationInfo }
				fields={ productFields }
				data={ data }
				isLoading={ isLoading || ! hasResolved }
				view={ view }
				actions={ actions }
				onChangeView={ setView }
				onChangeSelection={ onChangeSelection }
				getItemId={ getItemId }
				getItemParentId={ getItemParentId }
				selection={ selection }
				defaultLayouts={ DEFAULT_LAYOUTS }
				isItemClickable={ isProductEditorAccessible }
				empty={ <ProductListEmptyState tab={ selectedTab } /> }
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
				<ProductListPageHeader
					title={ __( 'Products', 'woocommerce' ) }
					subTitle={ __(
						'Add, edit, and manage the products you sell in your store.',
						'woocommerce'
					) }
					actions={ pageActions }
					toolbar={ toolbar }
				/>
				<DataViews.FiltersToggled className="woocommerce-product-list__filters" />
				<DataViews.Layout />
				<DataViews.Footer />
			</DataViews>
		</ProductListPage>
	);
}
