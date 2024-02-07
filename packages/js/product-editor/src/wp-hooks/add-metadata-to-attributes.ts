/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

export default function () {
	addFilter(
		'blocks.registerBlockType',
		'woocommerce/add-metadata-to-attributes',
		( settings ) => {
			if ( ! settings.attributes ) {
				settings.attributes = {};
			}

			if ( ! settings.attributes.metadata ) {
				settings.attributes.metadata = {
					type: 'object',
					default: {},
				};
			}

			return settings;
		}
	);
}
