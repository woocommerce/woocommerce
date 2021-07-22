/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { Main } from '@woocommerce/base-components/sidebar-layout';
import { getRegisteredBlocks } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import { useCheckoutBlockControlsContext } from '../../context';

const ALLOWED_BLOCKS = [
	'woocommerce/checkout-express-payment-block',
	'woocommerce/checkout-shipping-address-block',
	'woocommerce/checkout-shipping-methods-block',
	'woocommerce/checkout-contact-information-block',
	'woocommerce/checkout-billing-address-block',
	'woocommerce/checkout-payment-block',
	'woocommerce/checkout-order-note-block',
	'woocommerce/checkout-actions-block',
	'woocommerce/checkout-terms-block',
	'core/paragraph',
	'core/heading',
	'core/separator',
	...getRegisteredBlocks( 'fields' ),
];

export const Edit = (): JSX.Element => {
	const blockProps = useBlockProps();
	const {
		addressFieldControls: Controls,
	} = useCheckoutBlockControlsContext();
	return (
		<Main className="wc-block-checkout__main">
			<div { ...blockProps }>
				<Controls />
				<form className="wc-block-components-form wc-block-checkout__form">
					<InnerBlocks
						allowedBlocks={ ALLOWED_BLOCKS }
						templateLock={ false }
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
