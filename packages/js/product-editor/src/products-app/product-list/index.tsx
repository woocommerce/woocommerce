/**
 * External dependencies
 */
import { DataViews, View } from '@wordpress/dataviews';
import {
	createElement,
	useState,
	useMemo,
	useCallback,
	useEffect,
	Fragment,
} from '@wordpress/element';
import { Product, ProductQuery } from '@woocommerce/data';
import { drawerRight } from '@wordpress/icons';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { store as coreStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import classNames from 'classnames';
import {
	// @ts-expect-error missing types.
	__experimentalHeading as Heading,
	// @ts-expect-error missing types.
	__experimentalText as Text,
	// @ts-expect-error missing types.
	__experimentalHStack as HStack,
	// @ts-expect-error missing types.
	__experimentalVStack as VStack,
	FlexItem,
	Button,
} from '@wordpress/components';
// @ts-expect-error missing type.
// eslint-disable-next-line @woocommerce/dependency-group
import { privateApis as editorPrivateApis } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { unlock } from '../../lock-unlock';
import {
	useDefaultViews,
	defaultLayouts,
} from '../sidebar-dataviews/default-views';
import { LAYOUT_LIST } from '../constants';
import { productFields } from './fields';
import { useEditProductAction } from '../dataviews-actions';

const { NavigableRegion, usePostActions } = unlock( editorPrivateApis );
const { useHistory, useLocation } = unlock( routerPrivateApis );

export type ProductListProps = {
	subTitle?: string;
	className?: string;
	hideTitleFromUI?: boolean;
	postType?: string;
};

const PAGE_SIZE = 25;
const EMPTY_ARRAY: Product[] = [];

const getDefaultView = (
	defaultViews: Array< { slug: string; view: View } >,
	activeView: string
) => {
	return defaultViews.find( ( { slug } ) => slug === activeView )?.view;
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
function useView(
	postType: string
): [ View, ( view: View ) => void, ( view: View ) => void ] {
	const {
		params: { activeView = 'all', isCustom = 'false', layout },
	} = useLocation();
	const history = useHistory();

	const defaultViews = useDefaultViews( { postType } );
	const [ view, setView ] = useState< View >( () => {
		const initialView = getDefaultView( defaultViews, activeView ) ?? {
			type: layout ?? LAYOUT_LIST,
		};

		const type = layout ?? initialView.type;
		return {
			...initialView,
			type,
		};
	} );

	const setViewWithUrlUpdate = useCallback(
		( newView: View ) => {
			const { params } = history.getLocationWithParams();

			if ( newView.type === LAYOUT_LIST && ! params?.layout ) {
				// Skip updating the layout URL param if
				// it is not present and the newView.type is LAYOUT_LIST.
			} else if ( newView.type !== params?.layout ) {
				history.push( {
					...params,
					layout: newView.type,
				} );
			}

			setView( newView );
		},
		[ history, isCustom ]
	);

	// When layout URL param changes, update the view type
	// without affecting any other config.
	useEffect( () => {
		setView( ( prevView ) => ( {
			...prevView,
			type: layout ?? LAYOUT_LIST,
		} ) );
	}, [ layout ] );

	// When activeView or isCustom URL parameters change, reset the view.
	useEffect( () => {
		const newView = getDefaultView( defaultViews, activeView );

		if ( newView ) {
			const type = layout ?? newView.type;
			setView( {
				...newView,
				type,
			} );
		}
	}, [ activeView, isCustom, layout, defaultViews ] );

	return [ view, setViewWithUrlUpdate, setViewWithUrlUpdate ];
}

function getItemId( item: Product ) {
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
		quickEdit = false,
		postType = 'product',
		isCustom,
		activeView = 'all',
	} = location.params;
	const [ selection, setSelection ] = useState( [ postId ] );
	const [ view, setView ] = useView( postType );

	const queryParams = useMemo( () => {
		const filters: Partial< ProductQuery > = {};
		view.filters?.forEach( ( filter ) => {
			if ( filter.field === 'status' ) {
				filters.status = Array.isArray( filter.value )
					? filter.value.join( ',' )
					: filter.value;
			}
		} );
		const orderby =
			view.sort?.field === 'name' ? 'title' : view.sort?.field;

		return {
			per_page: view.perPage,
			page: view.page,
			order: view.sort?.direction,
			orderby,
			search: view.search,
			...filters,
		};
	}, [ location.params, view ] );

	const onChangeSelection = useCallback(
		( items ) => {
			setSelection( items );
			history.push( {
				...location.params,
				postId: items.join( ',' ),
			} );
		},
		[ history, location.params, view?.type ]
	);

	// TODO: Use the Woo data store to get all the products, as this doesn't contain all the product data.
	const { records, totalCount, isLoading } = useSelect(
		( select ) => {
			const { getProducts, getProductsTotalCount, isResolving } =
				select( 'wc/admin/products' );
			return {
				records: getProducts( queryParams ) as Product[],
				totalCount: getProductsTotalCount( queryParams ) as number,
				isLoading: isResolving( 'getProducts', [ queryParams ] ),
			};
		},
		[ queryParams ]
	);

	const paginationInfo = useMemo(
		() => ( {
			totalItems: totalCount,
			totalPages: Math.ceil( totalCount / ( view.perPage || PAGE_SIZE ) ),
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

	const classes = classNames( 'edit-site-page', className );

	return (
		<NavigableRegion
			className={ classes }
			ariaLabel={ __( 'Products', 'woocommerce' ) }
		>
			<div className="edit-site-page-content">
				{ ! hideTitleFromUI && (
					<VStack
						className="edit-site-page-header"
						as="header"
						spacing={ 0 }
					>
						<HStack className="edit-site-page-header__page-title">
							<Heading
								as="h2"
								level={ 3 }
								weight={ 500 }
								className="edit-site-page-header__title"
								truncate
							>
								{ __( 'Products', 'woocommerce' ) }
							</Heading>
							<FlexItem className="edit-site-page-header__actions">
								{ labels?.add_new_item && canCreateRecord && (
									<>
										<Button
											variant="primary"
											disabled={ true }
											// @ts-expect-error missing type.
											__next40pxDefaultSize
										>
											{ labels.add_new_item }
										</Button>
									</>
								) }
							</FlexItem>
						</HStack>
						{ subTitle && (
							<Text
								variant="muted"
								as="p"
								className="edit-site-page-header__sub-title"
							>
								{ subTitle }
							</Text>
						) }
					</VStack>
				) }
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
					defaultLayouts={ defaultLayouts }
					header={
						<Button
							// @ts-expect-error outdated type.
							size="compact"
							isPressed={ quickEdit }
							icon={ drawerRight }
							label={ __(
								'Toggle details panel',
								'woocommerce'
							) }
							onClick={ () => {
								history.push( {
									...location.params,
									quickEdit: quickEdit ? undefined : true,
								} );
							} }
						/>
					}
				/>
			</div>
		</NavigableRegion>
	);
}
