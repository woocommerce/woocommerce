/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, moreVertical, edit, cog } from '@wordpress/icons';
import { Dropdown, Button, MenuGroup, MenuItem } from '@wordpress/components';
import { getAdminLink, getSetting } from '@woocommerce/settings';
import classnames from 'classnames';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './style.scss';
import { SiteVisibilityTour } from '../tour';
import { useSiteVisibilityTour } from '../tour/use-site-visibility-tour';
import { COMING_SOON_PAGE_EDITOR_LINK } from '../constants';

export const LaunchYourStoreStatus = ( {
	comingSoon,
	storePagesOnly,
}: {
	comingSoon: string;
	storePagesOnly: string;
} ) => {
	const isComingSoon = comingSoon && comingSoon === 'yes';
	const isStorePagesOnly =
		isComingSoon && storePagesOnly && storePagesOnly === 'yes';
	const comingSoonText = isStorePagesOnly
		? __( 'Store coming soon', 'woocommerce' )
		: __( 'Site coming soon', 'woocommerce' );
	const liveText = __( 'Live', 'woocommerce' );
	const dropdownText = isComingSoon ? comingSoonText : liveText;
	const { showTour, setShowTour, onClose, shouldTourBeShown } =
		useSiteVisibilityTour();

	return (
		<div className="woocommerce-lys-status">
			{ shouldTourBeShown && showTour && (
				<SiteVisibilityTour
					onClose={ () => {
						onClose();
						setShowTour( false );
					} }
				/>
			) }
			<div className="woocommerce-lys-status-pill-wrapper">
				<Dropdown
					className="woocommerce-lys-status-dropdown"
					// @ts-expect-error Property does exists
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
									onClick={ () => {
										recordEvent(
											'launch_your_store_badge_menu_manage_site_visibility_click',
											{
												site_visibility: isComingSoon
													? 'coming_soon'
													: 'live',
											}
										);
									} }
									// @ts-expect-error Prop gets passed down to underlying button https://developer.wordpress.org/block-editor/reference-guides/components/menu-item/#props
									href={ getAdminLink(
										'admin.php?page=wc-settings&tab=site-visibility'
									) }
								>
									<Icon icon={ cog } size={ 24 } />
									{ __(
										'Manage site visibility',
										'woocommerce'
									) }
								</MenuItem>
								{ isComingSoon &&
									getSetting( 'currentThemeIsFSETheme' ) && (
										<MenuItem
                                        	onClick={ () => {
											    recordEvent(
												    'launch_your_store_badge_menu_customize_coming_soon_click'
											    );
									    	} }
											// @ts-expect-error Prop gets passed down to underlying button https://developer.wordpress.org/block-editor/reference-guides/components/menu-item/#props
											href={
												COMING_SOON_PAGE_EDITOR_LINK
											}
										>
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
