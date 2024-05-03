/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	ToggleControl,
	__experimentalRadio as Radio,
	__experimentalRadioGroup as RadioGroup,
} from '@wordpress/components';
import { Icon, store, shipping } from '@wordpress/icons';
import { ADMIN_URL, getSetting } from '@woocommerce/settings';
import { LOCAL_PICKUP_ENABLED } from '@woocommerce/block-settings';
import {
	InspectorControls,
	useBlockProps,
	RichText,
} from '@wordpress/block-editor';
import { useShippingData } from '@woocommerce/base-context/hooks';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';
import { useDispatch, useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import ExternalLinkCard from '@woocommerce/editor-components/external-link-card';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	FormStepBlock,
	AdditionalFields,
	AdditionalFieldsContent,
} from '../../form-step';
import { RatePrice, getLocalPickupPrices, getShippingPrices } from './shared';
import type { minMaxPrices } from './shared';
import './style.scss';
import { defaultShippingText, defaultLocalPickupText } from './constants';

const LocalPickupSelector = ( {
	checked,
	rate,
	showPrice,
	showIcon,
	toggleText,
	setAttributes,
}: {
	checked: string;
	rate: minMaxPrices;
	showPrice: boolean;
	showIcon: boolean;
	toggleText: string;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ) => {
	return (
		<Radio
			value="pickup"
			className={ classnames(
				'wc-block-checkout__shipping-method-option',
				{
					'wc-block-checkout__shipping-method-option--selected':
						checked === 'pickup',
				}
			) }
		>
			{ showIcon === true && (
				<Icon
					icon={ store }
					size={ 28 }
					className="wc-block-checkout__shipping-method-option-icon"
				/>
			) }
			<RichText
				value={ toggleText }
				placeholder={ defaultLocalPickupText }
				tagName="span"
				className="wc-block-checkout__shipping-method-option-title"
				onChange={ ( value ) =>
					setAttributes( { localPickupText: value } )
				}
				__unstableDisableFormats
				preserveWhiteSpace
			/>
			{ showPrice === true && (
				<RatePrice minRate={ rate.min } maxRate={ rate.max } />
			) }
		</Radio>
	);
};

const ShippingSelector = ( {
	checked,
	rate,
	showPrice,
	showIcon,
	toggleText,
	setAttributes,
}: {
	checked: string;
	rate: minMaxPrices;
	showPrice: boolean;
	showIcon: boolean;
	toggleText: string;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ) => {
	const Price =
		rate.min === undefined ? (
			<span className="wc-block-checkout__shipping-method-option-price">
				{ __( 'calculated with an address', 'woocommerce' ) }
			</span>
		) : (
			<RatePrice minRate={ rate.min } maxRate={ rate.max } />
		);

	return (
		<Radio
			value="shipping"
			className={ classnames(
				'wc-block-checkout__shipping-method-option',
				{
					'wc-block-checkout__shipping-method-option--selected':
						checked === 'shipping',
				}
			) }
		>
			{ showIcon === true && (
				<Icon
					icon={ shipping }
					size={ 28 }
					className="wc-block-checkout__shipping-method-option-icon"
				/>
			) }
			<RichText
				value={ toggleText }
				placeholder={ defaultShippingText }
				tagName="span"
				className="wc-block-checkout__shipping-method-option-title"
				onChange={ ( value ) =>
					setAttributes( { shippingText: value } )
				}
				__unstableDisableFormats
				preserveWhiteSpace
			/>
			{ showPrice === true && Price }
		</Radio>
	);
};

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		title: string;
		description: string;
		showStepNumber: boolean;
		allowCreateAccount: boolean;
		localPickupText: string;
		shippingText: string;
		showPrice: boolean;
		showIcon: boolean;
		className: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element | null => {
	useEffect( () => {
		const localPickupTitle = getSetting< string >(
			'localPickupText',
			attributes.localPickupText
		);
		setAttributes( { localPickupText: localPickupTitle } );
		// Disable the exhaustive deps rule because we only want to run this on first mount to set the attribute, not
		// each time the attribute changes.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ setAttributes ] );
	const { setPrefersCollection } = useDispatch( CHECKOUT_STORE_KEY );
	const { prefersCollection } = useSelect( ( select ) => {
		const checkoutStore = select( CHECKOUT_STORE_KEY );
		return {
			prefersCollection: checkoutStore.prefersCollection(),
		};
	} );
	const { showPrice, showIcon, className, localPickupText, shippingText } =
		attributes;
	const {
		shippingRates,
		needsShipping,
		hasCalculatedShipping,
		isCollectable,
	} = useShippingData();

	if (
		! needsShipping ||
		! hasCalculatedShipping ||
		! shippingRates ||
		! isCollectable ||
		! LOCAL_PICKUP_ENABLED
	) {
		return null;
	}

	const changeView = ( method: string ) => {
		if ( method === 'pickup' ) {
			setPrefersCollection( true );
		} else {
			setPrefersCollection( false );
		}
	};

	return (
		<FormStepBlock
			attributes={ attributes }
			setAttributes={ setAttributes }
			className={ classnames(
				'wc-block-checkout__shipping-method',
				className
			) }
		>
			<InspectorControls>
				<PanelBody title={ __( 'Appearance', 'woocommerce' ) }>
					<p className="wc-block-checkout__controls-text">
						{ __(
							'Choose how this block is displayed to your customers.',
							'woocommerce'
						) }
					</p>
					<ToggleControl
						label={ __( 'Show icon', 'woocommerce' ) }
						checked={ showIcon }
						onChange={ () =>
							setAttributes( {
								showIcon: ! showIcon,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Show costs', 'woocommerce' ) }
						checked={ showPrice }
						onChange={ () =>
							setAttributes( {
								showPrice: ! showPrice,
							} )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Shipping Methods', 'woocommerce' ) }>
					<p className="wc-block-checkout__controls-text">
						{ __(
							'Methods can be made managed in your store settings.',
							'woocommerce'
						) }
					</p>
					<ExternalLinkCard
						key={ 'shipping_methods' }
						href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping` }
						title={ __( 'Shipping', 'woocommerce' ) }
						description={ __(
							'Manage your shipping zones, methods, and rates.',
							'woocommerce'
						) }
					/>
					<ExternalLinkCard
						key={ 'pickup_location' }
						href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping&section=pickup_location` }
						title={ __( 'Pickup', 'woocommerce' ) }
						description={ __(
							'Allow customers to choose a local pickup location during checkout.',
							'woocommerce'
						) }
					/>
				</PanelBody>
			</InspectorControls>
			<RadioGroup
				id="shipping-method"
				className="wc-block-checkout__shipping-method-container"
				label="options"
				onChange={ changeView }
				checked={ prefersCollection ? 'pickup' : 'shipping' }
			>
				<ShippingSelector
					checked={ prefersCollection ? 'pickup' : 'shipping' }
					rate={ getShippingPrices(
						shippingRates[ 0 ]?.shipping_rates
					) }
					showPrice={ showPrice }
					showIcon={ showIcon }
					setAttributes={ setAttributes }
					toggleText={ shippingText }
				/>
				<LocalPickupSelector
					checked={ prefersCollection ? 'pickup' : 'shipping' }
					rate={ getLocalPickupPrices(
						shippingRates[ 0 ]?.shipping_rates
					) }
					showPrice={ showPrice }
					showIcon={ showIcon }
					setAttributes={ setAttributes }
					toggleText={ localPickupText }
				/>
			</RadioGroup>
			<AdditionalFields block={ innerBlockAreas.SHIPPING_METHOD } />
		</FormStepBlock>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<AdditionalFieldsContent />
		</div>
	);
};
