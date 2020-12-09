/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';
import { Accordion, AccordionPanel } from '@woocommerce/components';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import './style.scss';
import {
	getLowStockCount,
	getOrderStatuses,
	getUnreadOrders,
} from './orders/utils';
import { getAllPanels } from './panels';
import { getUnapprovedReviews } from './reviews/utils';

export const ActivityPanel = () => {
	const panelsData = useSelect( ( select ) => {
		const totalOrderCount = getSetting( 'orderCount', 0 );
		const orderStatuses = getOrderStatuses( select );
		const reviewsEnabled = getSetting( 'reviewsEnabled', 'no' );
		const countUnreadOrders = getUnreadOrders( select, orderStatuses );
		const manageStock = getSetting( 'manageStock', 'no' );
		const countLowStockProducts = getLowStockCount( select );
		const countUnapprovedReviews = getUnapprovedReviews( select );

		return {
			countLowStockProducts,
			countUnreadOrders,
			manageStock,
			orderStatuses,
			totalOrderCount,
			reviewsEnabled,
			countUnapprovedReviews,
		};
	} );

	const panels = getAllPanels( panelsData );

	return (
		<Accordion>
			<Fragment>
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
					return (
						<AccordionPanel
							key={ id }
							className={ className }
							count={ count }
							initialOpen={ initialOpen }
							title={ title }
							collapsible={ collapsible }
						>
							{ panel }
						</AccordionPanel>
					);
				} ) }
			</Fragment>
		</Accordion>
	);
};
