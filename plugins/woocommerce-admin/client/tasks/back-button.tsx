/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Tooltip } from '@wordpress/components';
import { Icon, chevronLeft } from '@wordpress/icons';
import { getHistory, updateQueryString } from '@woocommerce/navigation';
import { ENTER, SPACE } from '@wordpress/keycodes';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './back-button.scss';

export type BackButtonProps = {
	title: string;
};

export const BackButton: React.FC< BackButtonProps > = ( { title } ) => {
	const homeText = __( 'WooCommerce Home', 'woocommerce' );

	const navigateHome = () => {
		recordEvent( 'topbar_back_button', {
			page_name: title,
		} );
		updateQueryString( {}, getHistory().location.pathname, {} );
	};

	// if it's a task list page, render a back button to the homescreen
	return (
		<Tooltip text={ homeText }>
			<div
				tabIndex={ 0 }
				role="button"
				data-testid="header-back-button"
				className="woocommerce-layout__header-back-button"
				onKeyDown={ ( { keyCode } ) => {
					if ( keyCode === ENTER || keyCode === SPACE ) {
						navigateHome();
					}
				} }
			>
				<Icon icon={ chevronLeft } onClick={ navigateHome } />
			</div>
		</Tooltip>
	);
};
