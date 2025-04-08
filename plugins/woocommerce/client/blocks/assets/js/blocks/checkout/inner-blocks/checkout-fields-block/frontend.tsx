/**
 * External dependencies
 */
import type { ReactElement } from 'react';
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { Main } from '@woocommerce/base-components/sidebar-layout';
import { useStoreEvents } from '@woocommerce/base-context/hooks';
import { useEffect } from '@wordpress/element';
import { useCheckoutBlockContext } from '@woocommerce/blocks/checkout/context';

const FrontendBlock = ( {
	children,
	className,
}: {
	children: ReactElement[];
	className?: string;
} ): JSX.Element => {
	const { dispatchCheckoutEvent } = useStoreEvents();
	const { showFormStepNumbers } = useCheckoutBlockContext();

	// Ignore changes to dispatchCheckoutEvent callback so this is ran on first mount only.
	useEffect( () => {
		dispatchCheckoutEvent( 'render-checkout-form' );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return (
		<Main className={ clsx( 'wc-block-checkout__main', className ) }>
			<form
				aria-label={ __( 'Checkout', 'woocommerce' ) }
				className={ clsx(
					'wc-block-components-form wc-block-checkout__form',
					{
						'wc-block-checkout__form--with-step-numbers':
							showFormStepNumbers,
					}
				) }
			>
				{ children }
			</form>
		</Main>
	);
};

export default FrontendBlock;
