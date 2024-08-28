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
} from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { drawerRight } from '@wordpress/icons';
import { privateApis as routerPrivateApis } from '@wordpress/router';
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

const { NavigableRegion } = unlock( editorPrivateApis );
const { useHistory, useLocation } = unlock( routerPrivateApis );

const STATUSES = [
	{ value: 'draft', label: __( 'Draft', 'woocommerce' ) },
	{ value: 'future', label: __( 'Scheduled', 'woocommerce' ) },
	{ value: 'pending', label: __( 'Pending Review', 'woocommerce' ) },
	{ value: 'private', label: __( 'Private', 'woocommerce' ) },
	{ value: 'publish', label: __( 'Published', 'woocommerce' ) },
	{ value: 'trash', label: __( 'Trash', 'woocommerce' ) },
];

/**
 * TODO: auto convert some of the product editor blocks ( from the blocks directory ) to this format.
 * The edit function should work relatively well with the edit from the blocks, the only difference is that the blocks rely on getEntityProp to get the value
 */
const fields = [
	{
		id: 'name',
		label: __( 'Name', 'woocommerce' ),
		enableHiding: false,
		type: 'text',
		render: function nameRender( { item }: { item: Product } ) {
			return item.name;
		},
	},
	{
		id: 'sku',
		label: __( 'SKU', 'woocommerce' ),
		enableHiding: false,
		render: ( { item }: { item: Product } ) => {
			return item.sku;
		},
	},
	{
		id: 'date',
		label: __( 'Date', 'woocommerce' ),
		render: ( { item }: { item: Product } ) => {
			return <time>{ item.date_created }</time>;
		},
	},
	{
		label: __( 'Status', 'woocommerce' ),
		id: 'status',
		getValue: ( { item }: { item: Product } ) =>
			STATUSES.find( ( { value } ) => value === item.status )?.label ??
			item.status,
		elements: STATUSES,
		filterBy: {
			operators: [ 'isAny' ],
		},
		enableSorting: false,
	},
];

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

export default function ProductList( {
	subTitle,
	className,
	hideTitleFromUI = false,
}: ProductListProps ) {
	const history = useHistory();
	const location = useLocation();
	const { postId, quickEdit = false, postType = 'product' } = location.params;
	const [ selection, setSelection ] = useState( [ postId ] );
	const [ view, setView ] = useView( postType );

	const queryParams = useMemo( () => {
		const additionalParams = view?.filters?.reduce( ( params, filter ) => {
			params[ filter.field ] = filter.value;
			return params;
		}, {} as Record< string, string > );
		return {
			page: 1,
			per_page: PAGE_SIZE,
			order: view.sort?.direction,
			orderby: view.sort?.field,
			search: view.search,
			...additionalParams,
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
								{ /* { actions } */ }
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
					data={ records || EMPTY_ARRAY }
					view={ view }
					onChangeView={ setView }
					onChangeSelection={ onChangeSelection }
					isLoading={ isLoading }
					selection={ selection }
					// @ts-expect-error types seem rather strict for this still.
					fields={ fields }
					defaultLayouts={ defaultLayouts }
					paginationInfo={ {
						totalItems: totalCount,
						totalPages: Math.ceil( totalCount / PAGE_SIZE ),
					} }
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
