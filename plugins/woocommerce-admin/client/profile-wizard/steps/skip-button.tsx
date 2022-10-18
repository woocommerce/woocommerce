/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Popover } from '@wordpress/components';
import { Icon, info } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { ONBOARDING_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import UsageModal from './usage-modal';

const SkipButton: React.FC< {
	onSkipped?: () => void;
} > = ( { onSkipped } ) => {
	/* eslint-disable @wordpress/i18n-no-collapsible-whitespace */
	const skipSetupText = __(
		'Manual setup is only recommended for\n experienced WooCommerce users or developers.',
		'woocommerce'
	);

	const { createNotice } = useDispatch( 'core/notices' );
	const { invalidateResolutionForStoreSelector, updateProfileItems } =
		useDispatch( ONBOARDING_STORE_NAME );

	const trackingAllowed = useSelect(
		( select ) =>
			select( OPTIONS_STORE_NAME ).getOption(
				'woocommerce_allow_tracking'
			) === 'yes'
	);

	const [ isSkipSetupPopoverVisible, setSkipSetupPopoverVisibility ] =
		useState( false );

	const [ showUsageModal, setShowUsageModal ] = useState( false );

	const skipProfiler = () => {
		updateProfileItems( {
			skipped: true,
		} )
			.then( () => {
				if ( onSkipped ) {
					onSkipped();
				}
				getHistory().push( getNewPath( {}, '/', {} ) );
			} )
			.catch( () => {
				createNotice(
					'error',
					__(
						'There was a problem skipping the setup wizard',
						'woocommerce'
					)
				);
			} );
	};

	return (
		<>
			{ showUsageModal && (
				<UsageModal
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore -- ignoring it for now as UsageModal is not in ts yet.
					onContinue={ () => {
						skipProfiler();
					} }
					onClose={ () => {
						invalidateResolutionForStoreSelector( 'getTaskLists' );
						setShowUsageModal( false );
					} }
				/>
			) }
			<div className="woocommerce-profile-wizard__footer">
				<Button
					isLink
					className="woocommerce-profile-wizard__footer-link"
					onClick={ () => {
						if ( trackingAllowed ) {
							skipProfiler();
						} else {
							setShowUsageModal( true );
						}
					} }
				>
					{ __( 'Skip', 'woocommerce' ) }
				</Button>
				<Button
					isTertiary
					label={ skipSetupText }
					onClick={ () => {
						setSkipSetupPopoverVisibility( true );
					} }
				>
					<Icon icon={ info } />
				</Button>
				{ isSkipSetupPopoverVisible && (
					<Popover
						focusOnMount="container"
						position="top center"
						onClose={ () => setSkipSetupPopoverVisibility( false ) }
					>
						{ skipSetupText }
					</Popover>
				) }
			</div>
		</>
	);
};

export default SkipButton;
