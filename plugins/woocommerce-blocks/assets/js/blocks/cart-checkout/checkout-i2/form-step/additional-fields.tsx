/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import {
	RegisteredBlocks,
	getRegisteredBlockTemplate,
} from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import './editor.scss';
import { useForcedLayout } from '../use-forced-layout';

export const AdditionalFields = ( {
	area,
}: {
	area: keyof RegisteredBlocks;
} ): JSX.Element => {
	const registeredBlocks = getRegisteredBlockTemplate( area );
	const { 'data-block': clientId } = useBlockProps();
	const template = useForcedLayout( {
		clientId,
		template: registeredBlocks,
	} );
	return (
		<div className="wc-block-checkout__additional_fields">
			<InnerBlocks allowedBlocks={ template } />
		</div>
	);
};

export const AdditionalFieldsContent = (): JSX.Element => (
	<InnerBlocks.Content />
);
