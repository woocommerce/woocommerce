/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';

export const WC_HEADER_PAGE_TITLE_SLOT_NAME = 'woocommerce_header_page_title';

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
export const WooHeaderPageTitle = ( {
	children,
}: Omit< React.ComponentProps< typeof Fill >, 'name' > ) => {
	return <Fill name={ WC_HEADER_PAGE_TITLE_SLOT_NAME }>{ children }</Fill>;
};

WooHeaderPageTitle.Slot = ( {
	fillProps,
}: {
	fillProps?: React.ComponentProps< typeof Slot >[ 'fillProps' ];
} ) => (
	<Slot name={ WC_HEADER_PAGE_TITLE_SLOT_NAME } fillProps={ fillProps }>
		{ ( fills ) => {
			// @ts-expect-error TypeScript infers `fills` as a single ReactNode, but it is actually an array of ReactNode. https://github.com/WordPress/gutenberg/blob/3416bf4b0db6679b86e8e4226cbdb0d3387b25d7/packages/components/src/slot-fill/slot.tsx#L71-L83
			// Need to fix this upstream.
			return <>{ [ ...fills ].pop() }</>;
		} }
	</Slot>
);
