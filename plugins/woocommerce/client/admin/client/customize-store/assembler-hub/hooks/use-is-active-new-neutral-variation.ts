/**
 * External dependencies
 */
import { useContext, useMemo } from '@wordpress/element';
import { isEqual } from 'lodash';
import {
	privateApis as blockEditorPrivateApis,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/block-editor';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	unlock,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/edit-site/build-module/lock-unlock';

/**
 * Internal dependencies
 */
import { COLOR_PALETTES } from '../sidebar/global-styles/color-palette-variations/constants';

const { GlobalStylesContext } = unlock( blockEditorPrivateApis );

export const useIsActiveNewNeutralVariation = () => {
	// @ts-expect-error No types for this exist yet.
	const { user } = useContext( GlobalStylesContext );
	return useMemo(
		() =>
			isEqual( COLOR_PALETTES[ 0 ].settings.color, user.settings.color ),
		[ user ]
	);
};
