/**
 * External dependencies
 */
import classnames from 'classnames';
import { Fragment } from '@wordpress/element';
import { CardBody, CardMedia, CardDivider } from '@wordpress/components';
import { SetupRequired } from '@woocommerce/onboarding';
import { Pill } from '@woocommerce/components';
import { Text, useSlot } from '@woocommerce/experimental';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Action } from '../Action';
import './List.scss';
import fallbackImage from './fallback.png';

export const Item = ( { isRecommended, markConfigured, paymentGateway } ) => {
	const {
		image_72x72: image72x72,
		content,
		id,
		plugins = [],
		title,
		loading,
		enabled: isEnabled = false,
		installed: isInstalled = false,
		needsSetup = true,
		requiredSettings,
		settingsUrl: manageUrl,
		is_local_partner: isLocalPartner,
		external_link: externalLink,
	} = paymentGateway;

	const connectSlot = useSlot(
		`woocommerce_payment_gateway_configure_${ id }`
	);
	const setupSlot = useSlot( `woocommerce_payment_gateway_setup_${ id }` );

	const hasFills =
		Boolean( connectSlot?.fills?.length ) ||
		Boolean( setupSlot?.fills?.length );

	const hasSetup = Boolean(
		plugins.length || requiredSettings.length || hasFills || externalLink
	);
	const showRecommended = isRecommended && needsSetup;

	const classes = classnames(
		'woocommerce-task-payment',
		'woocommerce-task-card',
		needsSetup && 'woocommerce-task-payment-not-configured',
		'woocommerce-task-payment-' + id
	);

	return (
		<Fragment key={ id }>
			<CardBody
				style={ { paddingLeft: 0, marginBottom: 0 } }
				className={ classes }
			>
				<CardMedia isBorderless>
					<img
						src={ image72x72 }
						alt={ title }
						onError={ ( e ) =>
							( e.currentTarget.src = fallbackImage )
						}
					/>
				</CardMedia>
				<div className="woocommerce-task-payment__description">
					<Text as="h3" className="woocommerce-task-payment__title">
						<span>{ title }</span>
						{ showRecommended && (
							<Pill
								className={ ! isLocalPartner && 'pill-green' }
							>
								{ isLocalPartner
									? __( 'Local Partner', 'woocommerce' )
									: __( 'Recommended', 'woocommerce' ) }
							</Pill>
						) }
						{ isInstalled && needsSetup && !! plugins.length && (
							<SetupRequired />
						) }
					</Text>
					<div className="woocommerce-task-payment__content">
						{ content }
					</div>
				</div>
				<div className="woocommerce-task-payment__footer">
					<Action
						manageUrl={ manageUrl }
						id={ id }
						hasSetup={ hasSetup }
						needsSetup={ needsSetup }
						isEnabled={ isEnabled }
						isInstalled={ isInstalled }
						hasPlugins={ Boolean( plugins.length ) }
						isRecommended={ isRecommended }
						isLoading={ loading }
						markConfigured={ markConfigured }
						externalLink={ externalLink }
					/>
				</div>
			</CardBody>
			<CardDivider />
		</Fragment>
	);
};
