/**
 * External dependencies
 */
import { ComponentProps } from 'react';
import { useState } from '@wordpress/element';
import {
	DropdownMenu,
	MenuGroup,
	MenuItem as OriginalMenuItem,
} from '@wordpress/components';
import {
	Icon,
	commentAuthorAvatar,
	external,
	linkOff,
	chevronDown,
} from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './header-account.scss';
import { getAdminSetting } from '../../../utils/admin-settings';
import HeaderAccountModal from './header-account-modal';
import { MARKETPLACE_HOST } from '../constants';
import { connectUrl } from '../../utils/functions';

// Make TS happy: The MenuItem component passes these as an href prop to the underlying button.
interface MenuItemProps extends ComponentProps< typeof OriginalMenuItem > {
	href?: string; // Explicitly declare `href`
}

const MenuItem = ( props: MenuItemProps ) => <OriginalMenuItem { ...props } />;

interface HeaderAccountProps {
	page?: string;
}

export default function HeaderAccount( {
	page = 'wc-admin',
}: HeaderAccountProps ): React.JSX.Element {
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ useDefaultAvatar, setUseDefaultAvatar ] = useState( false );

	const openModal = () => setIsModalOpen( true );

	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const isConnected = wccomSettings?.isConnected ?? false;
	const connectionURL = connectUrl( page );
	const userEmail = wccomSettings?.userEmail;
	const avatarURL = wccomSettings?.userAvatar;

	const accountURL = MARKETPLACE_HOST + '/my-dashboard/';
	const accountOrConnect = isConnected ? accountURL : connectionURL;
	const isInApp = page === 'wc-addons';

	const avatar = () => {
		// Render the default avatar SVG when the user isn't connected, when
		// the connected user has no avatar URL, or when the avatar image
		// failed to load. Previously `avatarURL` fell back to the
		// `commentAuthorAvatar` icon component, which is a JSX element —
		// passing it as <img src> rendered `[object Object]` and only
		// recovered via the onError handler below.
		if ( ! isConnected || ! avatarURL || useDefaultAvatar ) {
			return <Icon icon={ commentAuthorAvatar } size={ 18 } />;
		}

		// Lock the avatar to 18x18 to match every other floating-header tab
		// icon (bell, store, listView, gear, ?). Without this the
		// connected-user state renders the avatar at 30x30, making the User
		// button wider than its neighbours — which knocks tab spacing out
		// of alignment and creates timing issues with adjacent click flows.
		return (
			<img
				src={ avatarURL }
				alt=""
				className="woocommerce-marketplace__header-account-avatar"
				onError={ () => setUseDefaultAvatar( true ) }
			/>
		);
	};

	const dropdownTrigger = () => {
		if ( ! isInApp ) {
			return avatar();
		}

		return (
			<span className="woocommerce-marketplace__header-account-trigger">
				{ avatar() }
				<span
					className="woocommerce-marketplace__header-account-trigger__email"
					title={
						isConnected
							? userEmail
							: __( 'Connect to WooCommerce.com', 'woocommerce' )
					}
				>
					{ isConnected
						? userEmail
						: __( 'Connect to WooCommerce.com', 'woocommerce' ) }
				</span>
				<Icon
					icon={ chevronDown }
					size={ 24 }
					className="woocommerce-marketplace__header-account-trigger__expand-icon"
				/>
			</span>
		);
	};

	const connectionStatusText = isConnected
		? __( 'Connected to WooCommerce.com', 'woocommerce' )
		: __( 'Connect to WooCommerce.com', 'woocommerce' );

	const connectionDetails = () => {
		if ( isConnected ) {
			return (
				<>
					<Icon
						icon={ commentAuthorAvatar }
						size={ 24 }
						className="woocommerce-marketplace__menu-icon"
					/>
					<span className="woocommerce-marketplace__main-text">
						{ userEmail }
					</span>
				</>
			);
		}
		return (
			<>
				<Icon
					icon={ commentAuthorAvatar }
					size={ 24 }
					className="woocommerce-marketplace__menu-icon"
				/>
				<div className="woocommerce-marketplace__menu-text">
					{ __( 'Connect account', 'woocommerce' ) }
					<span className="woocommerce-marketplace__sub-text">
						{ __(
							'Get product updates, manage your subscriptions from your store admin, and get streamlined support.',
							'woocommerce'
						) }
					</span>
				</div>
			</>
		);
	};

	return (
		<>
			<DropdownMenu
				// woocommerce-layout__activity-panel-tab is intentionally
				// only on toggleProps (the inner button) — not on the outer
				// DropdownMenu wrapper. Doubling it up made the User button
				// 16px wider than its neighbours (padding compounded on
				// both elements) and created a click "dead zone" on the
				// outer wrapper that swallowed the first click when
				// switching focus from another tab. Outer alignment is
				// handled by `__user-menu` styles in header-account.scss.
				className="woocommerce-marketplace__user-menu"
				icon={ dropdownTrigger() }
				label={ __( 'User options', 'woocommerce' ) }
				toggleProps={ {
					className: 'woocommerce-layout__activity-panel-tab',
					onClick: () =>
						recordEvent( 'header_account_click', { page } ),
				} }
				popoverProps={ {
					className: 'woocommerce-layout__activity-panel-popover',
				} }
			>
				{ () => (
					<>
						<MenuGroup
							className="woocommerce-layout__homescreen-display-options"
							label={
								isInApp && ! isConnected
									? undefined
									: connectionStatusText
							}
						>
							<MenuItem
								className="woocommerce-marketplace__menu-item"
								href={ accountOrConnect }
								onClick={ () => {
									if ( isConnected ) {
										recordEvent(
											'header_account_view_click',
											{ page }
										);
									} else {
										recordEvent(
											'header_account_connect_click',
											{ page }
										);
									}
								} }
							>
								{ connectionDetails() }
							</MenuItem>
							{ page === 'wc-addons' && ! isConnected && (
								<MenuItem
									href={ accountURL }
									onClick={ () =>
										recordEvent(
											'header_account_view_click',
											{ page }
										)
									}
								>
									<Icon
										icon={ external }
										size={ 24 }
										className="woocommerce-marketplace__menu-icon"
									/>
									{ __(
										'WooCommerce.com account',
										'woocommerce'
									) }
								</MenuItem>
							) }
						</MenuGroup>
						{ isConnected && (
							<MenuGroup className="woocommerce-layout__homescreen-display-options">
								<MenuItem
									onClick={ () => {
										recordEvent(
											'header_account_disconnect_click',
											{ page }
										);
										openModal();
									} }
								>
									<Icon
										icon={ linkOff }
										size={ 24 }
										className="woocommerce-marketplace__menu-icon"
									/>
									{ __(
										'Disconnect account',
										'woocommerce'
									) }
								</MenuItem>
							</MenuGroup>
						) }
					</>
				) }
			</DropdownMenu>
			{ isModalOpen && (
				<HeaderAccountModal
					setIsModalOpen={ setIsModalOpen }
					disconnectURL={ connectionURL }
				/>
			) }
		</>
	);
}
