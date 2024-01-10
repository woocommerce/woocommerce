/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { Icon, commentAuthorAvatar, external, linkOff } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './header-account.scss';
import { getAdminSetting } from '../../../utils/admin-settings';
import HeaderAccountModal from './header-account-modal';
import { MARKETPLACE_HOST } from '../constants';
import { connectUrl } from '../../utils/functions';

export default function HeaderAccount(): JSX.Element {
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const openModal = () => setIsModalOpen( true );

	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const isConnected = wccomSettings?.isConnected ?? false;
	const connectionURL = connectUrl();
	const userEmail = wccomSettings?.userEmail;
	const avatarURL = wccomSettings?.userAvatar ?? commentAuthorAvatar;

	// This is a hack to prevent TypeScript errors. The MenuItem component passes these as an href prop to the underlying button
	// component. That component is either an anchor with href if provided or a button that won't accept an href if no href is provided.
	// Due to early erroring of TypeScript, it only takes the button version into account which doesn't accept href.
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const accountURL: any = MARKETPLACE_HOST + '/my-dashboard/';
	const accountOrConnect = isConnected ? accountURL : connectionURL;

	const avatar = () => {
		if ( ! isConnected ) {
			return commentAuthorAvatar;
		}

		return (
			<img
				src={ avatarURL }
				alt=""
				className="woocommerce-marketplace__menu-avatar-image"
			/>
		);
	};

	const connectionStatusText = isConnected
		? __( 'Connected', 'woocommerce' )
		: __( 'Not Connected', 'woocommerce' );

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
							'Manage your subscriptions, get updates and support for your extensions and themes.',
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
				className="woocommerce-marketplace__user-menu"
				icon={ avatar() }
				label={ __( 'User options', 'woocommerce' ) }
			>
				{ () => (
					<>
						<MenuGroup
							className="woocommerce-layout__homescreen-display-options"
							label={ connectionStatusText }
						>
							<MenuItem
								className="woocommerce-marketplace__menu-item"
								href={ accountOrConnect }
							>
								{ connectionDetails() }
							</MenuItem>
							<MenuItem href={ accountURL }>
								<Icon
									icon={ external }
									size={ 24 }
									className="woocommerce-marketplace__menu-icon"
								/>
								{ __( 'Woo.com account', 'woocommerce' ) }
							</MenuItem>
						</MenuGroup>
						{ isConnected && (
							<MenuGroup className="woocommerce-layout__homescreen-display-options">
								<MenuItem onClick={ openModal }>
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
