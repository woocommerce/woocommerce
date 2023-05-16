/**
 * External dependencies
 */
import { PluginArea } from '@wordpress/plugins';

import { SlotFillProvider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { WooAIButtonItem } from '../components';

export function DescriptionButtonContainer() {
	return (
		<SlotFillProvider>
			<WooAIButtonItem.Slot />
			{ /* @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated. */ }
			<PluginArea scope="woocommerce-ai-product-description" />
		</SlotFillProvider>
	);
}
