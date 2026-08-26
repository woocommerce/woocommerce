/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import type { TemplateArray } from '@wordpress/blocks';
import { useEditorContext } from '@woocommerce/base-context';
import type { ReactElement } from 'react';

interface Props {
	template: TemplateArray;
	view: string;
}

export const Edit = ( { template, view }: Props ): ReactElement => {
	const blockProps = useBlockProps();
	const { currentView } = useEditorContext();

	return (
		<div { ...blockProps } hidden={ currentView !== view }>
			<InnerBlocks template={ template } templateLock="all" />
		</div>
	);
};

export const Save = (): ReactElement => (
	<div { ...useBlockProps.save() }>
		<InnerBlocks.Content />
	</div>
);
