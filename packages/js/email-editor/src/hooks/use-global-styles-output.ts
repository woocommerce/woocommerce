/**
 * External dependencies
 */
import {
	generateGlobalStyles,
	GlobalStylesConfig,
} from '@wordpress/global-styles-engine';
import { useMemo } from '@wordpress/element';
import { store as blocksStore } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';

const EMPTY_OBJECT = {};
const EMPTY_ARRAY = [];

export const useGlobalStylesOutputWithConfig = (
	mergedConfig: GlobalStylesConfig = {}
) => {
	const { getBlockStyles } = useSelect( ( select ) => {
		return {
			// @ts-expect-error No types for blocksStore.getBlockStyles.
			getBlockStyles: select( blocksStore ).getBlockStyles,
		};
	} );

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
			getBlockStyles,
		} );
		return styles;
	}, [ mergedConfig, getBlockStyles ] );
};
