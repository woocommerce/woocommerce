/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	ToggleControl,
	__experimentalRadio as Radio,
	__experimentalRadioGroup as RadioGroup,
} from '@wordpress/components';
import { Icon, store, shipping } from '@wordpress/icons';
import {
	InspectorControls,
	useBlockProps,
	RichText,
} from '@wordpress/block-editor';
import { useShippingData } from '@woocommerce/base-context/hooks';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';
import type { CartShippingPackageShippingRate } from '@woocommerce/type-defs/cart';

/**
 * Internal dependencies
 */
import {
	FormStepBlock,
	AdditionalFields,
	AdditionalFieldsContent,
} from '../../form-step';
import {
	RatePrice,
	getLocalPickupStartingPrice,
	getShippingStartingPrice,
} from './shared';
import './style.scss';

const LocalPickupSelector = ( {
	checked,
	rate,
	showPrice,
	showIcon,
	toggleText,
	setAttributes,
}: {
	checked: string;
	rate: CartShippingPackageShippingRate;
	showPrice: boolean;
	showIcon: boolean;
	toggleText: string;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ) => {
	return (
		<Radio
			value="pickup"
			className={ classnames( 'wc-block-checkout__collection-item', {
				'wc-block-checkout__collection-item--selected':
					checked === 'pickup',
			} ) }
		>
			{ showIcon === true && (
				<Icon
					icon={ store }
					size={ 28 }
					className="wc-block-checkout__collection-item-icon"
				/>
			) }
			<RichText
				value={ toggleText }
				tagName="span"
				className="wc-block-checkout__collection-item-title"
				onChange={ ( value ) =>
					setAttributes( { localPickupText: value } )
				}
				__unstableDisableFormats
				preserveWhiteSpace
			/>
			{ showPrice === true && <RatePrice rate={ rate } /> }
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
	rate: CartShippingPackageShippingRate;
	showPrice: boolean;
	showIcon: boolean;
	toggleText: string;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ) => {
	const Price =
		rate === undefined ? (
			<span className="wc-block-checkout__collection-item-price">
				{ __(
					'calculated with an address',
					'woo-gutenberg-products-block'
				) }
			</span>
		) : (
			<RatePrice rate={ rate } />
		);

	return (
		<Radio
			value="shipping"
			className={ classnames( 'wc-block-checkout__collection-item', {
				'wc-block-checkout__collection-item--selected':
					checked === 'shipping',
			} ) }
		>
			{ showIcon === true && (
				<Icon
					icon={ shipping }
					size={ 28 }
					className="wc-block-checkout__collection-item-icon"
				/>
			) }
			<RichText
				value={ toggleText }
				tagName="span"
				className="wc-block-checkout__collection-item-title"
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
} ): JSX.Element => {
	const [ currentView, changeView ] = useState( 'shipping' );
	const { showPrice, showIcon, className, localPickupText, shippingText } =
		attributes;
	const { shippingRates } = useShippingData();
	const localPickupStartingPrice = getLocalPickupStartingPrice(
		shippingRates[ 0 ]?.shipping_rates
	);
	const shippingStartingPrice = getShippingStartingPrice(
		shippingRates[ 0 ]?.shipping_rates
	);
	return (
		<FormStepBlock
			attributes={ attributes }
			setAttributes={ setAttributes }
			className={ classnames(
				'wc-block-checkout__collection-method',
				className
			) }
		>
			<InspectorControls>
				<PanelBody
					title={ __( 'Appearance', 'woo-gutenberg-products-block' ) }
				>
					<p className="wc-block-checkout__controls-text">
						{ __(
							'Choose how this block is displayed to your customers.',
							'woo-gutenberg-products-block'
						) }
					</p>
					<ToggleControl
						label={ __(
							'Show icon',
							'woo-gutenberg-products-block'
						) }
						checked={ showIcon }
						onChange={ () =>
							setAttributes( {
								showIcon: ! showIcon,
							} )
						}
					/>
					<ToggleControl
						label={ __(
							'Show costs',
							'woo-gutenberg-products-block'
						) }
						checked={ showPrice }
						onChange={ () =>
							setAttributes( {
								showPrice: ! showPrice,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<RadioGroup
				id="collection-method"
				className="wc-block-checkout__collection-method-container"
				label="options"
				onChange={ changeView }
				checked={ currentView }
			>
				<ShippingSelector
					checked={ currentView }
					rate={ shippingStartingPrice }
					showPrice={ showPrice }
					showIcon={ showIcon }
					setAttributes={ setAttributes }
					toggleText={ shippingText }
				/>
				<LocalPickupSelector
					checked={ currentView }
					rate={ localPickupStartingPrice }
					showPrice={ showPrice }
					showIcon={ showIcon }
					setAttributes={ setAttributes }
					toggleText={ localPickupText }
				/>
			</RadioGroup>
			<AdditionalFields block={ innerBlockAreas.COLLECTION_METHOD } />
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
