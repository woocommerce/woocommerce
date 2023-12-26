/**
 * External dependencies
 */
import { registerPlugin, PluginArea } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import {
	Button,
	Card,
	CardBody,
	createSlotFill,
	SlotFillProvider,
} from '@wordpress/components';
import { Icon, closeSmall } from '@wordpress/icons';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './conflict-error-slotfill.scss';
import warningIcon from './alert-triangle-icon.svg';

const { Fill, Slot } = createSlotFill( '__EXPERIMENTAL__WcAdminConflictError' );

const LearnMore = () => (
	<Button
		href="https://woo.com/document/setting-up-taxes-in-woocommerce/"
		target="_blank"
	>
		{ __( 'Learn more', 'woocommerce' ) }
	</Button>
);

const SettingsErrorFill = () => {
	const [ dismissedConflictWarning, setDismissedConflictWarning ] =
		useState( false );

	const [ pricesEnteredWithTaxSetting, setMainVal ] = useState(
		document.forms.mainform.elements.woocommerce_prices_include_tax
			.value === 'yes'
			? 'incl'
			: 'excl'
	);
	const [ displayPricesInShopWithTaxSetting, setDisplayShop ] = useState(
		/** We're using jQuery in this file because the select boxes are implemented using select2 and can only be interacted with using jQuery */
		window.jQuery( '#woocommerce_tax_display_shop' ).val()
	);
	const [ displayPricesInCartWithTaxSetting, setDisplayCart ] = useState(
		/** We're using jQuery in this file because the select boxes are implemented using select2 and can only be interacted with using jQuery */
		window.jQuery( '#woocommerce_tax_display_cart' ).val()
	);

	const { createNotice } = useDispatch( noticesStore );

	const handleApplyRecommendedSettings = () => {
		/** We're using jQuery in this file because the select boxes are implemented using select2 and can only be interacted with using jQuery */
		// eslint-disable-next-line no-undef
		window
			.jQuery( '#woocommerce_tax_display_shop' )
			.val( pricesEnteredWithTaxSetting )
			.trigger( 'change' );
		window
			.jQuery( '#woocommerce_tax_display_cart' )
			.val( pricesEnteredWithTaxSetting )
			.trigger( 'change' );

		createNotice(
			'success',
			__( 'Recommended settings applied.', 'woocommerce' )
		);

		recordEvent( 'tax_settings_conflict_recommended_settings_clicked' );
	};

	const ApplyRecommendedSettingsButton = () => (
		<Button variant="primary" onClick={ handleApplyRecommendedSettings }>
			{ __( 'Use recommended settings', 'woocommerce' ) }
		</Button>
	);

	useEffect( () => {
		document
			.querySelectorAll( "input[name='woocommerce_prices_include_tax']" )
			.forEach( ( input ) => {
				input.addEventListener( 'change', () =>
					setMainVal(
						document.forms.mainform.elements
							.woocommerce_prices_include_tax.value === 'yes'
							? 'incl'
							: 'excl'
					)
				);
			} );
	}, [] );

	useEffect( () => {
		window
			.jQuery( '#woocommerce_tax_display_shop' )
			.on( 'click change', () =>
				setDisplayShop(
					document.getElementById( 'woocommerce_tax_display_shop' )
						.value
				)
			);
	}, [] );

	useEffect( () => {
		window
			.jQuery( '#woocommerce_tax_display_cart' )
			.on( 'click change', () =>
				setDisplayCart(
					document.getElementById( 'woocommerce_tax_display_cart' )
						.value
				)
			);
	}, [] );

	const [ isConflict, setIsConflict ] = useState( false );

	useEffect( () => {
		if (
			displayPricesInShopWithTaxSetting === pricesEnteredWithTaxSetting &&
			displayPricesInCartWithTaxSetting === pricesEnteredWithTaxSetting
		) {
			setIsConflict( false );
		} else {
			setIsConflict( true );

			recordEvent( 'tax_settings_conflict', {
				main: pricesEnteredWithTaxSetting,
				shop: displayPricesInShopWithTaxSetting,
				cart: displayPricesInCartWithTaxSetting,
			} );
		}
	}, [
		displayPricesInCartWithTaxSetting,
		displayPricesInShopWithTaxSetting,
		pricesEnteredWithTaxSetting,
	] );

	if ( ! isConflict || dismissedConflictWarning ) {
		return <Fill></Fill>;
	}

	return (
		<Fill>
			<div className="woocommerce_tax_settings_conflict_error">
				<Card>
					<CardBody className="woocommerce_tax_settings_conflict_error_card_body">
						<div>
							<img
								className="woocommerce_tax_settings_conflict_error_card_body__warning_icon"
								src={ warningIcon }
								alt="Warning Icon"
							/>
						</div>
						<div>
							<div className="woocommerce_tax_settings_conflict_error_card_body__body_text">
								<p style={ { fontSize: 13 } }>
									{ interpolateComponents( {
										mixedString: __(
											'{{b}}Inconsistent tax settings:{{/b}} To avoid possible rounding errors, prices should be entered and displayed consistently in all locations either including, or excluding taxes.',
											'woocommerce'
										),
										components: {
											b: <b />,
										},
									} ) }
								</p>
							</div>
							<div className="woocommerce_tax_settings_conflict_error_card_body__buttons">
								<ApplyRecommendedSettingsButton /> <LearnMore />
							</div>
						</div>
						<div>
							<Button
								className="woocommerce_tax_settings_conflict_error_card_body__close_icon"
								onClick={ () => {
									setDismissedConflictWarning( true );

									recordEvent(
										'tax_settings_conflict_dismissed'
									);
								} }
							>
								<Icon icon={ closeSmall } />
							</Button>
						</div>
					</CardBody>
				</Card>
			</div>
		</Fill>
	);
};

export const WcAdminConflictErrorSlot = () => {
	return (
		<>
			<SlotFillProvider>
				<Slot />
				<PluginArea scope="woocommerce-settings" />
			</SlotFillProvider>
		</>
	);
};

registerPlugin( 'woocommerce-admin-tax-settings-conflict-warning', {
	scope: 'woocommerce-settings',
	render: SettingsErrorFill,
} );
