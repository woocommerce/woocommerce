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

	function update() {
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
		window.wp.updates
			.ajax(
				props.subscription.local.type === 'plugin'
					? 'update-plugin'
					: 'update-theme',
				{
					slug: props.subscription.local.slug,
					plugin: props.subscription.local.path,
					theme: props.subscription.local.path,
				}
			)
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

	return (
		<Button
			type="link"
			className="woocommerce-marketplace__my-subscriptions-update"
			onClick={ update }
			isBusy={ isUpdating }
		>
			{ isUpdating
				? __( 'Updating', 'woocommerce' )
				: __( 'Update', 'woocommerce' ) }
		</Button>
	);
}
