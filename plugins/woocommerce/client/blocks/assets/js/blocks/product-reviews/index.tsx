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

const blockConfig = {
	...metadata,
	edit,
	save,
};
// @ts-expect-error metadata is not typed.
registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
