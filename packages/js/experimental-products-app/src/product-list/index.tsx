/**
 * External dependencies
 */
import { DataViews, View } from '@wordpress/dataviews';
import {
	useState,
	useMemo,
	useCallback,
	useEffect,
	Fragment,
} from '@wordpress/element';
import { ProductQuery, productsStore } from '@woocommerce/data';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { store as coreStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import clsx from 'clsx';
import { Button } from '@wordpress/components';
import { privateApis as editorPrivateApis } from '@wordpress/editor';
import { Page } from '@wordpress/admin-ui';

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
import { useEditProductAction } from '../dataviews-actions';

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

export default function ProductList( {
	subTitle,
	className,
	hideTitleFromUI = false,
}: ProductListProps ) {
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

	// TODO: Use the Woo data store to get all the products, as this doesn't contain all the product data.
	const { records, totalCount, isLoading } = useSelect(
		( select ) => {
			// @ts-expect-error - The productsStore doesn't have types yet.
			const { getProducts, getProductsTotalCount, isResolving } =
				select( productsStore );
			return {
				records: getProducts( queryParams ) as ProductEntityRecord[],
				totalCount: getProductsTotalCount( queryParams ),
				isLoading: isResolving( 'getProducts', [ queryParams ] ),
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

	const { labels, canCreateRecord } = useSelect(
		( select ) => {
			const { getPostType, canUser } = select( coreStore );
			const postTypeData:
				| { labels: Record< string, string > }
				| undefined = getPostType( postType );
			return {
				labels: postTypeData?.labels,
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
	const editAction = useEditProductAction( { postType } );
	const actions = useMemo(
		() => [ editAction, ...postTypeActions ],
		[ postTypeActions, editAction ]
	);

	const classes = clsx( 'edit-site-page', className );

	const pageActions = ! hideTitleFromUI && (
		<Fragment>
			{ labels?.add_new_item && canCreateRecord && (
				<Button
					variant="primary"
					disabled={ true }
					__next40pxDefaultSize
				>
					{ labels.add_new_item }
				</Button>
			) }
		</Fragment>
	);

	return (
		<Page
			className={ classes }
			ariaLabel={ __( 'Products', 'woocommerce' ) }
			title={
				hideTitleFromUI ? undefined : __( 'Products', 'woocommerce' )
			}
			subTitle={ hideTitleFromUI ? undefined : subTitle }
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
