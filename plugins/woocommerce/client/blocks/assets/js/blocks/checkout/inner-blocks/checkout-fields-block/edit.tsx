/**
 * External dependencies
 */
import clsx from 'clsx';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { Main } from '@woocommerce/base-components/sidebar-layout';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';
import type { TemplateArray } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { useCheckoutBlockContext } from '../../context';
import {
	useForcedLayout,
	getAllowedBlocks,
} from '../../../cart-checkout-shared';
import './style.scss';
import { AddressFieldControls } from '../../address-field-controls';
export const Edit = ( {
	clientId,
	attributes,
}: {
	clientId: string;
	attributes: {
		className?: string;
		isPreview?: boolean;
	};
} ): JSX.Element => {
	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-checkout__main', attributes?.className ),
	} );
	const allowedBlocks = getAllowedBlocks( innerBlockAreas.CHECKOUT_FIELDS );

	const { showFormStepNumbers } = useCheckoutBlockContext();

	const defaultTemplate = [
		[ 'woocommerce/checkout-express-payment-block', {}, [] ],
		[ 'woocommerce/checkout-contact-information-block', {}, [] ],
		[ 'woocommerce/checkout-shipping-method-block', {}, [] ],
		[ 'woocommerce/checkout-pickup-options-block', {}, [] ],
		[ 'woocommerce/checkout-shipping-address-block', {}, [] ],
		[ 'woocommerce/checkout-billing-address-block', {}, [] ],
		[ 'woocommerce/checkout-shipping-methods-block', {}, [] ],
		[ 'woocommerce/checkout-payment-block', {}, [] ],
		[ 'woocommerce/checkout-additional-information-block', {}, [] ],
		[ 'woocommerce/checkout-order-note-block', {}, [] ],
		[ 'woocommerce/checkout-terms-block', {}, [] ],
		[ 'woocommerce/checkout-actions-block', {}, [] ],
	].filter( Boolean ) as unknown as TemplateArray;

	useForcedLayout( {
		clientId,
		registeredBlocks: allowedBlocks,
		defaultTemplate,
	} );

	return (
		<Main { ...blockProps }>
			<AddressFieldControls />
			<form
				className={ clsx(
					'wc-block-components-form wc-block-checkout__form',
					{
						'wc-block-checkout__form--with-step-numbers':
							showFormStepNumbers,
					}
				) }
			>
				<InnerBlocks
					allowedBlocks={ allowedBlocks }
					templateLock={ false }
					template={ defaultTemplate }
					renderAppender={ InnerBlocks.ButtonBlockAppender }
				/>
			</form>
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
