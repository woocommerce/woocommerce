/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import Block from './block';
import type { ReviewsByCategoryEditorProps } from './types';

export const Edit = (
	props: unknown & ReviewsByCategoryEditorProps
): JSX.Element => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Block { ...props } />
		</div>
	);
};
