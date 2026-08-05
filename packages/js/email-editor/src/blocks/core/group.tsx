/**
 * Internal dependencies
 */
import { updateBlockSettings } from '../../config-tools/block-config';

/**
 * Disables layout support for group blocks because the default layout `flex` add gaps between columns that it is not possible to support in emails.
 */
function disableGroupVariations() {
	updateBlockSettings( 'core/group', ( settings ) => {
		// @ts-expect-error: variations is not typed
		const variations = settings.variations ?? [];

		return {
			...settings,
			variations: variations.filter(
				( variation ) => variation.name === 'group'
			),
			supports: {
				...( settings.supports || {} ),
				layout: false,
			},
		};
	} );
}

export { disableGroupVariations };
