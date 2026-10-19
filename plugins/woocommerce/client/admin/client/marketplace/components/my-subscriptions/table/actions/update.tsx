/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useContext, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { SubscriptionsContext } from '../../../../contexts/subscriptions-context';
import { Subscription } from '../../types';
import ConnectModal from './connect-modal';
import RenewModal from './renew-modal';
import SubscribeModal from './subscribe-modal';
import {
	addNotice,
	getUpdateErrorNotice,
	getWpAjaxErrorData,
	PHP_REQUIREMENT_ERROR_CODE,
	removeNotice,
	updateProduct,
} from '../../../../utils/functions';
import { NoticeStatus } from '../../../../contexts/types';
import InstallWooConnectModal from '../../../woo-update-manager-plugin/install-woo-connect-modal';

interface UpdateProps {
	subscription: Subscription;
	wooUpdateManagerActive: boolean;
}

export default function Update( props: UpdateProps ) {
	const [ showModal, setShowModal ] = useState( false );
	const [ isUpdating, setIsUpdating ] = useState( false );
	const { loadSubscriptions } = useContext( SubscriptionsContext );

	const canUpdate =
		props.subscription.active &&
		props.subscription.local &&
		props.subscription.local.slug &&
		props.subscription.local.path &&
		props.wooUpdateManagerActive;

	function showUpdateError( error: unknown, onRetry: () => void ) {
		const { message, actions } = getUpdateErrorNotice(
			props.subscription.product_name,
			error,
			onRetry
		);
		addNotice(
			props.subscription.product_key,
			message,
			NoticeStatus.Error,
			{
				actions,
			}
		);
		setIsUpdating( false );

		recordEvent( 'marketplace_product_update_failed', {
			product_zip_slug: props.subscription.zip_slug,
			product_id: props.subscription.product_id,
			product_installed_version: props.subscription.local.version,
			product_current_version: props.subscription.version,
			error_code: getWpAjaxErrorData( error ).code || '',
		} );
	}

	function update() {
		recordEvent( 'marketplace_product_update_button_clicked', {
			product_zip_slug: props.subscription.zip_slug,
			product_id: props.subscription.product_id,
			product_installed_version: props.subscription.local.version,
			product_current_version: props.subscription.version,
		} );

		if ( ! canUpdate ) {
			setShowModal( true );
			return;
		}
		removeNotice( props.subscription.product_key );
		// Core refuses this update before downloading it, with no error code to explain why.
		if ( props.subscription.update_requires_php_met === false ) {
			showUpdateError(
				{
					success: false,
					data: { code: PHP_REQUIREMENT_ERROR_CODE },
				},
				update
			);
			return;
		}
		if ( ! window.wp.updates ) {
			addNotice(
				props.subscription.product_key,
				sprintf(
					// translators: %s is the product name.
					__( '%s couldn’t be updated.', 'woocommerce' ),
					props.subscription.product_name
				),
				NoticeStatus.Error,
				{
					actions: [
						{
							label: __(
								'Reload page and try again',
								'woocommerce'
							),
							onClick: () => {
								window.location.reload();
							},
						},
					],
				}
			);
			return;
		}

		setIsUpdating( true );

		updateProduct( props.subscription )
			.then( () => {
				void loadSubscriptions( false ).then( () => {
					addNotice(
						props.subscription.product_key,
						sprintf(
							// translators: %s is the product name.
							__( '%s updated successfully.', 'woocommerce' ),
							props.subscription.product_name
						),
						NoticeStatus.Success
					);
					setIsUpdating( false );
				} );

				recordEvent( 'marketplace_product_updated', {
					product_zip_slug: props.subscription.zip_slug,
					product_id: props.subscription.product_id,
					product_installed_version: props.subscription.local.version,
					product_current_version: props.subscription.version,
				} );
			} )
			.catch( ( error: unknown ) => showUpdateError( error, update ) );
	}

	const modal = () => {
		if ( ! showModal ) {
			return null;
		}

		if ( props.subscription.product_key === '' ) {
			return (
				<SubscribeModal
					onClose={ () => setShowModal( false ) }
					subscription={ props.subscription }
				/>
			);
		} else if ( props.subscription.expired ) {
			return (
				<RenewModal
					subscription={ props.subscription }
					onClose={ () => setShowModal( false ) }
				/>
			);
		} else if ( ! props.subscription.active ) {
			return (
				<ConnectModal
					subscription={ props.subscription }
					onClose={ () => setShowModal( false ) }
				/>
			);
		} else if ( ! props.wooUpdateManagerActive ) {
			return (
				<InstallWooConnectModal
					subscription={ props.subscription }
					onClose={ () => setShowModal( false ) }
				/>
			);
		}

		return null;
	};

	return (
		<>
			{ modal() }
			<Button
				variant="link"
				className="woocommerce-marketplace__my-subscriptions-update"
				onClick={ update }
				isBusy={ isUpdating }
				disabled={ isUpdating }
				label={ sprintf(
					// translators: %s is the product version.
					__( 'Update to %s', 'woocommerce' ),
					props.subscription.version
				) }
				showTooltip={ true }
				tooltipPosition="top center"
			>
				{ isUpdating
					? __( 'Updating', 'woocommerce' )
					: __( 'Update', 'woocommerce' ) }
			</Button>
		</>
	);
}
