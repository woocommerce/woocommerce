/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './editor.scss';
import { useForcedLayout } from '../use-forced-layout';
import { getAllowedBlocks } from '../editor-utils';

export const AdditionalFields = ( {
	block,
}: {
	// Name of the parent block.
	block: string;
} ): JSX.Element => {
	const { 'data-block': clientId } = useBlockProps();
	const allowedBlocks = getAllowedBlocks( block );

	useForcedLayout( {
		clientId,
		template: allowedBlocks,
	} );

	return (
		<div className="wc-block-checkout__additional_fields">
			<InnerBlocks allowedBlocks={ allowedBlocks } />
		</div>
	);
};

export const AdditionalFieldsContent = (): JSX.Element => (
	<InnerBlocks.Content />
);
