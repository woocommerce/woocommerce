// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
/**
 * External dependencies
 */
import { DataViews } from '@wordpress/dataviews';
import {
	createElement,
	useState,
	useMemo,
	useCallback,
} from '@wordpress/element';
import { edit, chevronRight } from '@wordpress/icons';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import { privateApis as editorPrivateApis } from '@wordpress/editor';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import classNames from 'classnames';
import {
	__experimentalHeading as Heading,
	__experimentalText as Text,
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
	FlexItem,
	Button,
} from '@wordpress/components';

const { usePostActions } = unlock( editorPrivateApis );
const { NavigableRegion } = unlock( editorPrivateApis );
const { useHistory, useLocation } = unlock( routerPrivateApis );

const STATUSES = [
	{ value: 'draft', label: __( 'Draft' ) },
	{ value: 'future', label: __( 'Scheduled' ) },
	{ value: 'pending', label: __( 'Pending Review' ) },
	{ value: 'private', label: __( 'Private' ) },
	{ value: 'publish', label: __( 'Published' ) },
	{ value: 'trash', label: __( 'Trash' ) },
];

/**
 * TODO: auto convert some of the product editor blocks ( from the blocks directory ) to this format.
 * The edit function should work relatively well with the edit from the blocks, the only difference is that the blocks rely on getEntityProp to get the value
 */
const fields = [
	{
		id: 'name',
		label: 'Name',
		enableHiding: false,
		render: ( { item } ) => {
			return item.name;
		},
	},
	{
		id: 'sku',
		label: 'SKU',
		enableHiding: false,
		render: ( { item } ) => {
			return item.sku;
		},
	},
	{
		id: 'date',
		label: 'Date',
		render: ( { item } ) => {
			return <time>{ item.date_created }</time>;
		},
	},
	{
		label: __( 'Status' ),
		id: 'status',
		getValue: ( { item } ) =>
			STATUSES.find( ( { value } ) => value === item.status )?.label ??
			item.status,
		elements: STATUSES,
		filterBy: {
			operators: [ 'isAny' ],
		},
		enableSorting: false,
	},
];

const defaultLayouts = {
	table: {
		layout: {
			primaryKey: 'my-key',
		},
	},
};

const useEditProductAction = () => {
	const history = useHistory();
	return useMemo(
		() => ( {
			id: 'edit-product',
			label: __( 'Edit', 'woocommerce' ),
			isPrimary: true,
			icon: edit,
			supportsBulk: true,
			isEligible( post ) {
				if ( post.status === 'trash' ) {
					return false;
				}
				return true;
			},
			callback( items ) {
				const post = items[ 0 ];
				history.push( {
					page: 'woocommerce-products-dashboard',
					postId: post.id,
					postType: post.type,
					quickEdit: true,
				} );
			},
		} ),
		[ history ]
	);
};

export default function ProductList( {
	subTitle,
	className,
	hideTitleFromUI = false,
} ) {
	const history = useHistory();
	const location = useLocation();
	const { postId, quickEdit = false } = location.params;
	const [ selection, setSelection ] = useState( [ postId ] );
	const classes = classNames( 'edit-site-page', className );
	const [ view, setView ] = useState( {
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
		return {};
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
	const records = useSelect(
		( select ) => {
			return select( 'wc/admin/products' ).getProducts( queryParams );
		},
		[ queryParams ]
	);

	const postTypeActions = usePostActions( {
		postType: 'product',
		context: 'list',
	} );
	const editAction = useEditProductAction();
	const actions = useMemo(
		() => [ editAction, ...postTypeActions ],
		[ postTypeActions, editAction ]
	);

	if ( ! records ) {
		return null;
	}

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
					actions={ actions }
					onChangeView={ setView }
					onChangeSelection={ onChangeSelection }
					selection={ selection }
					fields={ fields }
					defaultLayouts={ defaultLayouts }
					paginationInfo={ {
						totalItems: records.length,
						totalPages: 1,
					} }
					header={
						<Button
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
