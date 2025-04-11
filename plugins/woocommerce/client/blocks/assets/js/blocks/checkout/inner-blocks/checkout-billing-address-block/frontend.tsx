/**
 * External dependencies
 */
import clsx from 'clsx';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { FormStep } from '@woocommerce/blocks-components';
import { useCheckoutAddress } from '@woocommerce/base-context/hooks';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';
import { useCheckoutBlockContext } from '../../context';
import {
	getBillingAddresssBlockTitle,
	getBillingAddresssBlockDescription,
} from './utils';

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
} ): JSX.Element | null => {
	const { showFormStepNumbers } = useCheckoutBlockContext();
	const checkoutIsProcessing = useSelect( ( select ) =>
		select( checkoutStore ).isProcessing()
	);
	const { showBillingFields, forcedBillingAddress, useBillingAsShipping } =
		useCheckoutAddress();

	if ( ! showBillingFields && ! useBillingAsShipping ) {
		return null;
	}

	title = getBillingAddresssBlockTitle( title, forcedBillingAddress );
	description = getBillingAddresssBlockDescription(
		description,
		forcedBillingAddress
	);
	return (
		<FormStep
			id="billing-fields"
			disabled={ checkoutIsProcessing }
			className={ clsx( 'wc-block-checkout__billing-fields', className ) }
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
