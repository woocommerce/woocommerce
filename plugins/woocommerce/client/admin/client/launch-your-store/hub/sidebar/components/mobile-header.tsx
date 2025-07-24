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

export const LaunchStoreHubMobileHeader = ( props: SidebarComponentProps ) => {
	const handleBackClick = () => {
		props.sendEventToSidebar( {
			type: 'POP_BROWSER_STACK', // go back to previous URL
		} );
	};

	return (
		<div className="launch-your-store-mobile-header launch-store-hub-mobile-header">
			<Button
				className="launch-your-store-mobile-header__back-button"
				onClick={ handleBackClick }
				icon={ chevronLeft }
				iconSize={ 20 }
				aria-label={ __( 'Go back', 'woocommerce' ) }
			/>
			<h1 className="launch-your-store-mobile-header__title">
				{ __( 'Launch Your Store', 'woocommerce' ) }
			</h1>
		</div>
	);
};
