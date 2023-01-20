/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { createOrderedChildren, sortFillsByOrder } from '../../utils';

export const EXPERIMENTAL_WC_TASKLIST_FOOTER_SLOT_NAME =
	'experimental_woocommerce_tasklist_footer_item';
/**
 * Create a Fill for extensions to add items to the WooCommerce Admin Homescreen header banner.
 *
 * @slotFill ExperimentalWooTaskListFooterItem
 * @scope woocommerce-admin
 * @example
 * const MyHeaderItem = () => (
 * <WooHomescreenHeaderBannerItem>My header item</WooHomescreenHeaderBannerItem>
 * );
 *
 * registerPlugin( 'my-extension', {
 * render: MyHeaderItem,
 * scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array}  param0.children - Node children.
 * @param {Array}  param0.order    - Node order.
 */
export const ExperimentalWooTaskListFooterItem = ( {
	children,
	order = 1,
}: {
	children: React.ReactNode;
	order?: number;
} ) => {
	return (
		<Fill name={ EXPERIMENTAL_WC_TASKLIST_FOOTER_SLOT_NAME }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

ExperimentalWooTaskListFooterItem.Slot = ( {
	fillProps,
}: {
	fillProps?: Slot.Props;
} ) => (
	<Slot
		name={ EXPERIMENTAL_WC_TASKLIST_FOOTER_SLOT_NAME }
		fillProps={ fillProps }
	>
		{ sortFillsByOrder }
	</Slot>
);
