/**
 * External dependencies
 */
import classnames from 'classnames';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import { useCheckoutContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';
import LoginPrompt from './login-prompt';
import { useCheckoutBlockContext } from '../../context';

const FrontendBlock = ( {
	title,
	description,
	showStepNumber,
	children,
	className,
}: {
	title: string;
	description: string;
	allowCreateAccount: boolean;
	showStepNumber: boolean;
	children: JSX.Element;
	className?: string;
} ) => {
	const { isProcessing: checkoutIsProcessing } = useCheckoutContext();
	const { allowCreateAccount } = useCheckoutBlockContext();

	return (
		<FormStep
			id="contact-fields"
			disabled={ checkoutIsProcessing }
			className={ classnames(
				'wc-block-checkout__contact-fields',
				className
			) }
			title={ title }
			description={ description }
			showStepNumber={ showStepNumber }
			stepHeadingContent={ () => <LoginPrompt /> }
		>
			<Block allowCreateAccount={ allowCreateAccount } />
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
