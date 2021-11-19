/**
 * External dependencies
 */
import type { ReactElement } from 'react';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = (): ReactElement => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<p>Editing the mini cart contents</p>
		</div>
	);
};

export default Edit;
