/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { EditorProvider } from '@woocommerce/base-context';
import type { ReactElement } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';
import { useForcedLayout } from '../../cart-checkout-shared';
import { ORDER_STATUS_BLOCKS, ORDER_STATUS_TEMPLATE } from './inner-blocks';

interface Props {
	attributes: {
		currentView: string;
	};
	clientId: string;
}

const Edit = ( { attributes, clientId }: Props ): ReactElement => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-status',
	} );

	useForcedLayout( {
		clientId,
		registeredBlocks: ORDER_STATUS_BLOCKS,
		defaultTemplate: ORDER_STATUS_TEMPLATE,
	} );

	return (
		<div { ...blockProps }>
			<EditorProvider currentView={ attributes.currentView }>
				<InnerBlocks
					allowedBlocks={ ORDER_STATUS_BLOCKS }
					template={ ORDER_STATUS_TEMPLATE }
					templateLock="all"
				/>
			</EditorProvider>
		</div>
	);
};

export default Edit;

export const Save = (): ReactElement => <InnerBlocks.Content />;
