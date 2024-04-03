/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, moreVertical, edit, cog } from '@wordpress/icons';
import { Dropdown, Button, MenuGroup, MenuItem } from '@wordpress/components';
import { getNewPath } from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/settings';
import classnames from 'classnames';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
import { SiteVisibilityTour } from '../tour';

export const LaunchYourStoreStatus = ( { comingSoon, storePagesOnly } ) => {
	const isComingSoon = comingSoon && comingSoon === 'yes';
	const isStorePagesOnly =
		isComingSoon && storePagesOnly && storePagesOnly === 'yes';
	const comingSoonText = isStorePagesOnly
		? __( 'Store coming soon', 'woocommerce' )
		: __( 'Site coming soon', 'woocommerce' );
	const liveText = __( 'Live', 'woocommerce' );
	const dropdownText = isComingSoon ? comingSoonText : liveText;
	const launchYourStoreLink = new URL(
		getAdminLink( getNewPath( {}, '/launch-your-store', {} ) )
	);
	const [ showTour, setShowTour ] = useState( true );
	return (
		<div className="woocommerce-lys-status">
			{ showTour && (
				<SiteVisibilityTour
					onClose={ () => {
						setShowTour( false );
					} }
				/>
			) }
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
							<MenuGroup className="woocommerce-lys-status-popover">
								<MenuItem
									href={ getAdminLink(
										'admin.php?page=wc-settings'
									) }
								>
									<Icon icon={ cog } size={ 24 } />
									{ __(
										'Manage site visibility',
										'woocommerce'
									) }
								</MenuItem>
								{ isComingSoon && (
									<MenuItem href={ launchYourStoreLink.href }>
										<Icon icon={ edit } size={ 24 } />
										{ __(
											'Customize "Coming soon" page',
											'woocommerce'
										) }
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
