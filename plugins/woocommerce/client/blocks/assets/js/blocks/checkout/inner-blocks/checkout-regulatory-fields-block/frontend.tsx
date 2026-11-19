/**
 * External dependencies
 */
import clsx from 'clsx';
import { FormStep } from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { useCheckoutBlockContext } from '@woocommerce/blocks/checkout/context';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';
import { useRegulatoryFields } from './use-regulatory-fields';

const FrontendBlock = ( {
	title,
	description,
	children,
	className,
}: {
	title: string;
	description: string;
	children: JSX.Element;
	className?: string;
} ) => {
	const { showFormStepNumbers } = useCheckoutBlockContext();
	const fields = useRegulatoryFields();
	const checkoutIsProcessing = useSelect(
		( select ) => select( checkoutStore ).isProcessing(),
		[]
	);

	if ( fields.length === 0 ) {
		return null;
	}

	return (
		<FormStep
			id="regulatory-fields"
			disabled={ checkoutIsProcessing }
			className={ clsx(
				'wc-block-checkout__regulatory-fields',
				className
			) }
			title={ title }
			description={ description }
			showStepNumber={ showFormStepNumbers }
		>
			<Block />
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
