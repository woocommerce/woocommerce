/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { SelectControl } from '@woocommerce/components';
import { Icon, chevronDown } from '@wordpress/icons';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '../index';
import { UserProfileEvent } from '../events';
import { Navigation } from '../components/navigation/navigation';
import { Heading } from '../components/heading/heading';
import { Choice } from '../components/choice/choice';
import { MultipleSelector } from '../components/multiple-selector/multiple-selector';

const businessOptions = [
	{
		title: __( 'I’m just starting my business', 'woocommerce' ),
		value: 'im_just_starting_my_business' as const,
	},
	{
		title: __( 'I’m already selling', 'woocommerce' ),
		value: 'im_already_selling' as const,
	},
	{
		title: __( 'I’m setting up a store for a client', 'woocommerce' ),
		value: 'im_setting_up_a_store_for_a_client' as const,
	},
];

const sellingOnlineOptions = [
	{
		label: __( 'Yes, I’m selling online', 'woocommerce' ),
		value: 'yes_im_selling_online' as const,
		key: 'yes_im_selling_online' as const,
	},
	{
		label: __( 'No, I’m selling offline', 'woocommerce' ),
		value: 'no_im_selling_offline' as const,
		key: 'no_im_selling_offline' as const,
	},
	{
		label: __( 'I’m selling both online and offline', 'woocommerce' ),
		value: 'im_selling_both_online_and_offline' as const,
		key: 'im_selling_both_online_and_offline' as const,
	},
];

const platformOptions = [
	{
		label: __( 'Amazon', 'woocommerce' ),
		value: 'amazon' as const,
	},
	{
		label: __( 'Adobe Commerce', 'woocommerce' ),
		value: 'adobe_commerce' as const,
	},
	{
		label: __( 'Big Cartel', 'woocommerce' ),
		value: 'big_cartel' as const,
	},
	{
		label: __( 'Big Commerce', 'woocommerce' ),
		value: 'big_commerce' as const,
	},
	{
		label: __( 'Ebay', 'woocommerce' ),
		value: 'ebay' as const,
	},
	{
		label: __( 'Ecwid', 'woocommerce' ),
		value: 'ecwid' as const,
	},
	{
		label: __( 'Etsy', 'woocommerce' ),
		value: 'etsy' as const,
	},
	{
		label: __( 'Facebook Marketplace', 'woocommerce' ),
		value: 'facebook_marketplace' as const,
	},
	{
		label: __( 'Google Shopping', 'woocommerce' ),
		value: 'google_shopping' as const,
	},
	{
		label: __( 'Pinterest', 'woocommerce' ),
		value: 'pinterest' as const,
	},
	{
		label: __( 'Shopify', 'woocommerce' ),
		value: 'shopify' as const,
	},
	{
		label: __( 'Square', 'woocommerce' ),
		value: 'square' as const,
	},
	{
		label: __( 'Squarespace', 'woocommerce' ),
		value: 'squarespace' as const,
	},
	{
		label: __( 'Wix', 'woocommerce' ),
		value: 'wix' as const,
	},
	{
		label: __( 'WordPress', 'woocommerce' ),
		value: 'wordpress' as const,
	},
];

export type BusinessChoice = ( typeof businessOptions )[ 0 ][ 'value' ];
export type SellingOnlineAnswer =
	( typeof sellingOnlineOptions )[ 0 ][ 'value' ];
export type SellingPlatform = ( typeof platformOptions )[ 0 ][ 'value' ];

export const UserProfile = ( {
	sendEvent,
	navigationProgress,
	context,
}: {
	sendEvent: ( event: UserProfileEvent ) => void;
	navigationProgress: number;
	context: Pick< CoreProfilerStateMachineContext, 'userProfile' >;
} ) => {
	const [ businessChoice, setBusinessChoice ] = useState< BusinessChoice >(
		context.userProfile.businessChoice || 'im_just_starting_my_business'
	);
	const [ sellingOnlineAnswer, setSellingOnlineAnswer ] =
		useState< SellingOnlineAnswer | null >(
			context.userProfile.sellingOnlineAnswer || null
		);
	const [ sellingPlatforms, setSellingPlatforms ] =
		useState< Array< SellingPlatform > | null >(
			context.userProfile.sellingPlatforms || null
		);
	const [ isPlatformDropdownOpen, setIsPlatformDropdownOpen ] =
		useState( false );

	const renderAlreadySellingOptions = () => {
		return (
			<>
				<div className="woocommerce-profiler-selling-online-question">
					<p className="woocommerce-profiler-question-label">
						{ __( 'Are you selling online?', 'woocommerce' ) }
					</p>
					<SelectControl
						className="woocommerce-profiler-select-control__selling-online-question"
						instanceId={ 1 }
						label={ __( 'Select an option', 'woocommerce' ) }
						autoComplete="new-password" // disable autocomplete and autofill
						options={ sellingOnlineOptions }
						excludeSelectedOptions={ false }
						help={ <Icon icon={ chevronDown } /> }
						onChange={ (
							selectedOptionKey: typeof sellingOnlineAnswer
						) => {
							setSellingOnlineAnswer( selectedOptionKey );
						} }
						multiple={ false }
						selected={ sellingOnlineAnswer }
					/>
				</div>
				{ sellingOnlineAnswer &&
					[
						'yes_im_selling_online',
						'im_selling_both_online_and_offline',
					].includes( sellingOnlineAnswer ) && (
						<div className="woocommerce-profiler-selling-platform">
							<p className="woocommerce-profiler-question-label">
								{ __(
									'Which platform(s) are you currently using?',
									'woocommerce'
								) }
							</p>
							<MultipleSelector
								options={ platformOptions }
								selectedOptions={ platformOptions.filter(
									( option ) =>
										sellingPlatforms?.includes(
											option.value
										)
								) }
								onSelect={ ( items ) => {
									setSellingPlatforms(
										items.map(
											( item ) =>
												item.value as SellingPlatform
										)
									);
								} }
								onOpenClose={ setIsPlatformDropdownOpen }
							/>
						</div>
					) }
			</>
		);
	};

	const onContinue = () => {
		sendEvent( {
			type: 'USER_PROFILE_COMPLETED',
			payload: {
				userProfile: {
					businessChoice,
					sellingOnlineAnswer:
						businessChoice === 'im_already_selling'
							? sellingOnlineAnswer
							: null,
					sellingPlatforms:
						businessChoice === 'im_already_selling'
							? sellingPlatforms
							: null,
				},
			},
		} );
	};

	return (
		<div
			className="woocommerce-profiler-user-profile"
			data-testid="core-profiler-user-profile"
		>
			<Navigation
				percentage={ navigationProgress }
				skipText={ __( 'Skip this step', 'woocommerce' ) }
				onSkip={ () =>
					sendEvent( {
						type: 'USER_PROFILE_SKIPPED',
						payload: { userProfile: { skipped: true } },
					} )
				}
			/>
			<div
				className={ clsx(
					'woocommerce-profiler-page__content woocommerce-profiler-user-profile__content',
					{
						'is-platform-selector-open': isPlatformDropdownOpen,
					}
				) }
			>
				<Heading
					className="woocommerce-profiler__stepper-heading"
					title={ __(
						'Which one of these best describes you?',
						'woocommerce'
					) }
					subTitle={ __(
						'Let us know where you are in your commerce journey so that we can tailor your Woo experience for you.',
						'woocommerce'
					) }
				/>
				<form className="woocommerce-user-profile-choices">
					<fieldset>
						<legend className="screen-reader-text">
							{ __(
								'Which one of these best describes you?',
								'woocommerce'
							) }
						</legend>
						{ businessOptions.map( ( { title, value } ) => {
							return (
								<Choice
									key={ value }
									name="user-profile-choice"
									title={ title }
									selected={ businessChoice === value }
									value={ value }
									onChange={ ( _value ) => {
										setBusinessChoice(
											_value as BusinessChoice
										);
									} }
									subOptionsComponent={
										value === 'im_already_selling'
											? renderAlreadySellingOptions()
											: null
									}
								/>
							);
						} ) }
					</fieldset>
				</form>
				<div className="woocommerce-profiler-button-container">
					<Button
						className="woocommerce-profiler-button"
						variant="primary"
						onClick={ onContinue }
					>
						{ __( 'Continue', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</div>
	);
};
