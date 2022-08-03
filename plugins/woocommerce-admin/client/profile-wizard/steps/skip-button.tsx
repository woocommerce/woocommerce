/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Popover } from '@wordpress/components';
import { Icon, info } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';

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
						setTimeout( () => skipProfiler(), 0 );
					} }
					onClose={ () => {
						invalidateResolutionForStoreSelector( 'getTaskLists' );
						setTimeout( () => setShowUsageModal( false ), 0 );
					} }
				/>
			) }
			<div className="woocommerce-profile-wizard__footer">
				<Button
					isLink
					className="woocommerce-profile-wizard__footer-link"
					onClick={ () => {
						setShowUsageModal( true );
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
