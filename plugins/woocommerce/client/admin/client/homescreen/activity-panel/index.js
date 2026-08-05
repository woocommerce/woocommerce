/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { Badge } from '@woocommerce/components';
import {
	Button,
	Panel,
	PanelBody,
	PanelRow,
	__experimentalText as Text,
} from '@wordpress/components';
import {
	activityPanelStore,
	ordersStore,
	productsStore,
	useUser,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';
import { snakeCase } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';
import { getOrderStatuses } from './orders/utils';
import { getAllPanels } from './panels';
import { getUrlParams } from '../../utils';
import { getAdminSetting } from '~/utils/admin-settings';
import { isTaskListVisible } from '~/hooks/use-tasklists-state';

const ORDERS_QUERY_PARAMS = { _fields: [ 'id' ] };
const PUBLISHED_PRODUCTS_QUERY_PARAMS = {
	status: 'publish',
	_fields: [ 'id' ],
};

const ActivityPanelContent = ( { canManageWooCommerce } ) => {
	const panelsData = useSelect(
		( select ) => {
			const {
				getOrdersTotalCount,
				hasFinishedResolution: hasFinishedOrdersResolution,
			} = select( ordersStore );
			const totalOrderCount = getOrdersTotalCount(
				ORDERS_QUERY_PARAMS,
				0
			);
			const orderStatuses = getOrderStatuses( select );
			const reviewsEnabled = getAdminSetting( 'reviewsEnabled', 'no' );
			const manageStock = getAdminSetting( 'manageStock', 'no' );
			const loadingOrderCount = ! hasFinishedOrdersResolution(
				'getOrdersTotalCount',
				[ ORDERS_QUERY_PARAMS, 0 ]
			);

			const ordersOnlyData = {
				countsEnabled: canManageWooCommerce,
				loadingOrderAndProductCount: loadingOrderCount,
				lowStockProductsCount: null,
				unapprovedReviewsCount: null,
				unreadOrdersCount: null,
				manageStock,
				isTaskListHidden: ! isTaskListVisible( 'setup' ),
				publishedProductCount: 0,
				reviewsEnabled,
				totalOrderCount,
				orderStatuses,
			};

			// The counts endpoint needs manage_woocommerce and the products
			// count needs product read permissions; order managers without
			// them still get the orders panel, driven by data they can query.
			if ( ! canManageWooCommerce ) {
				return ordersOnlyData;
			}

			const {
				getProductsTotalCount,
				hasFinishedResolution: hasFinishedProductsResolution,
			} = select( productsStore );
			const counts =
				select( activityPanelStore ).getActivityPanelCounts();

			return {
				...ordersOnlyData,
				loadingOrderAndProductCount:
					loadingOrderCount ||
					! hasFinishedProductsResolution( 'getProductsTotalCount', [
						PUBLISHED_PRODUCTS_QUERY_PARAMS,
						0,
					] ),
				lowStockProductsCount:
					counts?.products_low_in_stock_count ?? null,
				unapprovedReviewsCount:
					counts?.reviews_to_moderate_count ?? null,
				unreadOrdersCount: counts?.orders_to_fulfill_count ?? null,
				publishedProductCount: getProductsTotalCount(
					PUBLISHED_PRODUCTS_QUERY_PARAMS,
					0
				),
			};
		},
		[ canManageWooCommerce ]
	);

	const panels = panelsData.loadingOrderAndProductCount
		? []
		: getAllPanels( panelsData );

	useEffect( () => {
		if ( panelsData.isTaskListHidden !== undefined ) {
			const visiblePanels = panels.reduce(
				( acc, panel ) => {
					const panelId = snakeCase( panel.id );
					acc[ panelId ] = true;
					return acc;
				},
				{ task_list: panelsData.isTaskListHidden }
			);
			recordEvent( 'activity_panel_visible_panels', visiblePanels );
		}
	}, [ panelsData.isTaskListHidden, panels ] );

	if ( panels.length === 0 ) {
		return null;
	}

	const getInitialOpenState = ( panelId ) => {
		const { opened_panel: openedPanel } = getUrlParams(
			window.location.search
		);
		return panelId === openedPanel;
	};

	return (
		<Panel className="woocommerce-activity-panel">
			{ panels.map( ( panelData ) => {
				const {
					className,
					count,
					id,
					initialOpen,
					panel,
					title,
					collapsible,
				} = panelData;
				return collapsible ? (
					<PanelBody
						title={ [
							<Text
								key={ title }
								variant="title.small"
								size="20"
								lineHeight="28px"
							>
								{ title }
							</Text>,
							count !== null && (
								<Badge
									key={ `${ title }-badge` }
									count={ count }
								/>
							),
						] }
						key={ id }
						className={ className }
						initialOpen={ getInitialOpenState( id ) || initialOpen }
						collapsible={ collapsible }
						disabled={ ! collapsible }
						onToggle={ ( isOpen ) => {
							if ( ! isOpen ) {
								return;
							}

							recordEvent( 'activity_panel_open', {
								tab: id,
							} );
						} }
					>
						<PanelRow>{ panel }</PanelRow>
					</PanelBody>
				) : (
					<div className="components-panel__body" key={ id }>
						<h2 className="components-panel__body-title">
							<Button
								className="components-panel__body-toggle"
								aria-expanded={ false }
								disabled={ true }
							>
								<Text
									variant="title.small"
									size="20"
									lineHeight="28px"
								>
									{ title }
								</Text>
								{ count !== null && <Badge count={ count } /> }
							</Button>
						</h2>
					</div>
				);
			} ) }
		</Panel>
	);
};

export const ActivityPanel = () => {
	const { currentUserCan } = useUser();
	const canManageWooCommerce = currentUserCan( 'manage_woocommerce' );
	// read_private_shop_orders mirrors the orders list permission the panel's
	// queries need; without either capability every request would return 403.
	const canViewOrders = currentUserCan( 'read_private_shop_orders' );

	if ( ! canManageWooCommerce && ! canViewOrders ) {
		return null;
	}

	return (
		<ActivityPanelContent canManageWooCommerce={ canManageWooCommerce } />
	);
};
