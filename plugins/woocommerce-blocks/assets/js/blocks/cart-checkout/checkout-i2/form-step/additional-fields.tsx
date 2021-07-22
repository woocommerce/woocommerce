/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import {
	RegisteredBlocks,
	getRegisteredBlocks,
} from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import './editor.scss';

export const AdditionalFields = ( {
	area,
}: {
	area: keyof RegisteredBlocks;
} ): JSX.Element => {
	return (
		<div className="wc-block-checkout__additional_fields">
			<InnerBlocks allowedBlocks={ getRegisteredBlocks( area ) } />
		</div>
	);
};

export const AdditionalFieldsContent = (): JSX.Element => (
	<InnerBlocks.Content />
);
