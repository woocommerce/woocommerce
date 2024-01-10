/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';
import {
	FillComponentProps,
	SlotComponentProps,
} from '@woocommerce/components/build-types/types';

export const EXPERIMENTAL_WC_HOMESCREEN_HEADER_BANNER_SLOT_NAME =
	'woocommerce_homescreen_experimental_header_banner_item';
/**
 * Create a Fill for extensions to add items to the WooCommerce Admin Homescreen header banner.
 *
 * @slotFill WooHomescreenHeaderBannerItem
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
export const WooHomescreenHeaderBannerItem = ( {
	children,
	order = 1,
}: {
	children: React.ReactNode;
	order?: number;
} ) => {
	return (
		<Fill name={ EXPERIMENTAL_WC_HOMESCREEN_HEADER_BANNER_SLOT_NAME }>
			{ ( fillProps: FillComponentProps ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooHomescreenHeaderBannerItem.Slot = ( {
	fillProps,
}: {
	fillProps?: SlotComponentProps;
} ) => (
	// @ts-expect-error - This seems like a type issue with Slot.
	<Slot
		name={ EXPERIMENTAL_WC_HOMESCREEN_HEADER_BANNER_SLOT_NAME }
		fillProps={ fillProps }
	>
		{ sortFillsByOrder }
	</Slot>
);
