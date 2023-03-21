/**
 * External dependencies
 */
import { registerFormatType } from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import { formatType } from './format-type';

export function registerUnderlineFormatType() {
	const { name, ...config } = formatType;

	registerFormatType( name, config );
}
