/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, moreVertical } from '@wordpress/icons';
import { Dropdown, Button, MenuGroup, MenuItem } from '@wordpress/components';
import { getAdminLink } from '@woocommerce/settings';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

export const LaunchYourStoreStatus = ( { comingSoon, storePagesOnly } ) => {
	const isComingSoon = comingSoon && comingSoon === 'yes';
	const dropdownText = isComingSoon
		? __( 'Coming soon', 'woocommerce' )
		: __( 'Live', 'woocommerce' );
	return (
		<div className="woocommerce-lys-status">
			<div className="woocommerce-lys-status-pill-wrapper">
				<Dropdown
					className="woocommerce-lys-status-dropdown"
					focusOnMount={ true }
					popoverProps={ { placement: 'bottom-start' } }
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button onClick={ onToggle } aria-expanded={ isOpen }>
							<div
								className={ classnames(
									'woocommerce-lys-status-pill',
									{ 'is-live': ! isComingSoon }
								) }
							>
								<span>{ dropdownText }</span>
								<Icon icon={ moreVertical } size={ 18 } />
							</div>
						</Button>
					) }
					renderContent={ () => (
						<>
							<MenuGroup>
								<MenuItem
									href={ getAdminLink(
										'admin.php?page=wc-settings'
									) }
								>
									Manage site visibility
								</MenuItem>
								{ isComingSoon && (
									<MenuItem
										href={ getAdminLink(
											'admin.php?page=wc-settings' // For now, waiting on the actual link
										) }
									>
										Customize "Coming soon" page
									</MenuItem>
								) }
							</MenuGroup>
						</>
					) }
				/>
			</div>
		</div>
	);
};
