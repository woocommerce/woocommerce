/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { createSlotFill, Panel } from '@wordpress/components';

const { Slot: InspectorSlot, Fill: InspectorFill } = createSlotFill(
	'ProductBlockEditorSidebarInspector'
);

export function Sidebar() {
	return (
		<div
			className="woocommerce-product-sidebar"
			role="region"
			aria-label={ __(
				'Product Block Editor advanced settings.',
				'woocommerce'
			) }
			tabIndex={ -1 }
		>
			{ /* @ts-ignore */ }
			<Panel header={ __( 'Inspector', 'woocommerce' ) }>
				<InspectorSlot bubblesVirtually />
			</Panel>
		</div>
	);
}

Sidebar.InspectorFill = InspectorFill;
