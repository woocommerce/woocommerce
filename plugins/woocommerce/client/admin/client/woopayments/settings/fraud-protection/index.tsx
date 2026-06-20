/**
 * External dependencies
 */
import { Button, Modal, Notice } from '@wordpress/components';
import { lazy, Suspense, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { help } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';
import type { MouseEvent } from 'react';

/**
 * Internal dependencies
 */
import { getSettingsPaymentsProviderRouteUrl } from '../../admin/utils';
import {
	useAdvancedFraudProtectionSettings,
	useCurrentProtectionLevel,
	useGetSettings,
} from '../data/hooks';
import './style.scss';

const ProtectionLevel = {
	BASIC: 'basic',
	ADVANCED: 'advanced',
} as const;

type SettingsRecord = Record< string, unknown >;
type StringSetting = [ string, ( value: string ) => void ];
type AdvancedFraudProtectionSetting = [ unknown, ( value: unknown[] ) => void ];

const asSettingsRecord = ( value: unknown ): SettingsRecord =>
	value && typeof value === 'object' ? ( value as SettingsRecord ) : {};

const FraudProtectionTour = lazy( () =>
	import(
		/* webpackChunkName: "settings-payments-woopayments-fraud-tour" */ './tour'
	).then( ( module ) => ( { default: module.FraudProtectionTour } ) )
);

const getNestedBooleanOrUndefined = (
	record: SettingsRecord,
	keys: string[]
): boolean | undefined => {
	let current: unknown = record;

	for ( const key of keys ) {
		if ( ! current || typeof current !== 'object' ) {
			return undefined;
		}

		current = ( current as SettingsRecord )[ key ];
	}

	return typeof current === 'boolean' ? current : undefined;
};

const getFraudProtectionFlags = ( settings: SettingsRecord ) => {
	const fraudProtection = asSettingsRecord( settings.fraud_protection );
	const accountStatus = asSettingsRecord( settings.account_status );
	const accountFraudProtection = asSettingsRecord(
		asSettingsRecord( accountStatus.fraudProtection )
	);
	const nativeAvsFailureDecline = getNestedBooleanOrUndefined(
		fraudProtection,
		[ 'decline_on_avs_failure' ]
	);
	const legacyAvsFailureDecline = getNestedBooleanOrUndefined(
		accountFraudProtection,
		[ 'declineOnAVSFailure' ]
	);
	const nativeCvcFailureDecline = getNestedBooleanOrUndefined(
		fraudProtection,
		[ 'decline_on_cvc_failure' ]
	);
	const legacyCvcFailureDecline = getNestedBooleanOrUndefined(
		accountFraudProtection,
		[ 'declineOnCVCFailure' ]
	);

	return {
		declineOnAVSFailure:
			nativeAvsFailureDecline ?? legacyAvsFailureDecline ?? true,
		declineOnCVCFailure:
			nativeCvcFailureDecline ?? legacyCvcFailureDecline ?? true,
	};
};

const normalizeProtectionLevel = ( level: string ) =>
	level === ProtectionLevel.ADVANCED
		? ProtectionLevel.ADVANCED
		: ProtectionLevel.BASIC;

const isWelcomeTourDismissed = ( settings: SettingsRecord ) =>
	asSettingsRecord( settings.fraud_protection ).is_welcome_tour_dismissed ===
	true;

const BasicFraudProtectionModal = ( {
	onClose,
	settings,
}: {
	onClose: () => void;
	settings: SettingsRecord;
} ) => {
	const { declineOnAVSFailure, declineOnCVCFailure } =
		getFraudProtectionFlags( settings );
	const hasActivePlatformChecks = declineOnAVSFailure || declineOnCVCFailure;

	return (
		<Modal
			title={ __( 'Basic filter level', 'woocommerce' ) }
			onRequestClose={ onClose }
			shouldCloseOnClickOutside
			shouldCloseOnEsc
			className="woopayments-fraud-protection-modal"
		>
			<div className="woopayments-fraud-protection-modal__body">
				<Notice status="info" isDismissible={ false }>
					{ __(
						'Provides basic anti-fraud protection only.',
						'woocommerce'
					) }
				</Notice>
				{ hasActivePlatformChecks && (
					<>
						<p>
							{ __(
								'Payments will be blocked if:',
								'woocommerce'
							) }
						</p>
						<ul>
							{ declineOnAVSFailure && (
								<li>
									{ __(
										'The billing address does not match what is on file with the card issuer.',
										'woocommerce'
									) }
								</li>
							) }
							{ declineOnCVCFailure && (
								<li>
									{ __(
										"The card's issuing bank cannot verify the CVV.",
										'woocommerce'
									) }
								</li>
							) }
						</ul>
					</>
				) }
				<Button variant="secondary" onClick={ onClose }>
					{ __( 'Got it', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};

export const FraudProtectionSettings = () => {
	const [ isBasicModalOpen, setBasicModalOpen ] = useState( false );
	const [ protectionLevel, setProtectionLevel ] =
		useCurrentProtectionLevel() as StringSetting;
	const [ advancedFraudProtectionSettings ] =
		useAdvancedFraudProtectionSettings() as AdvancedFraudProtectionSetting;
	const settings = asSettingsRecord( useGetSettings() );
	const hasFraudProtectionSettingsError =
		advancedFraudProtectionSettings === 'error';
	const shouldLoadFraudProtectionTour =
		! hasFraudProtectionSettingsError &&
		! isWelcomeTourDismissed( settings ) &&
		typeof window.IntersectionObserver !== 'undefined';
	const normalizedProtectionLevel =
		normalizeProtectionLevel( protectionLevel );
	const advancedSettingsUrl = getSettingsPaymentsProviderRouteUrl(
		'/woopayments/settings/fraud-protection?from=woopayments-settings'
	);
	const isAdvancedSettingsConfigured =
		Array.isArray( advancedFraudProtectionSettings ) &&
		advancedFraudProtectionSettings.length > 0;
	const isAdvancedSelected =
		normalizedProtectionLevel === ProtectionLevel.ADVANCED;

	const onLevelChange = ( level: string ) => () => {
		recordEvent( 'wcpay_fraud_protection_risk_level_preset_enabled', {
			preset: level,
		} );
		setProtectionLevel( level );
	};

	const onConfigureAdvanced = (
		event: MouseEvent< HTMLAnchorElement | HTMLButtonElement >
	) => {
		if ( ! isAdvancedSelected || hasFraudProtectionSettingsError ) {
			event.preventDefault();
		}
	};

	return (
		<div className="woopayments-fraud-protection">
			<h3>{ __( 'Set your payment risk level', 'woocommerce' ) }</h3>
			{ hasFraudProtectionSettingsError && (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'There was an error retrieving your fraud protection settings. Please refresh the page to try again.',
						'woocommerce'
					) }
				</Notice>
			) }
			<fieldset
				id="fraud-protection-card-options"
				disabled={ hasFraudProtectionSettingsError }
				className="woopayments-fraud-protection-levels"
			>
				<legend className="screen-reader-text">
					{ __( 'Fraud protection level', 'woocommerce' ) }
				</legend>
				<div className="woopayments-fraud-protection-levels__option">
					<div className="woopayments-fraud-protection-levels__header">
						<input
							id="woopayments-fraud-protection-basic"
							name="woopayments-fraud-protection-level"
							type="radio"
							value={ ProtectionLevel.BASIC }
							checked={
								normalizedProtectionLevel ===
								ProtectionLevel.BASIC
							}
							onChange={ onLevelChange( ProtectionLevel.BASIC ) }
						/>
						<label htmlFor="woopayments-fraud-protection-basic">
							{ __( 'Basic', 'woocommerce' ) }
						</label>
						<Button
							icon={ help }
							label={ __(
								'Basic level help icon',
								'woocommerce'
							) }
							variant="tertiary"
							aria-haspopup="dialog"
							aria-expanded={ isBasicModalOpen }
							onClick={ () => {
								recordEvent(
									'wcpay_fraud_protection_basic_modal_viewed'
								);
								setBasicModalOpen( true );
							} }
						/>
					</div>
					<p className="woopayments-fraud-protection-levels__copy">
						{ __(
							'Provides the base level of platform protection.',
							'woocommerce'
						) }
					</p>
				</div>
				<div className="woopayments-fraud-protection-levels__option woopayments-fraud-protection-levels__option--advanced">
					<div className="woopayments-fraud-protection-levels__advanced-content">
						<div className="woopayments-fraud-protection-levels__header">
							<input
								id="woopayments-fraud-protection-advanced"
								name="woopayments-fraud-protection-level"
								type="radio"
								value={ ProtectionLevel.ADVANCED }
								checked={ isAdvancedSelected }
								onChange={ onLevelChange(
									ProtectionLevel.ADVANCED
								) }
							/>
							<label htmlFor="woopayments-fraud-protection-advanced">
								{ __( 'Advanced', 'woocommerce' ) }
							</label>
						</div>
						<p className="woopayments-fraud-protection-levels__copy">
							{ __(
								'Allows you to fine-tune the level of filtering according to your business needs.',
								'woocommerce'
							) }
						</p>
					</div>
					<Button
						variant="secondary"
						href={
							isAdvancedSelected &&
							! hasFraudProtectionSettingsError
								? advancedSettingsUrl
								: undefined
						}
						disabled={
							! isAdvancedSelected ||
							hasFraudProtectionSettingsError
						}
						onClick={ onConfigureAdvanced }
					>
						{ isAdvancedSettingsConfigured
							? __( 'Edit', 'woocommerce' )
							: __( 'Configure', 'woocommerce' ) }
					</Button>
				</div>
			</fieldset>
			{ isBasicModalOpen && (
				<BasicFraudProtectionModal
					onClose={ () => setBasicModalOpen( false ) }
					settings={ settings }
				/>
			) }
			{ shouldLoadFraudProtectionTour && (
				<Suspense fallback={ null }>
					<FraudProtectionTour />
				</Suspense>
			) }
		</div>
	);
};

export default FraudProtectionSettings;
