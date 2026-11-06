/**
 * External dependencies
 */
import { useCallback, useMemo } from '@wordpress/element';
import { useSelect, dispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { EmailTheme, storeName } from '../store';

export function useUserTheme() {
	const { globalStylePost } = useSelect( ( select ) => {
		const post = select( storeName ).getGlobalEmailStylesPost() || null;
		return {
			globalStylePost: post,
		};
	}, [] );

	// Consumers use this as a dependency for expensive work such as regenerating
	// the global styles stylesheet, so the identity must only change when the
	// styles or settings actually change. Editing styles goes through
	// `editEntityRecord`, which replaces both values, so the memo still updates.
	const userTheme = useMemo(
		() => ( {
			settings: globalStylePost?.settings,
			styles: globalStylePost?.styles,
		} ),
		[ globalStylePost?.settings, globalStylePost?.styles ]
	);

	const updateGlobalStylesPost = useCallback(
		( newTheme: EmailTheme ) => {
			if ( ! globalStylePost ) {
				return;
			}
			void dispatch( coreStore ).editEntityRecord(
				'root',
				'globalStyles',
				globalStylePost.id,
				{
					styles: newTheme.styles,
					settings: newTheme.settings,
				}
			);
		},
		[ globalStylePost ]
	);

	return {
		userTheme,
		updateUserTheme: updateGlobalStylesPost,
	};
}
