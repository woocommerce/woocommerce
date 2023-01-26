/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalWooProductSectionItem as WooProductSectionItem,
	__experimentalWooProductFieldItem as WooProductFieldItem,
	__experimentalProductSectionLayout as ProductSectionLayout,
	Link,
	useFormContext,
} from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { Product } from '@woocommerce/data';
import { useContext } from '@wordpress/element';
import { Card, CardBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	PricingListField,
	PricingSaleField,
	PricingTaxesClassField,
	PricingTaxesChargeField,
} from './index';
import { useProductHelper } from '../../use-product-helper';
import { PLUGIN_ID } from '../constants';
import { CurrencyContext } from '../../../lib/currency-context';

import './pricing-section.scss';

export type CurrencyInputProps = {
	prefix: string;
	className: string;
	sanitize: ( value: Product[ keyof Product ] ) => string;
	onFocus: ( event: React.FocusEvent< HTMLInputElement > ) => void;
	onKeyUp: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
};

type PricingSectionFillsType = {
	tabId: string;
	basicSectionId: string;
	taxesSectionId: string;
};

export const PricingSectionFills: React.FC< PricingSectionFillsType > = ( {
	tabId,
	basicSectionId,
	taxesSectionId,
} ) => {
	const { setValues, values } = useFormContext< Product >();
	const { sanitizePrice } = useProductHelper();

	const context = useContext( CurrencyContext );
	const { getCurrencyConfig } = context;
	const currencyConfig = getCurrencyConfig();

	const currencyInputProps: CurrencyInputProps = {
		prefix: currencyConfig.symbol,
		className: 'half-width-field components-currency-control',
		sanitize: ( value: Product[ keyof Product ] ) => {
			return sanitizePrice( String( value ) );
		},
		onFocus( event: React.FocusEvent< HTMLInputElement > ) {
			// In some browsers like safari .select() function inside
			// the onFocus event doesn't work as expected because it
			// conflicts with onClick the first time user click the
			// input. Using setTimeout defers the text selection and
			// avoid the unexpected behaviour.
			setTimeout(
				function deferSelection( element: HTMLInputElement ) {
					element.select();
				},
				0,
				event.currentTarget
			);
		},
		onKeyUp( event: React.KeyboardEvent< HTMLInputElement > ) {
			const name = event.currentTarget.name as keyof Pick<
				Product,
				'regular_price' | 'sale_price'
			>;
			const amount = Number.parseFloat(
				sanitizePrice( values[ name ] || '0' )
			);
			const step = Number( event.currentTarget.step || '1' );
			if ( event.code === 'ArrowUp' ) {
				setValues( {
					[ name ]: String( amount + step ),
				} as unknown as Product );
			}
			if ( event.code === 'ArrowDown' ) {
				setValues( {
					[ name ]: String( amount - step ),
				} as unknown as Product );
			}
		},
	};

	return (
		<>
			<WooProductSectionItem
				id={ basicSectionId }
				tabs={ [ { name: tabId, order: 1 } ] }
				pluginId={ PLUGIN_ID }
			>
				<ProductSectionLayout
					title={ __( 'Pricing', 'woocommerce' ) }
					description={
						<>
							<span>
								{ __(
									'Set a competitive price, put the product on sale, and manage tax calculations.',
									'woocommerce'
								) }
							</span>
							<Link
								className="woocommerce-form-section__header-link"
								href="https://woocommerce.com/posts/how-to-price-products-strategies-expert-tips/"
								target="_blank"
								type="external"
								onClick={ () => {
									recordEvent( 'add_product_pricing_help' );
								} }
							>
								{ __(
									'How to price your product: expert tips',
									'woocommerce'
								) }
							</Link>
						</>
					}
				>
					<Card>
						<CardBody>
							<WooProductFieldItem.Slot
								section={ basicSectionId }
							/>
						</CardBody>
					</Card>
					<Card>
						<CardBody>
							<WooProductFieldItem.Slot
								section={ taxesSectionId }
							/>
						</CardBody>
					</Card>
				</ProductSectionLayout>
			</WooProductSectionItem>
			<WooProductFieldItem
				id="pricing/list"
				sections={ [ { name: basicSectionId, order: 1 } ] }
				pluginId={ PLUGIN_ID }
			>
				<PricingListField currencyInputProps={ currencyInputProps } />
			</WooProductFieldItem>
			<WooProductFieldItem
				id="pricing/sale"
				sections={ [ { name: basicSectionId, order: 3 } ] }
				pluginId={ PLUGIN_ID }
			>
				<PricingSaleField currencyInputProps={ currencyInputProps } />
			</WooProductFieldItem>
			<WooProductFieldItem
				id="pricing/taxes/charge"
				sections={ [ { name: taxesSectionId, order: 1 } ] }
				pluginId={ PLUGIN_ID }
			>
				<PricingTaxesChargeField />
			</WooProductFieldItem>
			<WooProductFieldItem
				id="pricing/taxes/class"
				sections={ [ { name: taxesSectionId, order: 3 } ] }
				pluginId={ PLUGIN_ID }
			>
				<PricingTaxesClassField />
			</WooProductFieldItem>
		</>
	);
};
