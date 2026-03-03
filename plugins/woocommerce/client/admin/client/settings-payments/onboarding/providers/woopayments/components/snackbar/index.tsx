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
	duration = 4000,
	className,
}: {
	children: React.ReactNode;
	duration?: number;
	className?: string;
} ) => {
	const [ isVisible, setIsVisible ] = useState( false );
	const [ isExiting, setIsExiting ] = useState( false );

	useEffect( () => {
		// Trigger entrance animation after mount
		const showTimer = setTimeout( () => {
			setIsVisible( true );

			// Start exit animation after the snackbar has been visible
			const exitTimer = setTimeout( () => {
				setIsExiting( true );
			}, duration );

			return () => clearTimeout( exitTimer );
		}, 100 );

		return () => {
			clearTimeout( showTimer );
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
