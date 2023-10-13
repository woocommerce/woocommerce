/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useContext, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Subscription } from '../../types';
import { SubscriptionsContext } from '../../../../contexts/subscriptions-context';

interface UpdateProps {
	subscription: Subscription;
}

export default function Update( props: UpdateProps ) {
	const [ isUpdating, setIsUpdating ] = useState( false );
	const { createWarningNotice, createSuccessNotice } =
		useDispatch( 'core/notices' );
	const { loadSubscriptions } = useContext( SubscriptionsContext );

	const canUpdate = props.subscription.active && props.subscription.local;

	function update() {
		if ( ! canUpdate ) {
			return;
		}
		if ( ! window.wp.updates ) {
			createWarningNotice(
				sprintf(
					// translators: %s is the product name.
					__( '%s couldn’t be updated.', 'woocommerce' ),
					props.subscription.product_name
				),
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

		const action =
			props.subscription.local.type === 'plugin'
				? 'update-plugin'
				: 'update-theme';
		window.wp.updates
			.ajax( action, {
				slug: props.subscription.local.slug,
				plugin: props.subscription.local.path,
				theme: props.subscription.local.path,
			} )
			.then( () => {
				loadSubscriptions( false );
				createSuccessNotice(
					sprintf(
						// translators: %s is the product name.
						__( '%s updated successfully.', 'woocommerce' ),
						props.subscription.product_name
					),
					{
						icon: <Icon icon="yes" />,
					}
				);
			} )
			.catch( () => {
				createWarningNotice(
					sprintf(
						// translators: %s is the product name.
						__( '%s couldn’t be updated.', 'woocommerce' ),
						props.subscription.product_name
					),
					{
						actions: [
							{
								label: __( 'Try again', 'woocommerce' ),
								onClick: update,
							},
						],
					}
				);
			} )
			.always( () => {
				setIsUpdating( false );
			} );
	}

	const buttonLabel = canUpdate
		? sprintf(
				// translators: %s is the product version.
				__( 'Update to %s', 'woocommerce' ),
				props.subscription.version
		  )
		: //TODO show renew/connect popup instead of this
		  __( 'You need an active subscription to update.', 'woocommerce' );

	return (
		<Button
			type="link"
			className="woocommerce-marketplace__my-subscriptions-update"
			onClick={ update }
			isBusy={ isUpdating }
			disabled={ isUpdating }
			label={ buttonLabel }
			showTooltip={ true }
			tooltipPosition="top center"
		>
			{ isUpdating
				? __( 'Updating', 'woocommerce' )
				: __( 'Update', 'woocommerce' ) }
		</Button>
	);
}
