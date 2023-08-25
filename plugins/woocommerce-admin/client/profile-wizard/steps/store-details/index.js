/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CheckboxControl,
	FlexItem as MaybeFlexItem,
	Spinner,
	Popover,
} from '@wordpress/components';
import { useState, useRef, useEffect, useContext } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { Form, TextControl } from '@woocommerce/components';
import {
	COUNTRIES_STORE_NAME,
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';
import { Icon, info } from '@wordpress/icons';
import { isEmail } from '@wordpress/url';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { getCountryCode, getCurrencyRegion } from '../../../dashboard/utils';
import {
	StoreAddress,
	getStoreAddressValidator,
} from '../../../dashboard/components/settings/general/store-address';
import UsageModal from '../usage-modal';
import { getAdminSetting } from '~/utils/admin-settings';
import './style.scss';

// FlexItem is not available until WP version 5.5. This code is safe to remove
// once the minimum WP supported version becomes 5.5.
const FlextItemSubstitute = ( { children, align } ) => {
	const style = {
		display: 'flex',
		'justify-content': align ? 'center' : 'flex-start',
	};
	return <div style={ style }>{ children }</div>;
};
const FlexItem = MaybeFlexItem || FlextItemSubstitute;

const LoadingPlaceholder = () => (
	<div className="woocommerce-admin__store-details__spinner">
		<Spinner />
	</div>
);

function StoreDetails( props ) {
	const [ showUsageModal, setShowUsageModal ] = useState( false );
	const [ skipping, setSkipping ] = useState( false );
	const [ isSkipSetupPopoverVisible, setIsSkipSetupPopoverVisible ] =
		useState( false );
	const changedFormValues = useRef( {} );
	const Currency = useContext( CurrencyContext );

	const { invalidateResolutionForStoreSelector, updateProfileItems } =
		useDispatch( ONBOARDING_STORE_NAME );
	const { updateAndPersistSettingsForGroup } =
		useDispatch( SETTINGS_STORE_NAME );
	const data = useSelect( ( select ) => {
		const { getSettings, getSettingsError, isUpdateSettingsRequesting } =
			select( SETTINGS_STORE_NAME );
		const {
			getProfileItems,
			isOnboardingRequesting,
			getEmailPrefill,
			hasFinishedResolution: hasFinishedResolutionOnboarding,
		} = select( ONBOARDING_STORE_NAME );
		const {
			getLocale,
			getLocales,
			getCountries,
			hasFinishedResolution: hasFinishedResolutionCountries,
		} = select( COUNTRIES_STORE_NAME );
		const { isResolving } = select( OPTIONS_STORE_NAME );

		const profileItems = getProfileItems();
		const emailPrefill = getEmailPrefill();

		const { general: settings = {} } = getSettings( 'general' );
		const isBusy =
			isOnboardingRequesting( 'updateProfileItems' ) ||
			isUpdateSettingsRequesting( 'general' ) ||
			isResolving( 'getOption', [ 'woocommerce_allow_tracking' ] );
		const isLoading =
			! hasFinishedResolutionOnboarding( 'getProfileItems' ) ||
			! hasFinishedResolutionOnboarding( 'getEmailPrefill' ) ||
			! hasFinishedResolutionCountries( 'getLocales' ) ||
			! hasFinishedResolutionCountries( 'getCountries' );
		const errorsRef = useRef( {
			settings: null,
		} );
		errorsRef.current = {
			settings: getSettingsError( 'general' ),
		};
		// Check if a store country is set so that we don't default
		// to WooCommerce's default country of the US:CA.
		const countryState = profileItems.is_store_country_set
			? settings.woocommerce_default_country
			: '';

		getCountries();
		getLocales();

		const initialValues = {
			addressLine1: settings.woocommerce_store_address || '',
			addressLine2: settings.woocommerce_store_address_2 || '',
			city: settings.woocommerce_store_city || '',
			countryState,
			postCode: settings.woocommerce_store_postcode || '',

			// By default, the marketing checkbox should be unticked by default to comply with WordPress.org plugin review guidelines.
			isAgreeMarketing:
				typeof profileItems.is_agree_marketing === 'boolean'
					? profileItems.is_agree_marketing
					: false,
			storeEmail:
				typeof profileItems.store_email === 'string'
					? profileItems.store_email
					: emailPrefill,
		};

		return {
			getLocale,
			initialValues,
			isLoading,
			profileItems,
			isBusy,
			settings,
			errorsRef,
		};
	} );

	function deriveCurrencySettings( countryState ) {
		if ( ! countryState ) {
			return null;
		}

		const country = getCountryCode( countryState );
		const { currencySymbols = {}, localeInfo = {} } = getAdminSetting(
			'onboarding',
			{}
		);
		return Currency.getDataForCountry(
			country,
			localeInfo,
			currencySymbols
		);
	}

	function onSubmit() {
		setShowUsageModal( true );
		setSkipping( false );
	}

	function onFormValueChange( changedFormValue ) {
		changedFormValues.current[ changedFormValue.name ] =
			changedFormValue.value;
	}

	async function onContinue( values ) {
		const { profileItems, settings, errorsRef } = data;

		const currencySettings = deriveCurrencySettings( values.countryState );
		Currency.setCurrency( currencySettings );

		recordEvent( 'storeprofiler_store_details_continue', {
			store_country: getCountryCode( values.countryState ),
			derived_currency: currencySettings.code,
			email_signup: values.isAgreeMarketing,
		} );

		await updateAndPersistSettingsForGroup( 'general', {
			general: {
				...settings,
				woocommerce_store_address: values.addressLine1,
				woocommerce_store_address_2: values.addressLine2,
				woocommerce_default_country: values.countryState,
				woocommerce_store_city: values.city,
				woocommerce_store_postcode: values.postCode,
				woocommerce_currency: currencySettings.code,
				woocommerce_currency_pos: currencySettings.symbolPosition,
				woocommerce_price_thousand_sep:
					currencySettings.thousandSeparator,
				woocommerce_price_decimal_sep:
					currencySettings.decimalSeparator,
				woocommerce_price_num_decimals: currencySettings.precision,
			},
		} );

		const profileItemsToUpdate = {
			is_agree_marketing: values.isAgreeMarketing,
			store_email: values.storeEmail,
			is_store_country_set:
				typeof values.countryState === 'string' &&
				values.countryState !== '',
		};

		const region = getCurrencyRegion( values.countryState );

		/**
		 * If a user has already selected cdb industry and returns to change to a
		 * non US store, remove cbd industry.
		 *
		 * NOTE: the following call to `updateProfileItems` does not respect the
		 * `await` and performs an update aysnchronously. This means the following
		 * screen may not be initialized with correct profile settings.
		 *
		 * This comment may be removed when a refactor to wp.data datastores is complete.
		 */
		if (
			region !== 'US' &&
			profileItems.industry &&
			profileItems.industry.length
		) {
			const cbdSlug = 'cbd-other-hemp-derived-products';
			const trimmedIndustries = profileItems.industry.filter(
				( industry ) => {
					return cbdSlug !== industry && cbdSlug !== industry.slug;
				}
			);
			profileItemsToUpdate.industry = trimmedIndustries;
		}

		let errorMessages = [];
		try {
			await updateProfileItems( profileItemsToUpdate );
		} catch ( error ) {
			// Array of error messages obtained from API response.
			if ( error?.data?.params ) {
				errorMessages = Object.values( error.data.params );
			}
		}

		if (
			! Boolean( errorsRef.current.settings ) &&
			! errorMessages.length
		) {
			return true;
		}
		createNotice(
			'error',
			__( 'There was a problem saving your store details', 'woocommerce' )
		);

		errorMessages.forEach( ( message ) =>
			createNotice( 'error', message )
		);
	}

	useEffect( () => {
		if (
			data.isLoading === false &&
			Object.keys( changedFormValues.current ).length === 0
		) {
			// Make a copy of the initialValues.
			// The values in this object gets updated on onFormValueChange.
			changedFormValues.current = { ...data.initialValues };
			props.trackStepValueChanges(
				props.step.key,
				data.initialValues,
				changedFormValues.current,
				() => {
					onContinue( changedFormValues.current );
				}
			);
		}
	}, [ data.isLoading, data.initialValues ] );

	function validateStoreDetails( values ) {
		const locale = data.getLocale( values.countryState );
		const validateAddress = getStoreAddressValidator( locale );
		const errors = validateAddress( values );

		if ( values.storeEmail && ! isEmail( values.storeEmail ) ) {
			errors.storeEmail = __( 'Invalid email address', 'woocommerce' );
		}

		if (
			values.isAgreeMarketing &&
			( ! values.storeEmail || ! values.storeEmail.trim().length )
		) {
			errors.storeEmail = __(
				'Please enter your email address to subscribe',
				'woocommerce'
			);
		}

		return errors;
	}

	const { skipProfiler } = props;

	/* eslint-disable @wordpress/i18n-no-collapsible-whitespace */
	const skipSetupText = __(
		'Manual setup is only recommended for\n experienced WooCommerce users or developers.',
		'woocommerce'
	);
	/* eslint-enable @wordpress/i18n-no-collapsible-whitespace */

	if ( data.isLoading ) {
		return (
			<div className="woocommerce-profile-wizard__store-details">
				<LoadingPlaceholder />
			</div>
		);
	}
	return (
		<div className="woocommerce-profile-wizard__store-details">
			<div className="woocommerce-profile-wizard__step-header">
				<Text variant="title.small" as="h2" size="20" lineHeight="28px">
					{ __( 'Welcome to WooCommerce', 'woocommerce' ) }
				</Text>
				<Text variant="body" as="p">
					{ __(
						'Tell us where you run your business to help us configure currency, shipping, taxes, and more in a fully automated way.',
						'woocommerce'
					) }
				</Text>
			</div>

			<Form
				initialValues={ data.initialValues }
				onSubmit={ onSubmit }
				validate={ validateStoreDetails }
				onChange={ onFormValueChange }
			>
				{ ( {
					getInputProps,
					handleSubmit,
					values,
					isValidForm,
					setValue,
				} ) => (
					<Card>
						{ showUsageModal && (
							<UsageModal
								onContinue={ () => {
									if ( skipping ) {
										skipProfiler();
									} else {
										onContinue( values ).then( () =>
											props.goToNextStep()
										);
									}
								} }
								onClose={ () => {
									setShowUsageModal( false );
									setSkipping( false );
								} }
							/>
						) }
						<CardBody>
							<StoreAddress
								getInputProps={ getInputProps }
								setValue={ setValue }
							/>

							<TextControl
								label={
									values.isAgreeMarketing
										? __( 'Email address', 'woocommerce' )
										: __( 'Email address', 'woocommerce' )
								}
								required={ values.isAgreeMarketing }
								autoComplete="email"
								{ ...getInputProps( 'storeEmail' ) }
							/>
							<FlexItem>
								<div className="woocommerce-profile-wizard__newsletter-signup">
									<CheckboxControl
										label={
											<>
												{ __(
													'Get tips, product updates and inspiration straight to your mailbox.',
													'woocommerce'
												) }{ ' ' }
												<span className="woocommerce-profile-wizard__powered-by-mailchimp">
													{ __(
														'Powered by Mailchimp',
														'woocommerce'
													) }
												</span>
											</>
										}
										{ ...getInputProps(
											'isAgreeMarketing'
										) }
									/>
								</div>
							</FlexItem>
						</CardBody>
						<CardFooter justify="center">
							<Button
								isPrimary
								onClick={ handleSubmit }
								isBusy={ data.isBusy }
								disabled={ ! isValidForm || data.isBusy }
								aria-disabled={ ! isValidForm || data.isBusy }
							>
								{ __( 'Continue', 'woocommerce' ) }
							</Button>
						</CardFooter>
					</Card>
				) }
			</Form>
			<div className="woocommerce-profile-wizard__footer">
				<Button
					isLink
					className="woocommerce-profile-wizard__footer-link"
					onClick={ () => {
						invalidateResolutionForStoreSelector( 'getTaskLists' );
						setShowUsageModal( true );
						setSkipping( true );
						return false;
					} }
				>
					{ __( 'Skip setup store details', 'woocommerce' ) }
				</Button>
				<Button
					isTertiary
					label={ skipSetupText }
					onClick={ () => setIsSkipSetupPopoverVisible( true ) }
				>
					<Icon icon={ info } />
				</Button>
				{ isSkipSetupPopoverVisible && (
					<Popover
						focusOnMount="container"
						position="top center"
						onClose={ () => setIsSkipSetupPopoverVisible( false ) }
					>
						{ skipSetupText }
					</Popover>
				) }
			</div>
		</div>
	);
}

export { StoreDetails };
