/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';

/**
 * Create a Fill for extensions to add items to the WooCommerce Admin header.
 *
 * @slotFill WooHeaderItem
 * @scope woocommerce-admin
 * @example
 * const MyHeaderItem = () => (
 * <WooHeaderItem>My header item</WooHeaderItem>
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
export const WooHeaderItem: React.FC< {
	children?: React.ReactNode;
	order?: number;
} > & {
	Slot: React.FC< Slot.Props >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ 'woocommerce_header_item' }>
			{ /* eslint-disable @typescript-eslint/ban-ts-comment */ }
			{
				// @ts-ignore It is okay to pass in a function as a render child of Fill
				( fillProps: Fill.Props ) => {
					return createOrderedChildren( children, order, fillProps );
				}
			}
			{ /* eslint-enable @typescript-eslint/ban-ts-comment */ }
		</Fill>
	);
};

WooHeaderItem.Slot = ( { fillProps } ) => (
	<Slot name={ 'woocommerce_header_item' } fillProps={ fillProps }>
		{ sortFillsByOrder }
	</Slot>
);

/**
 * Create a Fill for extensions to add items to the WooCommerce Admin
 * navigation area left of the page title.
 *
 * @slotFill WooHeaderNavigationItem
 * @scope woocommerce-admin
 * @example
 * const MyNavigationItem = () => (
 * <WooHeaderNavigationItem>My nav item</WooHeaderNavigationItem>
 * );
 *
 * registerPlugin( 'my-extension', {
 * render: MyNavigationItem,
 * scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array}  param0.children - Node children.
 * @param {Array}  param0.order    - Node order.
 */
export const WooHeaderNavigationItem: React.FC< {
	children?: React.ReactNode;
	order?: number;
} > & {
	Slot: React.FC< Slot.Props >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ 'woocommerce_header_navigation_item' }>
			{ /* eslint-disable @typescript-eslint/ban-ts-comment */ }
			{
				// @ts-ignore It is okay to pass in a function as a render child of Fill
				( fillProps: Fill.Props ) => {
					return createOrderedChildren( children, order, fillProps );
				}
			}
			{ /* eslint-enable @typescript-eslint/ban-ts-comment */ }
		</Fill>
	);
};

WooHeaderNavigationItem.Slot = ( { fillProps }: Slot.Props ) => (
	<Slot name={ 'woocommerce_header_navigation_item' } fillProps={ fillProps }>
		{ sortFillsByOrder }
	</Slot>
);

/**
 * Create a Fill for extensions to add custom page titles.
 *
 * @slotFill WooHeaderPageTitle
 * @scope woocommerce-admin
 * @example
 * const MyPageTitle = () => (
 * 	<WooHeaderPageTitle>My page title</WooHeaderPageTitle>
 * );
 *
 * registerPlugin( 'my-page-title', {
 * 	render: MyPageTitle,
 * 	scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array}  param0.children - Node children.
 */
export const WooHeaderPageTitle: React.FC< {
	children?: React.ReactNode;
} > & {
	Slot: React.FC< Slot.Props >;
} = ( { children } ) => {
	// eslint-disable-next-line
	// @ts-ignore
	return <Fill name={ 'woocommerce_header_page_title' }>{ children }</Fill>;
};

WooHeaderPageTitle.Slot = ( { fillProps } ) => (
	<Slot name={ 'woocommerce_header_page_title' } fillProps={ fillProps }>
		{ ( fills ) => {
			return <>{ [ ...fills ].pop() }</>;
		} }
	</Slot>
);
