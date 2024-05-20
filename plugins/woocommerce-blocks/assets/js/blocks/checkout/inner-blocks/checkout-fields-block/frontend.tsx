/**
 * External dependencies
 */
import type { ReactElement } from 'react';
import classnames from 'classnames';
import { Main } from '@woocommerce/base-components/sidebar-layout';
import { useStoreEvents } from '@woocommerce/base-context/hooks';
import { useEffect } from '@wordpress/element';

const FrontendBlock = ( {
	children,
	className,
}: {
	children: ReactElement[];
	className?: string;
} ): JSX.Element => {
	const { dispatchCheckoutEvent } = useStoreEvents();

	// Ignore changes to dispatchCheckoutEvent callback so this is ran on first mount only.
	useEffect( () => {
		dispatchCheckoutEvent( 'render-checkout-form' );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return (
		<Main className={ classnames( 'wc-block-checkout__main', className ) }>
			<form className="wc-block-components-form wc-block-checkout__form">
				{ children }
			</form>
		</Main>
	);
};

export default FrontendBlock;
