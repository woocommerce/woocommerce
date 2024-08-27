/**
 * External dependencies
 */
import { DataViews } from '@wordpress/dataviews';
import type { View, SupportedLayouts } from '@wordpress/dataviews';
import {
	createElement,
	useState,
	useMemo,
	useCallback,
} from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { chevronRight } from '@wordpress/icons';
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

const defaultLayouts: SupportedLayouts = {
	table: {
		layout: {
			primaryField: 'name',
		},
	},
};

export type ProductListProps = {
	subTitle?: string;
	className?: string;
	hideTitleFromUI?: boolean;
	postType?: string;
};

const PAGE_SIZE = 25;

export default function ProductList( {
	subTitle,
	className,
	hideTitleFromUI = false,
}: ProductListProps ) {
	const history = useHistory();
	const location = useLocation();
	const { postId, quickEdit = false } = location.params;
	const [ selection, setSelection ] = useState( [ postId ] );
	const [ view, setView ] = useState< View >( {
		type: 'table',
		perPage: 5,
		page: 1,
		sort: {
			field: 'date',
			direction: 'desc',
		},
		search: '',
		filters: [],
		fields: [ 'name', 'sku', 'date', 'status' ],
		layout: {},
	} );

	const queryParams = useMemo( () => {
		return {
			page: 1,
			per_page: PAGE_SIZE,
		};
	}, [] );

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
	const { records, totalCount } = useSelect(
		( select ) => {
			return {
				records: select( 'wc/admin/products' ).getProducts(
					queryParams
				) as Product[],
				totalCount: select( 'wc/admin/products' ).getProductsTotalCount(
					queryParams
				) as number,
			};
		},
		[ queryParams ]
	);

	if ( ! records ) {
		return null;
	}

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
					data={ records }
					view={ view }
					onChangeView={ setView }
					onChangeSelection={ onChangeSelection }
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
							icon={ chevronRight }
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
