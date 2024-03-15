/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { isWpVersion } from '@woocommerce/settings';
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { getGutenbergVersion } from '../../../utils/get-gutenberg-version';

export const useBlockToolbarSettings = () => {
	const { hasFixedToolbar: hasInlineFixedBlockToolbarPreference } = useSelect(
		( select ) => {
			// @ts-expect-error These selectors are available in the block data store.
			const { get: getPreference } = select( preferencesStore );
			return {
				hasFixedToolbar: getPreference( 'core', 'fixedToolbar' ),
			};
		},
		[]
	);

	const canUseInlineFixedBlockToolbar =
		isWpVersion( '6.5', '>=' ) || getGutenbergVersion() > 17.3;

	const forceInlineFixedBlockToolbar =
		getGutenbergVersion() >= 17.9 && getGutenbergVersion() < 18.0;

	const hasInlineFixedBlockToolbar =
		canUseInlineFixedBlockToolbar &&
		( hasInlineFixedBlockToolbarPreference ||
			forceInlineFixedBlockToolbar );

	return {
		hasInlineFixedBlockToolbarPreference,
		canUseInlineFixedBlockToolbar,
		forceInlineFixedBlockToolbar,
		hasInlineFixedBlockToolbar,
	};
};
