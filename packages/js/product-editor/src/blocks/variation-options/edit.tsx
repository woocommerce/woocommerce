/**
 * External dependencies
 */

import { useBlockProps } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';

export function Edit() {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<p>hello world</p>
		</div>
	);
}
