/**
 * External dependencies
 */
import clsx from 'clsx';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { FormStep } from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { useCheckoutBlockContext } from '@woocommerce/blocks/checkout/context';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';
import LoginPrompt from './login-prompt';

const FrontendBlock = ( {
	title,
	description,
	children,
	className,
}: {
	title: string;
	description: string;
	showStepNumber: boolean;
	children: JSX.Element;
	className?: string;
} ) => {
	const checkoutIsProcessing = useSelect( ( select ) =>
		select( checkoutStore ).isProcessing()
	);

	const { showFormStepNumbers } = useCheckoutBlockContext();

	return (
		<FormStep
			id="contact-fields"
			disabled={ checkoutIsProcessing }
			className={ clsx( 'wc-block-checkout__contact-fields', className ) }
			title={ title }
			description={ description }
			showStepNumber={ showFormStepNumbers }
			stepHeadingContent={ () => <LoginPrompt /> }
		>
			<Block />
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
