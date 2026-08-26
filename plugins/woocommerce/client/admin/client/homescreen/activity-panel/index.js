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

const ActivityPanelContent = ( {
	canManageReviews,
	canManageWooCommerce,
	canUpdateStock,
	canViewOrders,
} ) => {
	const panelsData = useSelect(
		( select ) => {
			// Each lookup runs only with the capability its endpoint checks:
			// the wc/v3 orders count needs read_private_shop_orders and the
			// Activity Panel counts need manage_woocommerce. The products count
			// only runs when at least one of its Stock or Reviews consumers can
			// perform every request and action that panel exposes.
			const data = {
				canManageReviews,
				countsEnabled: canManageWooCommerce,
				canUpdateStock,
				loadingOrderAndProductCount: false,
				lowStockProductsCount: null,
				unapprovedReviewsCount: null,
				unreadOrdersCount: null,
				manageStock: getAdminSetting( 'manageStock', 'no' ),
				isTaskListHidden: ! isTaskListVisible( 'setup' ),
				publishedProductCount: 0,
				reviewsEnabled: getAdminSetting( 'reviewsEnabled', 'no' ),
				totalOrderCount: 0,
				orderStatuses: getOrderStatuses( select ),
			};

			if ( canViewOrders ) {
				const {
					getOrdersTotalCount,
					hasFinishedResolution: hasFinishedOrdersResolution,
				} = select( ordersStore );
				data.totalOrderCount = getOrdersTotalCount(
					ORDERS_QUERY_PARAMS,
					0
				);
				data.loadingOrderAndProductCount =
					data.loadingOrderAndProductCount ||
					! hasFinishedOrdersResolution( 'getOrdersTotalCount', [
						ORDERS_QUERY_PARAMS,
						0,
					] );
			}

			if (
				canManageWooCommerce &&
				( canManageReviews || ( canUpdateStock && canViewOrders ) )
			) {
				const {
					getProductsTotalCount,
					hasFinishedResolution: hasFinishedProductsResolution,
				} = select( productsStore );
				data.publishedProductCount = getProductsTotalCount(
					PUBLISHED_PRODUCTS_QUERY_PARAMS,
					0
				);
				data.loadingOrderAndProductCount =
					data.loadingOrderAndProductCount ||
					! hasFinishedProductsResolution( 'getProductsTotalCount', [
						PUBLISHED_PRODUCTS_QUERY_PARAMS,
						0,
					] );
			}

			if ( canManageWooCommerce ) {
				const counts =
					select( activityPanelStore ).getActivityPanelCounts();
				data.unreadOrdersCount =
					counts?.orders_to_fulfill_count ?? null;
				data.lowStockProductsCount =
					counts?.products_low_in_stock_count ?? null;
				data.unapprovedReviewsCount =
					counts?.reviews_to_moderate_count ?? null;
			}

			return data;
		},
		[
			canManageReviews,
			canManageWooCommerce,
			canUpdateStock,
			canViewOrders,
		]
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
	const canViewOrders = currentUserCan( 'read_private_shop_orders' );
	const canViewProducts = currentUserCan( 'read_private_products' );
	const canManageReviews =
		canViewProducts &&
		currentUserCan( 'moderate_comments' ) &&
		currentUserCan( 'edit_products' );
	const canUpdateStock =
		canViewProducts &&
		currentUserCan( 'edit_product' ) &&
		currentUserCan( 'edit_others_products' ) &&
		currentUserCan( 'edit_published_products' );

	// Without manage_woocommerce only the Orders panel can render, and it
	// requires order read access.
	if ( ! canManageWooCommerce && ! canViewOrders ) {
		return null;
	}

	return (
		<ActivityPanelContent
			canManageReviews={ canManageReviews }
			canManageWooCommerce={ canManageWooCommerce }
			canUpdateStock={ canUpdateStock }
			canViewOrders={ canViewOrders }
		/>
	);
};
