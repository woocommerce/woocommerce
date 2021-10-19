/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';

export const gatedStyledText = ( { color, fontSize } ) =>
	isFeaturePluginBuild()
		? {
				color,
				fontSize,
		  }
		: {};
