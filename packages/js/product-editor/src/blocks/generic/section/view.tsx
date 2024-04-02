/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type { ProductEditorBlockEditProps } from '../../../types';
import { SectionBlockAttributes } from './types';

export function View( {
	attributes,
	// @ts-ignore Need to add type declaration for children.
	children,
}: ProductEditorBlockEditProps< SectionBlockAttributes > ) {
	return (
		<div>
			<h2>{ attributes.title }</h2>
			<div>{ children }</div>
		</div>
	);
}
