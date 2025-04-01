/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import save from './save';
import edit from './edit';
import icon from './icon';

const blockConfig = {
	...metadata,
	icon,
	edit,
	save,
};
// @ts-expect-error blockConfig is not typed.
registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
