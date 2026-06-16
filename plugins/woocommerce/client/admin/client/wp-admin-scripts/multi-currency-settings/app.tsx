/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	CheckboxControl,
	Modal,
	SearchControl,
	Spinner,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { MultiCurrencyCurrency, StoreCurrenciesResponse } from './types';
import { StoreLevelSettings } from './store-settings';

const REST_BASE = '/wc/v3/payments/multi-currency';

const currencyValues = (
	currencies: Record< string, MultiCurrencyCurrency >
): MultiCurrencyCurrency[] => Object.values( currencies );

const normalizeEnabledCodes = (
	codes: string[],
	defaultCode: string,
	availableCurrencies: MultiCurrencyCurrency[]
): string[] => {
	const selectedLookup = new Set( [ defaultCode, ...codes ] );

	return availableCurrencies
		.map( ( currency ) => currency.code )
		.filter( ( code ) => selectedLookup.has( code ) );
};

const formatExchangeRate = ( currency: MultiCurrencyCurrency ): string => {
	if ( currency.is_default ) {
		return __( 'Default currency', 'woocommerce' );
	}

	return currency.rate.toLocaleString( undefined, {
		maximumFractionDigits: 6,
	} );
};

export function MultiCurrencySettingsApp() {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );
	const [ currencies, setCurrencies ] =
		useState< StoreCurrenciesResponse | null >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ selectedCodes, setSelectedCodes ] = useState< string[] >( [] );
	const [ search, setSearch ] = useState( '' );
	const manageCurrenciesButtonRef = useRef< HTMLButtonElement | null >(
		null
	);
	const shouldRestoreManagementFocusRef = useRef( false );

	useEffect( () => {
		let isMounted = true;

		apiFetch< StoreCurrenciesResponse >( {
			path: `${ REST_BASE }/currencies`,
		} )
			.then( ( response ) => {
				if ( ! isMounted ) {
					return;
				}

				setCurrencies( response );
			} )
			.catch( () => {
				if ( ! isMounted ) {
					return;
				}

				createErrorNotice(
					__( 'Error loading currencies.', 'woocommerce' )
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

	const availableCurrencies = useMemo(
		() => ( currencies ? currencyValues( currencies.available ) : [] ),
		[ currencies ]
	);
	const enabledCurrencies = useMemo(
		() => ( currencies ? currencyValues( currencies.enabled ) : [] ),
		[ currencies ]
	);
	const defaultCode = currencies?.default.code ?? '';

	useEffect( () => {
		if (
			isSaving ||
			isModalOpen ||
			! shouldRestoreManagementFocusRef.current
		) {
			return;
		}

		shouldRestoreManagementFocusRef.current = false;
		manageCurrenciesButtonRef.current?.focus();
	}, [ isModalOpen, isSaving, currencies ] );

	const filteredAvailableCurrencies = useMemo( () => {
		const query = search.trim().toLowerCase();

		return availableCurrencies.filter( ( currency ) => {
			if ( currency.code === defaultCode ) {
				return false;
			}

			if ( query === '' ) {
				return true;
			}

			return (
				currency.name.toLowerCase().includes( query ) ||
				currency.code.toLowerCase().includes( query )
			);
		} );
	}, [ availableCurrencies, defaultCode, search ] );

	const saveEnabledCurrencies = async ( codes: string[] ) => {
		if ( ! currencies || isSaving ) {
			return;
		}

		const enabled = normalizeEnabledCodes(
			codes,
			currencies.default.code,
			availableCurrencies
		);

		setIsSaving( true );

		try {
			const response = await apiFetch< StoreCurrenciesResponse >( {
				path: `${ REST_BASE }/update-enabled-currencies`,
				method: 'POST',
				data: { enabled },
			} );

			shouldRestoreManagementFocusRef.current = true;
			setCurrencies( response );
			setIsModalOpen( false );
			createSuccessNotice(
				__( 'Enabled currencies updated.', 'woocommerce' )
			);
		} catch ( error ) {
			createErrorNotice(
				__( 'Error updating enabled currencies.', 'woocommerce' )
			);
		} finally {
			setIsSaving( false );
		}
	};

	const openModal = () => {
		setSelectedCodes(
			enabledCurrencies.map( ( currency ) => currency.code )
		);
		setSearch( '' );
		setIsModalOpen( true );
	};

	const toggleCurrency = ( code: string, checked: boolean ) => {
		setSelectedCodes( ( currentCodes ) => {
			if ( checked ) {
				return currentCodes.includes( code )
					? currentCodes
					: [ ...currentCodes, code ];
			}

			return currentCodes.filter(
				( currentCode ) => currentCode !== code
			);
		} );
	};

	if ( isLoading ) {
		return (
			<p aria-live="polite">
				<Spinner />
				{ __( 'Loading currencies…', 'woocommerce' ) }
			</p>
		);
	}

	if ( ! currencies ) {
		return (
			<p role="alert">
				{ __(
					'Unable to load multi-currency settings.',
					'woocommerce'
				) }
			</p>
		);
	}

	return (
		<div className="woocommerce-multi-currency-settings">
			<StoreLevelSettings />

			<div className="woocommerce-multi-currency-settings__actions">
				<h2>{ __( 'Enabled currencies', 'woocommerce' ) }</h2>
				<Button
					ref={ manageCurrenciesButtonRef }
					variant="secondary"
					aria-haspopup="dialog"
					aria-expanded={ isModalOpen }
					onClick={ openModal }
				>
					{ __( 'Add/remove currencies', 'woocommerce' ) }
				</Button>
			</div>

			<table className="widefat striped">
				<thead>
					<tr>
						<th scope="col">{ __( 'Name', 'woocommerce' ) }</th>
						<th scope="col">{ __( 'Code', 'woocommerce' ) }</th>
						<th scope="col">
							{ __( 'Exchange rate', 'woocommerce' ) }
						</th>
						<th scope="col">{ __( 'Actions', 'woocommerce' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ enabledCurrencies.map( ( currency ) => (
						<tr key={ currency.code }>
							<th scope="row">{ currency.name }</th>
							<td>{ currency.code }</td>
							<td>{ formatExchangeRate( currency ) }</td>
							<td>
								{ currency.is_default ? (
									<span>
										{ __(
											'Default currency',
											'woocommerce'
										) }
									</span>
								) : (
									<Button
										variant="link"
										disabled={ isSaving }
										accessibleWhenDisabled
										aria-label={ sprintf(
											/* translators: %s: Currency name. */
											__(
												'Remove %s as an enabled currency',
												'woocommerce'
											),
											currency.name
										) }
										onClick={ () =>
											saveEnabledCurrencies(
												enabledCurrencies
													.map(
														( enabledCurrency ) =>
															enabledCurrency.code
													)
													.filter(
														( code ) =>
															code !==
															currency.code
													)
											)
										}
									>
										{ __( 'Remove', 'woocommerce' ) }
									</Button>
								) }
							</td>
						</tr>
					) ) }
				</tbody>
			</table>

			{ isModalOpen && (
				<Modal
					title={ __( 'Add enabled currencies', 'woocommerce' ) }
					onRequestClose={ () => setIsModalOpen( false ) }
				>
					<SearchControl
						__nextHasNoMarginBottom
						label={ __( 'Search currencies', 'woocommerce' ) }
						placeholder={ __( 'Search currencies', 'woocommerce' ) }
						value={ search }
						onChange={ ( value ) => setSearch( value ) }
					/>
					<div>
						{ filteredAvailableCurrencies.map( ( currency ) => (
							<CheckboxControl
								__nextHasNoMarginBottom
								key={ currency.code }
								label={ `${ currency.name } ${ currency.code }` }
								checked={ selectedCodes.includes(
									currency.code
								) }
								onChange={ ( checked ) =>
									toggleCurrency(
										currency.code,
										Boolean( checked )
									)
								}
							/>
						) ) }
					</div>
					<div className="woocommerce-multi-currency-settings__modal-actions">
						<Button
							variant="tertiary"
							onClick={ () => setIsModalOpen( false ) }
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							isBusy={ isSaving }
							disabled={ isSaving }
							accessibleWhenDisabled
							onClick={ () =>
								saveEnabledCurrencies( selectedCodes )
							}
						>
							{ __( 'Update selected', 'woocommerce' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}
