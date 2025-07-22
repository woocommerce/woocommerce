/**
 * External dependencies
 */
import { useContext, useState } from 'react';
import { CheckboxControl, Icon } from '@wordpress/components';
import CurrencyFactory, {
	CurrencyContext,
	SymbolPosition,
} from '@woocommerce/currency';
import { decodeEntities } from '@wordpress/html-entities';
import { range } from 'lodash';

/**
 * Internal dependencies
 */
import { LineItem } from '../../data/types';

type FulfillmentItemProps = {
	item: LineItem;
	quantity: number;
	currency: string;
	editMode: boolean;
	toggleItem: ( id: number, index: number, checked: boolean ) => void;
	isChecked: ( id: number, index: number ) => boolean;
	isIndeterminate: ( id: number ) => boolean;
};

export default function FulfillmentLineItem( {
	item,
	quantity,
	currency,
	editMode,
	toggleItem,
	isChecked,
	isIndeterminate,
}: FulfillmentItemProps ) {
	const [ itemExpanded, setItemExpanded ] = useState( false );

	const currencyContext = useContext( CurrencyContext );

	const storeCurrency = currencyContext.getCurrencyConfig();

	const getFormattedItemTotal = (
		total: number | string,
		orderCurrencyCode: string
	) => {
		if ( ! orderCurrencyCode ) {
			orderCurrencyCode = storeCurrency?.code || 'USD';
		}

		// If the order currency is the same as the store currency, we show the formatted amount.
		if ( storeCurrency && storeCurrency.code === orderCurrencyCode ) {
			return currencyContext.formatAmount( total );
		}

		const symbol =
			window.wcFulfillmentSettings.currency_symbols[ orderCurrencyCode ];

		if ( ! symbol ) {
			// This should never happen, but if it does, we'll just show the currency code.
			return `${ orderCurrencyCode }${ total }`;
		}

		// If the order currency is different from the store currency, we show the currency code and amount in the order currency.
		return CurrencyFactory( {
			...storeCurrency,
			symbol: decodeEntities( symbol ),
			symbolPosition: storeCurrency.symbolPosition as
				| SymbolPosition
				| undefined,
			code: orderCurrencyCode,
		} ).formatAmount( total );
	};

	return (
		<>
			<div
				className={ [
					'woocommerce-fulfillment-item-container',
					itemExpanded ? 'woocommerce-fulfillment-item-expanded' : '',
				].join( ' ' ) }
			>
				{ editMode && (
					<div className="woocommerce-fulfillment-item-checkbox">
						<CheckboxControl
							value={ item.id }
							checked={ isChecked( item.id, -1 ) }
							onChange={ ( value ) => {
								toggleItem( item.id, -1, value );
							} }
							indeterminate={ isIndeterminate( item.id ) }
							__nextHasNoMarginBottom
						/>
					</div>
				) }
				{ editMode && quantity > 1 && (
					<Icon
						icon={
							itemExpanded ? 'arrow-up-alt2' : 'arrow-down-alt2'
						}
						onClick={ () => {
							setItemExpanded( ! itemExpanded );
						} }
						size={ 16 }
					/>
				) }
				<div className="woocommerce-fulfillment-item-title">
					<div className="woocommerce-fulfillment-item-image-container">
						{ item.image?.src && (
							<img
								src={ item.image?.src }
								alt={ item.name }
								width={ 32 }
								height={ 32 }
								className="woocommerce-fulfillment-item-image"
							/>
						) }
					</div>
					<div className="woocommerce-fulfillment-item-name-sku">
						<div className="woocommerce-fulfillment-item-name">
							{ item.name }
						</div>
						{ item.sku && (
							<span className="woocommerce-fulfillment-item-sku">
								{ item.sku }
							</span>
						) }
					</div>
				</div>
				{ quantity > 1 && (
					<div className="woocommerce-fulfillment-item-quantity">
						{ 'x' + quantity }
					</div>
				) }
				<div className="woocommerce-fulfillment-item-price">
					{ getFormattedItemTotal(
						parseFloat( item.total ) * ( quantity / item.quantity ),
						currency
					) }
				</div>
			</div>
			{ editMode && itemExpanded && (
				<div className="woocommerce-fulfillment-item-expansion">
					{ range( quantity ).map( ( index ) => (
						<div
							key={ 'fulfillment-item-expansion-' + index }
							className="woocommerce-fulfillment-item-expansion-row"
						>
							{ editMode && (
								<div className="woocommerce-fulfillment-item-checkbox">
									<CheckboxControl
										name={ `fulfillment-item-${ item.id }-${ index }` }
										value={ item.id + '-' + index }
										checked={ isChecked( item.id, index ) }
										onChange={ ( value ) => {
											toggleItem( item.id, index, value );
										} }
										__nextHasNoMarginBottom
									/>
								</div>
							) }
							<div className="woocommerce-fulfillment-item-title">
								<div className="woocommerce-fulfillment-item-image-container">
									<img
										src={ item.image.src }
										alt={ item.name }
										width={ 32 }
										height={ 32 }
										className="woocommerce-fulfillment-item-image"
									/>
								</div>
								<div className="woocommerce-fulfillment-item-name-sku">
									<div className="woocommerce-fulfillment-item-name">
										{ item.name }
									</div>
									{ item.sku && (
										<span className="woocommerce-fulfillment-item-sku">
											{ item.sku }
										</span>
									) }
								</div>
							</div>
							<div className="woocommerce-fulfillment-item-price">
								{ getFormattedItemTotal(
									parseInt( item.total, 10 ) / item.quantity,
									currency
								) }
							</div>
						</div>
					) ) }
				</div>
			) }
		</>
	);
}
