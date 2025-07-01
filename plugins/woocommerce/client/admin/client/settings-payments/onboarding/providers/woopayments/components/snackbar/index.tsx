/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Snackbar from '~/layout/transient-notices/snackbar';
import './snackbar.scss';

/**
 * A custom snackbar component for the WooPayments onboarding modal.
 */
const WooPaymentsOnboardingModalSnackbar = ( {
	children,
	className,
}: {
	children: React.ReactNode;
	className: string;
} ) => {
	const [ isVisible, setIsVisible ] = useState( false );
	const [ isExiting, setIsExiting ] = useState( false );

	useEffect( () => {
		// Trigger entrance animation after mount
		const showTimer = setTimeout( () => {
			setIsVisible( true );
		}, 100 );

		// Start exit animation before unmount
		const exitTimer = setTimeout( () => {
			setIsExiting( true );
		}, 4700 ); // Start exit animation 300ms before the 5s timeout

		return () => {
			clearTimeout( showTimer );
			clearTimeout( exitTimer );
		};
	}, [] );

	const classNames = [
		'woopayments_onboarding_modal_snackbar_wrapper',
		className,
		isVisible ? 'is-visible' : '',
		isExiting ? 'is-exiting' : '',
	]
		.filter( Boolean )
		.join( ' ' );

	return (
		<div className={ classNames }>
			<Snackbar className={ className + '__snackbar' }>
				{ children }
			</Snackbar>
		</div>
	);
};

export default WooPaymentsOnboardingModalSnackbar;
