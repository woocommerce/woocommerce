/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';

export const EXPERIMENTAL_WC_TASKLIST_FOOTER_SLOT_NAME =
	'experimental_woocommerce_tasklist_footer_item';
/**
 * Create a Fill for extensions to add items to the WooCommerce Admin Task List footer.
 *
 * @slotFill ExperimentalWooTaskListFooterItem
 * @scope woocommerce-admin
 * @example
 * const MyFooterItem = () => (
 * 	<Fill name="experimental_woocommerce_tasklist_footer_item">
 * 		<div className="woocommerce-experiments-placeholder-slotfill">
 * 			<div className="placeholder-slotfill-content">
 * 				Slotfill goes in here!
 * 			</div>
 * 		</div>
 * 	</Fill>
  );
 *
 * registerPlugin( 'my-extension', {
 * render: MyFooterItem,
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
			{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
			{ /* @ts-ignore - TODO - fix this type error. */ }
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

ExperimentalWooTaskListFooterItem.Slot = ( {
	fillProps,
}: {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - TODO - fix this type error.
	fillProps?: Slot.Props;
} ) => (
	<Slot
		name={ EXPERIMENTAL_WC_TASKLIST_FOOTER_SLOT_NAME }
		fillProps={ fillProps }
	>
		{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
		{ /* @ts-ignore - TODO - fix this type error. */ }
		{ sortFillsByOrder }
	</Slot>
);
