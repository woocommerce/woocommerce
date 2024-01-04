/**
 * External dependencies
 */
import classnames from 'classnames';
import { FormStep } from '@woocommerce/blocks-components';
import { ADDITIONAL_FORM_KEYS } from '@woocommerce/block-settings';
import { useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';

const FrontendBlock = ( {
	title,
	description,
	showStepNumber,
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
		select( CHECKOUT_STORE_KEY ).isProcessing()
	);

	if ( ADDITIONAL_FORM_KEYS.length === 0 ) {
		return null;
	}

	return (
		<FormStep
			id="additional-information-fields"
			disabled={ checkoutIsProcessing }
			className={ classnames(
				'wc-block-checkout__additional-information-fields',
				className
			) }
			title={ title }
			description={ description }
			showStepNumber={ showStepNumber }
		>
			<Block />
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
