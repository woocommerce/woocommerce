/**
 * External dependencies
 */
import { EllipsisMenu } from '@woocommerce/components';
import { PaymentsProvider } from '@woocommerce/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EllipsisMenuContent } from '~/settings-payments/components/ellipsis-menu-content';
import { isWooPayments } from '~/settings-payments/utils';
import { WooPaymentsResetAccountModal } from '~/settings-payments/components/modals';

interface EllipsisMenuProps {
	/**
	 * The label for the ellipsis menu.
	 */
	label: string;
	/**
	 * The payments provider associated with the menu.
	 */
	provider: PaymentsProvider;
}

/**
 * A wrapper component for rendering an ellipsis menu with dynamic content based on the provided payment provider.
 * This component determines whether the provider supports specific actions, such as resetting accounts and displays appropriate menu content.
 */
export const EllipsisMenuWrapper = ( {
	provider,
	label,
}: EllipsisMenuProps ) => {
	const [ resetAccountModalVisible, setResetAccountModalVisible ] =
		useState( false );

	// For WooPayments, we can reset a connected account if either:
	// - the account is a test-drive/sandbox account - this can be reset at any time.
	// - the account is a live account that has not completed onboarding.
	const canResetAccount =
		isWooPayments( provider.id ) &&
		provider._type === 'gateway' &&
		provider.state?.account_connected &&
		( provider.onboarding?.state?.test_mode ||
			! provider.onboarding?.state?.completed ) &&
		!! provider.onboarding?._links?.reset?.href;

	// For WooPayments, we can reset onboarding if there is no account connected but onboarding has been started.
	// This is an escape hatch for when the account is reset from the Transact Platform, but the onboarding state is not reset.
	// This is mutually exclusive with canResetAccount since resetting the account already includes resetting the onboarding.
	const canResetOnboarding =
		! canResetAccount &&
		isWooPayments( provider.id ) &&
		provider._type === 'gateway' &&
		! provider.state?.account_connected &&
		provider.onboarding?.state?.started &&
		!! provider.onboarding?._links?.reset?.href;

	return (
		<>
			<EllipsisMenu
				label={ label }
				renderContent={ ( { onToggle } ) => (
					<EllipsisMenuContent
						provider={ provider }
						pluginFile={ provider.plugin.file }
						isSuggestion={ provider._type === 'suggestion' }
						links={ provider.links }
						onToggle={ onToggle }
						isEnabled={ provider.state?.enabled }
						canResetAccount={ canResetAccount }
						setResetAccountModalVisible={
							setResetAccountModalVisible
						}
						canResetOnboarding={ canResetOnboarding }
					/>
				) }
				focusOnMount="firstElement"
			/>
			{ /* Modal for resetting WooPayments accounts */ }
			<WooPaymentsResetAccountModal
				isOpen={ resetAccountModalVisible }
				onClose={ () => setResetAccountModalVisible( false ) }
				hasAccount={ provider.state?.account_connected }
				isTestMode={ provider.onboarding?.state?.test_mode }
				resetUrl={ provider.onboarding?._links?.reset?.href }
			/>
		</>
	);
};
