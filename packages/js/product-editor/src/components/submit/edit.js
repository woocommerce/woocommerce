/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';

export function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Button type="submit" text="Submit" />
		</div>
	);
}
