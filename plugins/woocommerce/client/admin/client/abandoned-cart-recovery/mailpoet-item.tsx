/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Pill } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch } from '@wordpress/data';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ProductIcon } from '~/marketing/components';

const MAILPOET_SLUG = 'mailpoet';

type MailPoetItemProps = {
	pluginsBeingSetup: ReadonlyArray< string >;
	onSetupClick: ( slugs: string[] ) => Promise< void >;
};

const MailPoetItem = ( {
	pluginsBeingSetup,
	onSetupClick,
}: MailPoetItemProps ) => {
	const { createSuccessNotice } = useDispatch( 'core/notices' );

	const handleSetupClick = () => {
		recordEvent( 'abandoned_cart_recovery_recommendation_click', {
			plugin: MAILPOET_SLUG,
		} );

		onSetupClick( [ MAILPOET_SLUG ] )
			.then( () => {
				createSuccessNotice(
					__( '🎉 MailPoet is installed!', 'woocommerce' ),
					{
						actions: [
							{
								url: getAdminLink(
									'admin.php?page=mailpoet-newsletters'
								),
								label: __( 'Set up MailPoet', 'woocommerce' ),
							},
						],
					}
				);
			} )
			.catch( () => {
				// Error notice handled by createNoticesFromResponse in the install hook.
			} );
	};

	return (
		<div className="woocommerce-list__item-inner woocommerce-abandoned-cart-recovery-recommendation-item">
			<div className="woocommerce-list__item-before">
				<ProductIcon product="mailpoet" />
			</div>
			<div className="woocommerce-list__item-text">
				<span className="woocommerce-list__item-title">
					{ __( 'MailPoet', 'woocommerce' ) }
					<Pill>{ __( 'Recommended', 'woocommerce' ) }</Pill>
				</span>
				<span className="woocommerce-list__item-content">
					{ __(
						'Send newsletters and automated welcome series from your WooCommerce dashboard. Free and installs in one click.',
						'woocommerce'
					) }
				</span>
			</div>
			<div className="woocommerce-list__item-after">
				<Button
					variant="secondary"
					onClick={ handleSetupClick }
					isBusy={ pluginsBeingSetup.includes( MAILPOET_SLUG ) }
					disabled={ pluginsBeingSetup.length > 0 }
				>
					{ __( 'Get started', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default MailPoetItem;
