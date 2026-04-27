/**
 * External dependencies
 */
import { DataViews, View } from '@wordpress/dataviews';
import { useState, useMemo, useCallback, useEffect } from '@wordpress/element';
import { ProductQuery } from '@woocommerce/data';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { store as coreStore, useEntityRecords } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import clsx from 'clsx';
import { Button, Stack } from '@wordpress/ui';
import { privateApis as editorPrivateApis } from '@wordpress/editor';
import { Page } from '@wordpress/admin-ui';
import { addQueryArgs } from '@wordpress/url';
import { getAdminLink } from '@woocommerce/settings';

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

export default function ProductList( { className }: ProductListProps ) {
	const history = useHistory();
	const location = useLocation();
	const {
		postId,
		postType = 'product',
		isCustom,
		activeView = 'all',
	} = location.params;
	const [ selection, setSelection ] = useState( [ postId ] );
	const [ view, setView ] = useView( postType );

	const queryParams = useMemo( () => {
		const filters: Partial< ProductQuery > = {};
		view.filters?.forEach( ( filter ) => {
			if (
				filter.field === 'status' ||
				filter.field === 'product_status'
			) {
				filters.status = Array.isArray( filter.value )
					? filter.value.join( ',' )
					: filter.value;
			}
		} );
		const orderby =
			view.sort?.field === 'name'
				? 'title'
				: ( view.sort?.field as ProductQuery[ 'orderby' ] );

		return {
			per_page: view.perPage,
			page: view.page,
			order: view.sort?.direction,
			orderby,
			search: view.search,
			...filters,
		};
	}, [ view ] );

	const onChangeSelection = useCallback(
		( items: string[] ) => {
			setSelection( items );
			history.push( {
				...location.params,
				postId: items.join( ',' ),
			} );
		},
		[ history, location.params ]
	);

	const {
		records,
		totalItems: totalCount,
		isResolving: isLoading,
	} = useEntityRecords< ProductEntityRecord >(
		'root',
		'product',
		queryParams
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
				( { id }: { id: string } ) => id !== 'view-post'
			),
		],
		[ postTypeActions, productActions ]
	);

	const classes = clsx( 'edit-site-page', className );

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
				isLoading={ isLoading }
				view={ view }
				actions={ actions }
				onChangeView={ setView }
				onChangeSelection={ onChangeSelection }
				getItemId={ getItemId }
				selection={ selection }
				defaultLayouts={ DEFAULT_LAYOUTS }
			/>
		</Page>
	);
}
