/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { NameBlockAttributes } from '../types';

// @ts-ignore
export function Edit( { attributes, context }: BlockEditProps< NameBlockAttributes > ) {
	return <div>Product name list view: { context.product.name }</div>;
}
