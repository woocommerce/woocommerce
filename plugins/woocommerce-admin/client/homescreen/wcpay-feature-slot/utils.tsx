/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';

export const EXPERIMENTAL_WC_HOMESCREEN_WC_PAY_FEATURE_SLOT_NAME =
	'experimental_woocommerce_wcpay_feature';
/**
 * Create a Fill for WC Pay to add featured content to the homescreen.
 *
 * @slotFill WooHomescreenWCPayFeatureItem
 * @scope woocommerce-admin
 * @example
 * const MyFill = () => (
 * <Fill name="experimental_woocommerce_wcpay_feature">My fill</fill>
 * );
 *
 * registerPlugin( 'my-extension', {
 * render: MyFill,
 * scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array}  param0.children - Node children.
 * @param {Array}  param0.order    - Node order.
 */
export const WooHomescreenWCPayFeatureItem = ( {
	children,
	order = 1,
}: {
	children: React.ReactNode;
	order?: number;
} ) => {
	return (
		<Fill name={ EXPERIMENTAL_WC_HOMESCREEN_WC_PAY_FEATURE_SLOT_NAME }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooHomescreenWCPayFeatureItem.Slot = ( {
	fillProps,
}: {
	fillProps?: Slot.Props;
} ) => (
	<Slot
		name={ EXPERIMENTAL_WC_HOMESCREEN_WC_PAY_FEATURE_SLOT_NAME }
		fillProps={ fillProps }
	>
		{ sortFillsByOrder }
	</Slot>
);
