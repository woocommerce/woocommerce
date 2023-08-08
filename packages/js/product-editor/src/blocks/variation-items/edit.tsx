/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */

export function Edit() {
	const blockProps = useBlockProps();

	return <div { ...blockProps }></div>;
}
