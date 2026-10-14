/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { IconButton, Text } from '@wordpress/ui';
import { close } from '@wordpress/icons';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import OnboardingModal from '~/settings-payments/onboarding/components/modal';
import { ThemeOptions } from './theme-options';
import { ResetNotice } from './reset-notice';
import type { PurplePrototypeSettings } from './settings';

const goHome = () => getHistory().push( getNewPath( {}, '/', {} ) );

/**
 * The CYS options in the same modal frame as WooPayments onboarding. Rendered over Home.
 */
export const ModalContainer = ( {
	settings,
}: {
	settings: PurplePrototypeSettings;
} ) => (
	<OnboardingModal setIsOpen={ goHome }>
		<div className="settings-payments-onboarding-modal__wrapper purple-cys-modal">
			<div className="settings-payments-onboarding-modal__sidebar purple-cys-modal__sidebar">
				<Text variant="heading-lg" render={ <h2 /> }>
					{ __( 'Customize your store', 'woocommerce' ) }
				</Text>
				<Text
					className="purple-cys-modal__sidebar-description"
					render={ <p /> }
				>
					{ __(
						'Choose the look your store starts with: Purple, the free theme built by WooCommerce, another theme from the Marketplace, or the theme you have now. You can change it any time.',
						'woocommerce'
					) }
				</Text>
			</div>
			<div className="settings-payments-onboarding-modal__content purple-cys-modal__panel">
				<div className="purple-cys-modal__header">
					<div className="purple-cys-modal__title">
						<Text variant="heading-lg" render={ <h1 /> }>
							{ __(
								'Choose how your store looks',
								'woocommerce'
							) }
						</Text>
						<Text
							className="purple-cys-modal__subtitle"
							render={ <p /> }
						>
							{ __(
								'Start with Purple, made by WooCommerce for stores, or browse themes from other developers.',
								'woocommerce'
							) }
						</Text>
					</div>
					<IconButton
						variant="minimal"
						tone="neutral"
						icon={ close }
						label={ __( 'Close', 'woocommerce' ) }
						onClick={ goHome }
					/>
				</div>
				<div className="purple-cys-modal__body">
					<ResetNotice />
					<ThemeOptions settings={ settings } />
				</div>
			</div>
		</div>
	</OnboardingModal>
);
