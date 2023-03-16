/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';

export const EXPERIMENTAL_WC_MARKETING_OVERVIEW_SECTION_SLOT_NAME =
	'experimental_woocommerce_marketing_overview_section';
/**
 * Create a Fill for extensions to add a section to the Marketing Overview page.
 *
 * @slotFill WooMarketingOverviewSection
 * @scope woocommerce-admin
 * @example
 * const MySection = () => (
 * 	<Fill name="experimental_woocommerce_marketing_overview_section">
 * 		<div className="woocommerce-experiments-placeholder-slotfill">
 * 			<div className="placeholder-slotfill-content">
 * 				Slotfill goes in here!
 * 			</div>
 * 		</div>
 * 	</Fill>
 * );
 *
 * registerPlugin( 'my-extension', {
 * render: MySection,
 * scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array}  param0.children - Node children.
 * @param {Array}  param0.order    - Node order.
 */
export const WooMarketingOverviewSection = ( {
	children,
	order = 1,
}: {
	children: React.ReactNode;
	order?: number;
} ) => {
	return (
		<Fill name={ EXPERIMENTAL_WC_MARKETING_OVERVIEW_SECTION_SLOT_NAME }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooMarketingOverviewSection.Slot = ( {
	fillProps,
}: {
	fillProps?: Slot.Props;
} ) => (
	<Slot
		name={ EXPERIMENTAL_WC_MARKETING_OVERVIEW_SECTION_SLOT_NAME }
		fillProps={ fillProps }
	>
		{ sortFillsByOrder }
	</Slot>
);
