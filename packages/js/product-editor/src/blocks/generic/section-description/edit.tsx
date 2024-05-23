/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { SectionDescriptionBlockAttributes } from './types';

export function SectionDescriptionBlockEdit( {
	attributes,
}: ProductEditorBlockEditProps< SectionDescriptionBlockAttributes > ) {
	const { content } = attributes;

	return (
		<div>
			<div>{ content }</div>
		</div>
	);
}
