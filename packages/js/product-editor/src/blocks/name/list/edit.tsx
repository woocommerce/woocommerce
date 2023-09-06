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

export function Edit( { attributes }: BlockEditProps< NameBlockAttributes > ) {
	return <div>list view</div>;
}
