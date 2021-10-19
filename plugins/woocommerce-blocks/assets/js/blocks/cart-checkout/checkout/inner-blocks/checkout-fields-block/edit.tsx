/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { Main } from '@woocommerce/base-components/sidebar-layout';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';
import type { TemplateArray } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	useCheckoutBlockControlsContext,
	useCheckoutBlockContext,
} from '../../context';
import { useForcedLayout, getAllowedBlocks } from '../../../shared';
import './style.scss';

export const Edit = ( { clientId }: { clientId: string } ): JSX.Element => {
	const blockProps = useBlockProps();
	const {
		showOrderNotes,
		showPolicyLinks,
		showReturnToCart,
		cartPageId,
	} = useCheckoutBlockContext();
	const allowedBlocks = getAllowedBlocks( innerBlockAreas.CHECKOUT_FIELDS );

	const {
		addressFieldControls: Controls,
	} = useCheckoutBlockControlsContext();

	const defaultTemplate = ( [
		[ 'woocommerce/checkout-express-payment-block', {}, [] ],
		[ 'woocommerce/checkout-contact-information-block', {}, [] ],
		[ 'woocommerce/checkout-shipping-address-block', {}, [] ],
		[ 'woocommerce/checkout-billing-address-block', {}, [] ],
		[ 'woocommerce/checkout-shipping-methods-block', {}, [] ],
		[ 'woocommerce/checkout-payment-block', {}, [] ],
		showOrderNotes
			? [ 'woocommerce/checkout-order-note-block', {}, [] ]
			: false,
		showPolicyLinks
			? [ 'woocommerce/checkout-terms-block', {}, [] ]
			: false,
		[
			'woocommerce/checkout-actions-block',
			{
				showReturnToCart,
				cartPageId,
			},
			[],
		],
	].filter( Boolean ) as unknown ) as TemplateArray;

	useForcedLayout( {
		clientId,
		registeredBlocks: allowedBlocks,
		defaultTemplate,
	} );

	return (
		<Main className="wc-block-checkout__main">
			<div { ...blockProps }>
				<Controls />
				<form className="wc-block-components-form wc-block-checkout__form">
					<InnerBlocks
						allowedBlocks={ allowedBlocks }
						templateLock={ false }
						template={ defaultTemplate }
						renderAppender={ InnerBlocks.ButtonBlockAppender }
					/>
				</form>
			</div>
		</Main>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
