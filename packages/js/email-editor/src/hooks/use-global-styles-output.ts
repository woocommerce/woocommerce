/**
 * External dependencies
 */
import {
	generateGlobalStyles,
	type GlobalStylesConfig,
} from '@wordpress/global-styles-engine';
import { useMemo } from '@wordpress/element';

const EMPTY_OBJECT = {};
const EMPTY_ARRAY = [];

export const useGlobalStylesOutputWithConfig = (
	mergedConfig: GlobalStylesConfig = {}
) => {
	return useMemo( () => {
		if ( ! mergedConfig?.styles || ! mergedConfig?.settings ) {
			return [ EMPTY_ARRAY, EMPTY_OBJECT ];
		}
		const blockTypes = [];
		const styles = generateGlobalStyles( mergedConfig, blockTypes, {
			hasBlockGapSupport: true,
			hasFallbackGapSupport: false,
			disableLayoutStyles: false,
			disableRootPadding: false,
		} );
		return styles;
	}, [ mergedConfig ] );
};
