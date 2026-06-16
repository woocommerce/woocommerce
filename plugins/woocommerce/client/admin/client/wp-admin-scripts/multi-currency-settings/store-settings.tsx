/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	CheckboxControl,
	RadioControl,
	Spinner,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type {
	RenderingMode,
	StoreSettingsBoolean,
	StoreSettingsResponse,
	StoreSettingsState,
} from './types';

const REST_BASE = '/wc/v3/payments/multi-currency';

const isEnabled = ( value: StoreSettingsBoolean ): boolean =>
	value === true || value === 'yes';

const normalizeStoreSettings = (
	response: StoreSettingsResponse
): StoreSettingsState => ( {
	enableAutoCurrency: isEnabled(
		response.wcpay_multi_currency_enable_auto_currency
	),
	enableStorefrontSwitcher: isEnabled(
		response.wcpay_multi_currency_enable_storefront_switcher
	),
	renderingMode: response.wcpay_multi_currency_rendering_mode || 'speed',
	isCacheOptimizedFeatureEnabled: response.is_cache_optimized_feature_enabled,
	siteTheme: response.site_theme,
} );

const serializeStoreSettings = ( settings: StoreSettingsState ) => ( {
	wcpay_multi_currency_enable_auto_currency: settings.enableAutoCurrency
		? 'yes'
		: 'no',
	wcpay_multi_currency_enable_storefront_switcher:
		settings.enableStorefrontSwitcher ? 'yes' : 'no',
	wcpay_multi_currency_rendering_mode: settings.renderingMode,
} );

const areSettingsEqual = (
	currentSettings: StoreSettingsState | null,
	draftSettings: StoreSettingsState | null
): boolean => {
	if ( ! currentSettings || ! draftSettings ) {
		return true;
	}

	return (
		currentSettings.enableAutoCurrency ===
			draftSettings.enableAutoCurrency &&
		currentSettings.enableStorefrontSwitcher ===
			draftSettings.enableStorefrontSwitcher &&
		currentSettings.renderingMode === draftSettings.renderingMode
	);
};

export function StoreLevelSettings() {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );
	const [ currentSettings, setCurrentSettings ] =
		useState< StoreSettingsState | null >( null );
	const [ draftSettings, setDraftSettings ] =
		useState< StoreSettingsState | null >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );

	useEffect( () => {
		let isMounted = true;

		apiFetch< StoreSettingsResponse >( {
			path: `${ REST_BASE }/get-settings`,
		} )
			.then( ( response ) => {
				if ( ! isMounted ) {
					return;
				}

				const normalizedSettings = normalizeStoreSettings( response );
				setCurrentSettings( normalizedSettings );
				setDraftSettings( normalizedSettings );
			} )
			.catch( () => {
				if ( ! isMounted ) {
					return;
				}

				createErrorNotice(
					__( 'Error loading store settings.', 'woocommerce' )
				);
			} )
			.finally( () => {
				if ( isMounted ) {
					setIsLoading( false );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [ createErrorNotice ] );

	const isDirty = useMemo(
		() => ! areSettingsEqual( currentSettings, draftSettings ),
		[ currentSettings, draftSettings ]
	);

	const updateDraftSettings = (
		values: Partial< StoreSettingsState >
	): void => {
		setDraftSettings( ( settings ) =>
			settings
				? {
						...settings,
						...values,
				  }
				: settings
		);
	};

	const saveSettings = async () => {
		if ( ! draftSettings || isSaving || ! isDirty ) {
			return;
		}

		setIsSaving( true );

		try {
			const response = await apiFetch< StoreSettingsResponse >( {
				path: `${ REST_BASE }/update-settings`,
				method: 'POST',
				data: serializeStoreSettings( draftSettings ),
			} );
			const normalizedSettings = normalizeStoreSettings( response );

			setCurrentSettings( normalizedSettings );
			setDraftSettings( normalizedSettings );
			createSuccessNotice( __( 'Store settings saved.', 'woocommerce' ) );
		} catch ( error ) {
			createErrorNotice(
				__( 'Error saving store settings.', 'woocommerce' )
			);
		} finally {
			setIsSaving( false );
		}
	};

	if ( isLoading ) {
		return (
			<section className="woocommerce-multi-currency-settings__store-settings">
				<p aria-live="polite">
					<Spinner />
					{ __( 'Loading store settings…', 'woocommerce' ) }
				</p>
			</section>
		);
	}

	if ( ! draftSettings ) {
		return (
			<section className="woocommerce-multi-currency-settings__store-settings">
				<p role="alert">
					{ __( 'Unable to load store settings.', 'woocommerce' ) }
				</p>
			</section>
		);
	}

	return (
		<section className="woocommerce-multi-currency-settings__store-settings">
			<h2>{ __( 'Store settings', 'woocommerce' ) }</h2>
			<CheckboxControl
				__nextHasNoMarginBottom
				checked={ draftSettings.enableAutoCurrency }
				label={ __(
					'Automatically switch customers to their local currency if it has been enabled',
					'woocommerce'
				) }
				onChange={ ( checked ) =>
					updateDraftSettings( {
						enableAutoCurrency: Boolean( checked ),
					} )
				}
			/>
			{ draftSettings.siteTheme === 'Storefront' && (
				<CheckboxControl
					__nextHasNoMarginBottom
					checked={ draftSettings.enableStorefrontSwitcher }
					label={ __(
						'Add a currency switcher to the Storefront theme on breadcrumb section.',
						'woocommerce'
					) }
					onChange={ ( checked ) =>
						updateDraftSettings( {
							enableStorefrontSwitcher: Boolean( checked ),
						} )
					}
				/>
			) }
			{ draftSettings.isCacheOptimizedFeatureEnabled && (
				<RadioControl
					label={ __( 'Price rendering mode', 'woocommerce' ) }
					selected={ draftSettings.renderingMode }
					options={ [
						{
							label: __(
								'Optimized for speed (default)',
								'woocommerce'
							),
							value: 'speed',
						},
						{
							label: __( 'Optimized for caching', 'woocommerce' ),
							value: 'cache',
						},
					] }
					onChange={ ( value ) =>
						updateDraftSettings( {
							renderingMode: value as RenderingMode,
						} )
					}
				/>
			) }
			<Button
				variant="primary"
				isBusy={ isSaving }
				disabled={ isSaving || ! isDirty }
				accessibleWhenDisabled
				onClick={ saveSettings }
			>
				{ __( 'Save changes', 'woocommerce' ) }
			</Button>
		</section>
	);
}
