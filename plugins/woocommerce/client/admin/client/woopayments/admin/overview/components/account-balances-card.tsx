/**
 * External dependencies
 */
import { Button, ExternalLink, Modal } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type {
	WooPaymentsDeposit,
	WooPaymentsDepositsOverview,
	WooPaymentsInstantBalance,
} from '../types';
import {
	formatWooPaymentsAmount,
	getBalanceCurrencyOptions,
	getAmountForCurrency,
	getInstantBalanceForCurrency,
	getSelectedBalanceCurrency,
} from '../utils';
import { getSettingsPaymentsProviderRouteUrl } from '../../utils';

const INSTANT_PAYOUTS_DOCS_URL =
	'https://woocommerce.com/document/woopayments/payouts/instant-payouts/';

const InstantPayoutModal = ( {
	instantBalance,
	isSubmitting,
	onClose,
	onSubmit,
}: {
	instantBalance: WooPaymentsInstantBalance;
	isSubmitting: boolean;
	onClose: () => void;
	onSubmit: () => void;
} ) => {
	const feePercentage = `${ instantBalance.fee_percentage }%`;

	return (
		<Modal
			title={ __( 'Instant payout', 'woocommerce' ) }
			onRequestClose={ onClose }
			className="woocommerce-woopayments-instant-payout-modal"
		>
			<p>
				{ sprintf(
					/* translators: %s: Instant payout fee percentage. */
					__(
						'Need cash in a hurry? Instant payouts are available within 30 minutes for a nominal %s service fee.',
						'woocommerce'
					),
					feePercentage
				) }{ ' ' }
				<ExternalLink href={ INSTANT_PAYOUTS_DOCS_URL }>
					{ __( 'Learn more', 'woocommerce' ) }
				</ExternalLink>
			</p>
			<ul>
				<li className="woocommerce-woopayments-instant-payout-modal__balance">
					{ __(
						'Balance available for instant payout:',
						'woocommerce'
					) }{ ' ' }
					<span>
						{ formatWooPaymentsAmount(
							instantBalance.amount,
							instantBalance.currency
						) }
					</span>
				</li>
				<li className="woocommerce-woopayments-instant-payout-modal__fee">
					{ sprintf(
						/* translators: %s: Instant payout fee percentage. */
						__( '%s service fee:', 'woocommerce' ),
						feePercentage
					) }{ ' ' }
					<span>
						-
						{ formatWooPaymentsAmount(
							instantBalance.fee,
							instantBalance.currency
						) }
					</span>
				</li>
				<li className="woocommerce-woopayments-instant-payout-modal__net">
					{ __( 'Net payout amount:', 'woocommerce' ) }{ ' ' }
					<span>
						{ formatWooPaymentsAmount(
							instantBalance.net,
							instantBalance.currency
						) }
					</span>
				</li>
			</ul>

			<div className="woocommerce-woopayments-instant-payout-modal__footer">
				<Button
					variant="secondary"
					onClick={ onClose }
					__next40pxDefaultSize
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ onSubmit }
					isBusy={ isSubmitting }
					disabled={ isSubmitting }
					__next40pxDefaultSize
				>
					{ sprintf(
						/* translators: %s: Net instant payout amount. */
						__( 'Pay out %s now', 'woocommerce' ),
						formatWooPaymentsAmount(
							instantBalance.net,
							instantBalance.currency
						)
					) }
				</Button>
			</div>
		</Modal>
	);
};

export const AccountBalancesCard = ( {
	isLoading,
	errorMessage,
	overview,
	selectedCurrency,
	onCurrencyChange,
	onInstantPayoutSubmit,
}: {
	isLoading: boolean;
	errorMessage: string | null;
	overview: WooPaymentsDepositsOverview | null;
	selectedCurrency?: string;
	onCurrencyChange?: ( currency: string ) => void;
	onInstantPayoutSubmit?: (
		currency: string
	) => Promise< WooPaymentsDeposit >;
} ) => {
	const [ isInstantPayoutModalOpen, setIsInstantPayoutModalOpen ] =
		useState( false );
	const [ isInstantPayoutSubmitting, setIsInstantPayoutSubmitting ] =
		useState( false );
	const headingId = 'woocommerce-woopayments-balance-heading';
	const currencySelectId = 'woocommerce-woopayments-balance-currency';
	const statusMessage =
		( isLoading && __( 'Loading balance…', 'woocommerce' ) ) ||
		errorMessage ||
		'';

	if ( ! isLoading && ! errorMessage && ! overview ) {
		return null;
	}

	const currency = overview
		? getSelectedBalanceCurrency( overview, selectedCurrency )
		: '';
	const currencyOptions = overview
		? getBalanceCurrencyOptions( overview )
		: [];
	const available = overview
		? getAmountForCurrency( overview.balance?.available, currency )
		: 0;
	const pending = overview
		? getAmountForCurrency( overview.balance?.pending, currency )
		: 0;
	const total = available + pending;
	const hasBalanceData = ! isLoading && ! errorMessage && !! overview;
	const instantBalance = hasBalanceData
		? getInstantBalanceForCurrency( overview, currency )
		: null;
	const hasInstantBalance = !! instantBalance && instantBalance.amount > 0;
	const submitInstantPayout = async () => {
		if ( ! instantBalance || ! onInstantPayoutSubmit ) {
			return;
		}

		setIsInstantPayoutSubmitting( true );

		try {
			const deposit = await onInstantPayoutSubmit(
				instantBalance.currency
			);
			const depositAmount = formatWooPaymentsAmount(
				deposit.amount,
				deposit.currency || instantBalance.currency
			);

			setIsInstantPayoutModalOpen( false );
			dispatch( 'core/notices' ).createSuccessNotice(
				sprintf(
					/* translators: %s: Instant payout amount. */
					__( 'Instant payout for %s in transit.', 'woocommerce' ),
					depositAmount
				),
				{
					actions: [
						{
							label: __( 'View details', 'woocommerce' ),
							url: getSettingsPaymentsProviderRouteUrl(
								`/woopayments/payouts/details?id=${ encodeURIComponent(
									deposit.id
								) }`
							),
						},
					],
				}
			);
		} catch ( error ) {
			dispatch( 'core/notices' ).createErrorNotice(
				__( 'Error creating instant payout.', 'woocommerce' )
			);
		} finally {
			setIsInstantPayoutSubmitting( false );
		}
	};

	return (
		<section
			className="woocommerce-woopayments-overview-card"
			aria-labelledby={ headingId }
			aria-busy={ isLoading }
		>
			<div className="woocommerce-woopayments-overview-card__header">
				<h2 id={ headingId } tabIndex={ -1 }>
					{ __( 'Balance', 'woocommerce' ) }
				</h2>
				{ hasBalanceData && currencyOptions.length > 1 && (
					<div className="woocommerce-woopayments-overview__currency-selector">
						<label htmlFor={ currencySelectId }>
							{ __( 'Balance currency', 'woocommerce' ) }
						</label>
						<select
							id={ currencySelectId }
							value={ currency }
							onChange={ ( event ) =>
								onCurrencyChange?.( event.target.value )
							}
						>
							{ currencyOptions.map( ( currencyOption ) => (
								<option
									key={ currencyOption }
									value={ currencyOption }
								>
									{ currencyOption.toUpperCase() }
								</option>
							) ) }
						</select>
					</div>
				) }
			</div>
			<p
				className={
					hasBalanceData
						? 'screen-reader-text'
						: 'woocommerce-woopayments-overview__status'
				}
				role={ errorMessage ? 'alert' : 'status' }
				aria-live={ errorMessage ? 'assertive' : 'polite' }
			>
				{ statusMessage }
			</p>
			{ hasBalanceData && (
				<>
					<dl className="woocommerce-woopayments-overview__balance-grid woocommerce-woopayments-overview__balance-grid--summary">
						<div>
							<dt>{ __( 'Total balance', 'woocommerce' ) }</dt>
							<dd
								aria-label={ __(
									'Total balance',
									'woocommerce'
								) }
							>
								{ formatWooPaymentsAmount( total, currency ) }
							</dd>
							<dd className="woocommerce-woopayments-overview__help">
								{ __(
									'Total balance combines both pending funds (transactions under processing) and available funds (ready for payout).',
									'woocommerce'
								) }
							</dd>
						</div>
						<div>
							<dt>{ __( 'Available funds', 'woocommerce' ) }</dt>
							<dd
								aria-label={ __(
									'Available funds',
									'woocommerce'
								) }
							>
								{ formatWooPaymentsAmount(
									available,
									currency
								) }
							</dd>
							<dd className="woocommerce-woopayments-overview__help">
								{ __(
									'Available funds have completed processing and are ready to be dispatched to your bank account.',
									'woocommerce'
								) }
							</dd>
						</div>
					</dl>
					{ hasInstantBalance && (
						<div className="woocommerce-woopayments-overview__instant-payout">
							<p>
								{ sprintf(
									/* translators: 1: Available instant payout amount, 2: Instant payout fee percentage. */
									__(
										'Get %1$s via instant payout. Funds are typically in your bank account within 30 mins. Fee: %2$s%%.',
										'woocommerce'
									),
									formatWooPaymentsAmount(
										instantBalance.amount,
										instantBalance.currency
									),
									String( instantBalance.fee_percentage )
								) }
							</p>
							<Button
								variant="primary"
								onClick={ () =>
									setIsInstantPayoutModalOpen( true )
								}
								disabled={ ! onInstantPayoutSubmit }
								__next40pxDefaultSize
							>
								{ sprintf(
									/* translators: %s: Available instant payout amount. */
									__( 'Get %s now', 'woocommerce' ),
									formatWooPaymentsAmount(
										instantBalance.amount,
										instantBalance.currency
									)
								) }
							</Button>
						</div>
					) }
					{ isInstantPayoutModalOpen && instantBalance && (
						<InstantPayoutModal
							instantBalance={ instantBalance }
							isSubmitting={ isInstantPayoutSubmitting }
							onClose={ () =>
								setIsInstantPayoutModalOpen( false )
							}
							onSubmit={ submitInstantPayout }
						/>
					) }
				</>
			) }
		</section>
	);
};
