/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { SidebarItemProps } from '~/settings-payments/onboarding/types';
import { WC_ASSET_URL } from '~/utils/admin-settings';

/**
 * Sidebar navigation item component
 */
export default function SidebarItem( {
	label,
	isCompleted,
	isActive,
}: SidebarItemProps ): React.ReactNode {
	return (
		<div
			className={ clsx(
				'settings-payments-onboarding-modal__sidebar--list-item',
				{
					'is-active': isActive,
					'is-completed': isCompleted,
				}
			) }
		>
			<span className="settings-payments-onboarding-modal__sidebar--list-item-icon">
				{ isCompleted ? (
					<img
						src={
							WC_ASSET_URL +
							'images/onboarding/icons/complete.svg'
						}
						alt={ __( 'Step completed', 'woocommerce' ) }
					/>
				) : (
					<img
						src={
							WC_ASSET_URL + 'images/onboarding/icons/pending.svg'
						}
						alt={ __( 'Step active', 'woocommerce' ) }
					/>
				) }
			</span>
			<span className="settings-payments-onboarding-modal__sidebar--list-item-label">
				{ label }
			</span>
		</div>
	);
}
