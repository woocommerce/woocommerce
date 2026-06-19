/**
 * External dependencies
 */
import { Button, Modal, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import type {
	WooPaymentsOverviewRequirementError,
	WooPaymentsOverviewShell,
} from '../types';

const getRequirementLabel = ( error: WooPaymentsOverviewRequirementError ) =>
	error.reason ||
	error.code ||
	__( 'More information is required.', 'woocommerce' );

export const UpdateBusinessDetailsModal = ( {
	shell,
	onClose,
}: {
	shell: WooPaymentsOverviewShell;
	onClose: () => void;
} ) => {
	const accountLink = shell.account_status.account_link;
	const accountLinkWithSource = accountLink
		? addQueryArgs( accountLink, {
				from: 'WCPAY_OVERVIEW',
				source: 'wcpay-update-business-details-task',
		  } )
		: '';
	const errors = shell.account_status.requirements?.errors ?? [];

	const openAccountLink = () => {
		if ( accountLinkWithSource ) {
			window.open(
				accountLinkWithSource,
				'_blank',
				'noopener,noreferrer'
			);
		}
	};

	return (
		<Modal
			title={ __( 'Update business details', 'woocommerce' ) }
			className="woocommerce-woopayments-update-business-details-modal"
			onRequestClose={ onClose }
		>
			<p>
				{ __(
					'Some business details need attention before payments and payouts can continue without interruption.',
					'woocommerce'
				) }
			</p>
			{ errors.length > 0 && (
				<div className="woocommerce-woopayments-update-business-details-modal__errors">
					{ errors.map( ( error, index ) => (
						<Notice
							key={ `${ error.code || error.reason || index }` }
							status="warning"
							isDismissible={ false }
						>
							{ getRequirementLabel( error ) }
						</Notice>
					) ) }
				</div>
			) }
			<div className="woocommerce-woopayments-update-business-details-modal__footer">
				<Button variant="secondary" onClick={ onClose }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ openAccountLink }
					disabled={ ! accountLinkWithSource }
				>
					{ __( 'Update', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
