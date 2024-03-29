/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';

export function Save() {
	const blockProps = useBlockProps.save();

	return (
		<div
			{ ...blockProps }
			data-wp-interactive='{ "namespace": "myPlugin" }'
		>
			State text: <span data-wp-text="state.someState"></span>
		</div>
	);
}
