/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { chevronLeft } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { SidebarComponentProps } from '../xstate';
import MobileSidebarToggle from './mobile-toggle';

export const LaunchStoreHubMobileHeader = ( props: SidebarComponentProps ) => {
	const handleBackClick = () => {
		props.sendEventToSidebar( {
			type: 'POP_BROWSER_STACK', // go back to previous URL
		} );
	};

	return (
		<div className="mobile-header launch-store-hub-mobile-header">
			<Button
				className="mobile-header__back-button"
				onClick={ handleBackClick }
				icon={ chevronLeft }
				iconSize={ 20 }
				aria-label={ __( 'Go back', 'woocommerce' ) }
			/>
			<h1 className="mobile-header__title">
				{ __( 'Launch your store', 'woocommerce' ) }
			</h1>
			{ props.onToggle && (
				<MobileSidebarToggle onToggle={ props.onToggle } />
			) }
		</div>
	);
};
