/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Popover } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { Icon, info } from '@wordpress/icons';
import { Link } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

/**
 * Whether the store leaves free orders out of report totals.
 *
 * Read from an immutable snapshot rather than the settings data store, because
 * report filter configs are evaluated at module load. A store that turns the
 * setting on will see the control after the next full page load.
 *
 * @return {boolean} True when free orders are excluded by default.
 */
export function excludesFreeOrders() {
	return Boolean( getAdminSetting( 'analyticsExcludesFreeOrders', false ) );
}

/**
 * Zero, formatted in the store's own currency.
 *
 * @return {string} Formatted zero, e.g. "$0.00".
 */
function getFreeOrderAmount() {
	return getAdminSetting( 'analyticsFreeOrderAmount', '0' );
}

/**
 * Label for the filter, with a help affordance beside it.
 *
 * A click-toggled popover rather than a tooltip, because it holds a link:
 * tooltips dismiss on pointer-out and are not reachable by keyboard.
 *
 * @return {Object} -
 */
const FreeOrdersLabel = () => {
	const [ isOpen, setIsOpen ] = useState( false );

	return (
		<>
			{ __( 'Orders', 'woocommerce' ) }
			<Button
				className="woocommerce-free-orders-filter__help-toggle"
				label={ __( 'About free orders', 'woocommerce' ) }
				aria-expanded={ isOpen }
				onClick={ () => setIsOpen( ! isOpen ) }
			>
				<Icon icon={ info } size={ 20 } />
			</Button>
			{ isOpen && (
				<Popover
					// Beside the icon rather than below it, so the popover does
					// not cover the control it is describing.
					placement="right-start"
					offset={ 8 }
					focusOnMount="firstElement"
					onClose={ () => setIsOpen( false ) }
				>
					<div className="woocommerce-free-orders-filter__help">
						<p>
							{ sprintf(
								/* translators: %s: zero formatted in the store currency, e.g. $0.00 */
								__(
									'Orders with a total of %s can be included in or excluded from your reports.',
									'woocommerce'
								),
								getFreeOrderAmount()
							) }
						</p>
						<Link
							href={ getNewPath( {}, '/analytics/settings' ) }
							type="wc-admin"
						>
							{ __(
								'Change the default in Analytics settings',
								'woocommerce'
							) }
						</Link>
					</div>
				</Popover>
			) }
		</>
	);
};

/**
 * Builds the "Orders" filter that includes or excludes free orders.
 *
 * Only rendered once the store has turned the setting on, so that a store which
 * never issues free orders is not given a control that does nothing. Visibility
 * follows that setting and never the contents of the date range being viewed: a
 * control that came and went as the dates moved would read as a glitch.
 *
 * @param {Object} options              Options.
 * @param {string} options.defaultValue 'include' or 'exclude'.
 * @return {Object} A report filter config.
 */
export function getFreeOrdersFilter( { defaultValue = 'exclude' } = {} ) {
	return {
		label: <FreeOrdersLabel />,
		staticParams: [ 'chartType', 'paged', 'per_page', 'filter' ],
		param: 'free_orders',
		defaultValue,
		showFilters: () => excludesFreeOrders(),
		filters: [
			{
				label: __( 'Include free orders', 'woocommerce' ),
				value: 'include',
			},
			{
				label: __( 'Exclude free orders', 'woocommerce' ),
				value: 'exclude',
			},
		],
	};
}
