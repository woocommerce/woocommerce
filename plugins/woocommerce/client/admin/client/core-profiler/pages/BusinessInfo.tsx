/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	TextControl,
	Spinner,
	CheckboxControl,
	Notice,
} from '@wordpress/components';
import { FormInputValidation } from '@automattic/components';
import { SelectControl } from '@woocommerce/components';
import { Icon, chevronDown } from '@wordpress/icons';
import {
	useEffect,
	useState,
	createInterpolateElement,
} from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { z } from 'zod';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '../index';
import { BusinessInfoEvent } from '../events';
import { CountryStateOption } from '../services/country';
import { Heading } from '../components/heading/heading';
import { Navigation } from '../components/navigation/navigation';
import { GeolocationCountrySelect } from '../components/geolocation-country-select/geolocation-country-select';

/** These are some store names that are known to be set by default and not likely to be used as actual names */
export const POSSIBLY_DEFAULT_STORE_NAMES = [
	undefined,
	'woocommerce',
	'Site Title',
	'',
];
export type IndustryChoice = ( typeof industryChoices )[ number ][ 'key' ];
export const industryChoices = [
	{
		label: __( 'Clothing and accessories', 'woocommerce' ),
		key: 'clothing_and_accessories' as const,
	},
	{
		label: __( 'Food and drink', 'woocommerce' ),
		key: 'food_and_drink' as const,
	},
	{
		label: __( 'Electronics and computers', 'woocommerce' ),
		key: 'electronics_and_computers' as const,
	},
	{
		label: __( 'Health and beauty', 'woocommerce' ),
		key: 'health_and_beauty' as const,
	},
	{
		label: __( 'Education and learning', 'woocommerce' ),
		key: 'education_and_learning' as const,
	},
	{
		label: __( 'Home, furniture and garden', 'woocommerce' ),
		key: 'home_furniture_and_garden' as const,
	},
	{
		label: __( 'Arts and crafts', 'woocommerce' ),
		key: 'arts_and_crafts' as const,
	},
	{
		label: __( 'Sports and recreation', 'woocommerce' ),
		key: 'sports_and_recreation' as const,
	},
	{
		label: __( 'Other', 'woocommerce' ),
		key: 'other' as const,
	},
];

export type IndustryChoiceOption = ( typeof industryChoices )[ number ];

export const selectIndustryMapping = {
	im_just_starting_my_business: __(
		'What type of products or services do you plan to sell?',
		'woocommerce'
	),
	im_already_selling: __(
		'Which industry is your business in?',
		'woocommerce'
	),
	im_setting_up_a_store_for_a_client: __(
		'Which industry is your client’s business in?',
		'woocommerce'
	),
};

export type BusinessInfoContextProps = Pick<
	CoreProfilerStateMachineContext,
	'geolocatedLocation' | 'userProfile' | 'businessInfo' | 'countries'
> & {
	onboardingProfile: Pick<
		CoreProfilerStateMachineContext[ 'onboardingProfile' ],
		| 'industry'
		| 'business_choice'
		| 'is_store_country_set'
		| 'is_agree_marketing'
		| 'store_email'
	>;
} & Partial< Pick< CoreProfilerStateMachineContext, 'currentUserEmail' > >;

export const BusinessInfo = ( {
	context,
	navigationProgress,
	sendEvent,
}: {
	context: BusinessInfoContextProps;
	navigationProgress: number;
	sendEvent: ( event: BusinessInfoEvent ) => void;
} ) => {
	const {
		geolocatedLocation,
		userProfile: { businessChoice },
		businessInfo,
		countries,
		onboardingProfile: {
			is_store_country_set: isStoreCountrySet = false,
			industry: industryFromOnboardingProfile = [],
			business_choice: businessChoiceFromOnboardingProfile = '',
			is_agree_marketing: isOptInMarketingFromOnboardingProfile = false,
			store_email: storeEmailAddressFromOnboardingProfile = '',
		} = {},
		currentUserEmail,
	} = context;

	const [ storeName, setStoreName ] = useState(
		businessInfo.storeName || ''
	);

	const [ storeCountry, setStoreCountry ] = useState< CountryStateOption >( {
		key: '',
		label: '',
	} );

	useEffect( () => {
		if ( isStoreCountrySet ) {
			const previouslyStoredCountryOption = countries.find(
				( country ) => country.key === businessInfo.location
			);
			setStoreCountry(
				previouslyStoredCountryOption || { key: '', label: '' }
			);
		}
	}, [ businessInfo.location, countries, isStoreCountrySet ] );

	const [ industry, setIndustry ] = useState<
		IndustryChoiceOption | undefined
	>(
		industryFromOnboardingProfile
			? industryChoices.find(
					( choice ) =>
						choice.key === industryFromOnboardingProfile[ 0 ]
			  )
			: undefined
	);

	const selectCountryLabel = __( 'Select country/region', 'woocommerce' );
	const selectIndustryQuestionLabel =
		selectIndustryMapping[
			businessChoice ||
				businessChoiceFromOnboardingProfile ||
				'im_just_starting_my_business'
		];

	const [ hasSubmitted, setHasSubmitted ] = useState( false );

	const [ isEmailInvalid, setIsEmailInvalid ] = useState( false );

	const [ storeEmailAddress, setEmailAddress ] = useState(
		storeEmailAddressFromOnboardingProfile || currentUserEmail || ''
	);

	const [ isOptInMarketing, setIsOptInMarketing ] = useState< boolean >(
		isOptInMarketingFromOnboardingProfile || false
	);

	const [ doValidate, setDoValidate ] = useState( false );
	const [ geolocationOverruled, setGeolocationOverruled ] = useState( false );

	useEffect( () => {
		if ( doValidate ) {
			const parseEmail = z
				.string()
				.email()
				.safeParse( storeEmailAddress );
			setIsEmailInvalid( isOptInMarketing && ! parseEmail.success );
			setDoValidate( false );
		}
	}, [ isOptInMarketing, doValidate, storeEmailAddress ] );

	return (
		<div
			className="woocommerce-profiler-business-information"
			data-testid="core-profiler-business-information"
		>
			<Navigation percentage={ navigationProgress } />
			<div className="woocommerce-profiler-page__content woocommerce-profiler-business-information__content">
				<Heading
					className="woocommerce-profiler__stepper-heading"
					title={ __(
						'Tell us a bit about your store',
						'woocommerce'
					) }
					subTitle={ __(
						'We’ll use this information to help you set up payments, shipping, and taxes, as well as recommending the best theme for your store.',
						'woocommerce'
					) }
				/>

				<form
					className="woocommerce-profiler-business-information-form"
					autoComplete="off"
				>
					<TextControl
						__nextHasNoMarginBottom
						className="woocommerce-profiler-business-info-store-name"
						onChange={ ( value ) => {
							setStoreName( value );
						} }
						value={ decodeEntities( storeName ) }
						label={
							<>
								{ __(
									'Give your store a name',
									'woocommerce'
								) }
							</>
						}
						placeholder={ __(
							'Ex. My awesome store',
							'woocommerce'
						) }
					/>
					<p className="woocommerce-profiler-question-subtext">
						{ __(
							'Don’t worry — you can always change it later!',
							'woocommerce'
						) }
					</p>
					<p className="woocommerce-profiler-question-label">
						{ selectIndustryQuestionLabel }
					</p>
					<SelectControl
						className="woocommerce-profiler-select-control__industry"
						instanceId={ 1 }
						placeholder={ __(
							'Select an industry',
							'woocommerce'
						) }
						label={ __( 'Select an industry', 'woocommerce' ) }
						options={ industryChoices }
						excludeSelectedOptions={ false }
						help={ <Icon icon={ chevronDown } /> }
						onChange={ ( results ) => {
							if ( Array.isArray( results ) && results.length ) {
								setIndustry(
									results[ 0 ] as IndustryChoiceOption
								);
							}
						} }
						selected={ industry ? [ industry ] : [] }
						showAllOnFocus
						isSearchable
					/>
					<p className="woocommerce-profiler-question-label">
						{ __( 'Where is your store located?', 'woocommerce' ) }
						<span className="woocommerce-profiler-question-required">
							{ '*' }
						</span>
					</p>
					<GeolocationCountrySelect
						label={ selectCountryLabel }
						placeholder={ selectCountryLabel }
						countries={ countries }
						initialValue={ storeCountry }
						onChange={ ( countryStateOption ) => {
							setStoreCountry( countryStateOption );
						} }
						geolocatedLocation={ geolocatedLocation }
						onGeolocationOverruledChange={ ( overruled ) => {
							setGeolocationOverruled( overruled );
						} }
					/>
					{ countries.length === 0 && (
						<Notice
							className="woocommerce-profiler-select-control__country-error"
							isDismissible={ false }
							status="error"
						>
							{ createInterpolateElement(
								__(
									'Oops! We encountered a problem while fetching the list of countries to choose from. <retryButton/> or <skipButton/>',
									'woocommerce'
								),
								{
									retryButton: (
										<Button
											onClick={ () => {
												sendEvent( {
													type: 'RETRY_PRE_BUSINESS_INFO',
												} );
											} }
											variant="tertiary"
										>
											{ __(
												'Please try again',
												'woocommerce'
											) }
										</Button>
									),
									skipButton: (
										<Button
											onClick={ () => {
												sendEvent( {
													type: 'SKIP_BUSINESS_INFO_STEP',
												} );
											} }
											variant="tertiary"
										>
											{ __(
												'Skip this step',
												'woocommerce'
											) }
										</Button>
									),
								}
							) }
						</Notice>
					) }
					{
						<>
							<TextControl
								__nextHasNoMarginBottom
								className={ clsx(
									'woocommerce-profiler-business-info-email-adddress',
									{ 'is-error': isEmailInvalid }
								) }
								onChange={ ( value ) => {
									if ( isEmailInvalid ) {
										setDoValidate( true ); // trigger validation as we want to feedback to the user as soon as it becomes valid
									}
									setEmailAddress( value );
								} }
								onBlur={ () => {
									setDoValidate( true );
								} }
								value={ decodeEntities( storeEmailAddress ) }
								label={
									<>
										{ __(
											'Your email address',
											'woocommerce'
										) }
										{ isOptInMarketing && (
											<span className="woocommerce-profiler-question-required">
												{ '*' }
											</span>
										) }
									</>
								}
								placeholder={ __(
									'wordpress@example.com',
									'woocommerce'
								) }
							/>
							{ isEmailInvalid && (
								<FormInputValidation
									isError
									text={ __(
										'This email is not valid.',
										'woocommerce'
									) }
								/>
							) }
							<CheckboxControl
								__nextHasNoMarginBottom
								className="core-profiler__checkbox"
								label={ __(
									'Opt-in to receive tips, discounts, and recommendations from the Woo team directly in your inbox.',
									'woocommerce'
								) }
								checked={ isOptInMarketing }
								onChange={ ( isChecked ) => {
									setIsOptInMarketing( isChecked );
									setDoValidate( true );
								} }
							/>
						</>
					}
				</form>
				<div className="woocommerce-profiler-button-container">
					<Button
						className="woocommerce-profiler-button"
						variant="primary"
						disabled={ ! storeCountry.key || isEmailInvalid }
						onClick={ () => {
							sendEvent( {
								type: 'BUSINESS_INFO_COMPLETED',
								payload: {
									storeName,
									industry: industry?.key,
									storeLocation: storeCountry.key,
									geolocationOverruled:
										geolocationOverruled || false,
									isOptInMarketing,
									storeEmailAddress,
								},
							} );
							setHasSubmitted( true );
						} }
					>
						{ hasSubmitted ? (
							<Spinner />
						) : (
							__( 'Continue', 'woocommerce' )
						) }
					</Button>
				</div>
			</div>
		</div>
	);
};
