/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import {
	sortFillsByOrder,
	createOrderedChildren,
} from '@woocommerce/components';

export const EXPERIMENTAL_WC_CYS_TRANSITIONAL_PAGE_SECONDARY_BUTTON_SLOT_NAME =
	'customize_your_store_congrats_page_secondary_button';

/**
 * Create a Fill for extensions to add a secondary button to the congrats page.
 *
 * @slotFill WooCYSSecondaryButton
 * @scope woocommerce-admin
 * @example
 * const MyButton = () => (
 * 	<Fill name="__experimental_customize_your_store_congrats_page_secondary_button">
 * 		<Button className="woocommerce-experiments-button-slotfill">
 * 				Slotfill goes in here!
 *    </Button>
 * 	</Fill>
 * );
 *
 * registerPlugin( 'my-extension', {
 * 	render: MyButton,
 * 	scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array}  param0.children - Node children.
 * @param {Array}  param0.order    - Node order.
 */
export const WooCYSSecondaryButton: React.FC< {
	children?: React.ReactNode;
	order?: number;
} > & {
	Slot: React.FC< Slot.Props >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill
			name={
				EXPERIMENTAL_WC_CYS_TRANSITIONAL_PAGE_SECONDARY_BUTTON_SLOT_NAME
			}
		>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooCYSSecondaryButton.Slot = ( { fillProps } ) => (
	<Slot
		name={
			EXPERIMENTAL_WC_CYS_TRANSITIONAL_PAGE_SECONDARY_BUTTON_SLOT_NAME
		}
		fillProps={ fillProps }
	>
		{ sortFillsByOrder }
	</Slot>
);
