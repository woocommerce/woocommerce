/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { createOrderedChildren, sortFillsByOrder } from '../../utils';

type WooTaskListCompletionItemProps = {
	children: React.ReactNode;
	order?: number;
};

export const EXPERIMENTAL_WC_TASK_LIST_COMPLETION_SLOT_NAME =
	'woocommerce_task_list-completion_item';
/**
 * Create a Fill for extensions to add items to the WooCommerce Admin Task List completion component slot.
 *
 * @slotFill WooTaskListCompletionItem
 * @scope woocommerce-admin
 * @example
 * const MyTasklistCompletionItem = () => (
 * <WooTaskListCompletionItem>My Task List completion item</WooTaskListCompletionItem>
 * );
 *
 * registerPlugin( 'my-extension', {
 * render: MyTasklistCompletionItem,
 * scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array}  param0.children - Node children.
 * @param {Array}  param0.order    - Node order.
 */
export const WooTaskListCompletionItem = ( {
	children,
	order = 1,
}: WooTaskListCompletionItemProps ) => {
	return (
		<Fill name={ EXPERIMENTAL_WC_TASK_LIST_COMPLETION_SLOT_NAME }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooTaskListCompletionItem.Slot = ( {
	fillProps,
}: {
	fillProps?: Slot.Props;
} ) => (
	<Slot
		name={ EXPERIMENTAL_WC_TASK_LIST_COMPLETION_SLOT_NAME }
		fillProps={ fillProps }
	>
		{ sortFillsByOrder }
	</Slot>
);
