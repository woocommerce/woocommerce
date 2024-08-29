/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';

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
			{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
			{ /* @ts-ignore - TODO - fix this type error. */ }
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooHomescreenHeaderBannerItem.Slot = ( {
	fillProps,
}: {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - TODO - fix this type error.
	fillProps?: Slot.Props;
} ) => (
	<Slot
		name={ EXPERIMENTAL_WC_HOMESCREEN_HEADER_BANNER_SLOT_NAME }
		fillProps={ fillProps }
	>
		{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
		{ /* @ts-ignore - TODO - fix this type error. */ }
		{ sortFillsByOrder }
	</Slot>
);
