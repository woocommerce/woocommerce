/**
 * External dependencies
 */
import {
	Button,
	Card,
	CheckboxControl,
	Notice,
	Spinner,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { useSettings } from '../data/hooks';

export type ExpressCheckoutLocation = 'product' | 'cart' | 'checkout';

type LocationCheckboxesProps = {
	enabledLocations: string[];
	isMethodEnabled: boolean;
	isPaymentMethodsListMode?: boolean;
	onChange: ( location: ExpressCheckoutLocation, value: boolean ) => void;
};

export const ExpressCheckoutSettingsSection = ( {
	className,
	description,
	title,
	children,
}: {
	className?: string;
	description: ReactNode;
	title: string;
	children: ReactNode;
} ) => (
	<section
		className={ [
			'woopayments-express-checkout-settings__section',
			className,
		]
			.filter( Boolean )
			.join( ' ' ) }
		aria-label={ title }
	>
		<div className="woopayments-express-checkout-settings__section-description">
			{ description }
		</div>
		<Card className="woopayments-express-checkout-settings__section-controls">
			{ children }
		</Card>
	</section>
);

export const ExpressCheckoutInlineNotice = ( {
	children,
	status = 'warning',
}: {
	children: ReactNode;
	status?: 'info' | 'warning' | 'error' | 'success';
} ) => (
	<Notice
		className="woopayments-express-checkout-settings__notice"
		status={ status }
		isDismissible={ false }
	>
		{ children }
	</Notice>
);

export const ExpressCheckoutLocationCheckboxes = ( {
	enabledLocations,
	isMethodEnabled,
	isPaymentMethodsListMode = false,
	onChange,
}: LocationCheckboxesProps ) => {
	const locations: Array< [ ExpressCheckoutLocation, string ] > = [
		[ 'product', __( 'Show on product page', 'woocommerce' ) ],
		[ 'cart', __( 'Show on cart page', 'woocommerce' ) ],
		[ 'checkout', __( 'Show on checkout page', 'woocommerce' ) ],
	];

	return (
		<ul className="woopayments-express-checkout-settings__locations">
			{ locations.map( ( [ location, label ] ) => {
				const checked = isPaymentMethodsListMode
					? location === 'checkout'
					: isMethodEnabled && enabledLocations.includes( location );

				return (
					<li key={ location }>
						<CheckboxControl
							checked={ checked }
							disabled={
								isPaymentMethodsListMode || ! isMethodEnabled
							}
							label={ label }
							onChange={ ( value ) =>
								onChange( location, Boolean( value ) )
							}
							__nextHasNoMarginBottom
						/>
					</li>
				);
			} ) }
		</ul>
	);
};

export const ExpressCheckoutPreviewFallback = () => (
	<ExpressCheckoutInlineNotice status="info">
		{ __(
			'To preview the express checkout buttons, ensure your store uses HTTPS on a publicly available domain, and you are viewing this page in a Safari or Chrome browser. Your device must be configured to use Apple Pay or Google Pay.',
			'woocommerce'
		) }
	</ExpressCheckoutInlineNotice>
);

export const ExpressCheckoutSaveBar = () => {
	const { saveSettings, isSaving, isLoading, isDirty } = useSettings();
	const [ statusMessage, setStatusMessage ] = useState( '' );
	const isDisabled = isSaving || isLoading || ! isDirty;

	const saveOnClick = async () => {
		if ( isDisabled ) {
			return;
		}

		setStatusMessage( '' );
		const isSuccess = await saveSettings();
		setStatusMessage(
			isSuccess
				? __( 'Settings saved.', 'woocommerce' )
				: __( 'Error saving settings.', 'woocommerce' )
		);
	};

	return (
		<div className="woopayments-settings-save-bar">
			<Button
				variant="primary"
				isBusy={ isSaving }
				disabled={ isDisabled }
				accessibleWhenDisabled
				onClick={ saveOnClick }
			>
				{ __( 'Save changes', 'woocommerce' ) }
			</Button>
			<p
				aria-live="polite"
				className="woopayments-settings-save-bar__status"
			>
				{ isDirty
					? __( 'You have unsaved changes.', 'woocommerce' )
					: statusMessage ||
					  __( 'Settings are up to date.', 'woocommerce' ) }
			</p>
		</div>
	);
};

export const ExpressCheckoutBusyState = ( {
	children,
	isBusy,
}: {
	children: ReactNode;
	isBusy: boolean;
} ) => (
	<div
		className="woopayments-express-checkout-settings__busy-state"
		aria-busy={ isBusy }
	>
		{ isBusy && (
			<div
				className="woopayments-express-checkout-settings__busy-overlay"
				role="status"
				aria-live="polite"
			>
				<Spinner />
				{ __( 'Saving settings…', 'woocommerce' ) }
			</div>
		) }
		{ children }
	</div>
);
