/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

const v1 = {
	save: () => {
		const blockProps = useBlockProps.save();

		return (
			<div { ...blockProps }>
				{ /* @ts-expect-error: `InnerBlocks.Content` is a component that is typed in WordPress core*/ }
				<InnerBlocks.Content />
			</div>
		);
	},
};

const deprecated = [ v1 ];

export default deprecated;
