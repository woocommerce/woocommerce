/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import { DebouncedValidatedTextInput } from '@woocommerce/base-components/text-input';
import { useCheckoutContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import LoginPrompt from './login-prompt';

const ContactFieldsStep = ( { emailValue, onChangeEmail } ) => {
	const { isProcessing: checkoutIsProcessing } = useCheckoutContext();

	return (
		<FormStep
			id="contact-fields"
			disabled={ checkoutIsProcessing }
			className="wc-block-checkout__contact-fields"
			title={ __(
				'Contact information',
				'woo-gutenberg-products-block'
			) }
			description={ __(
				"We'll use this email to send you details and updates about your order.",
				'woo-gutenberg-products-block'
			) }
			stepHeadingContent={ () => <LoginPrompt /> }
		>
			<DebouncedValidatedTextInput
				id="email"
				type="email"
				label={ __( 'Email address', 'woo-gutenberg-products-block' ) }
				value={ emailValue }
				autoComplete="email"
				onChange={ onChangeEmail }
				required={ true }
			/>
		</FormStep>
	);
};

export default ContactFieldsStep;
