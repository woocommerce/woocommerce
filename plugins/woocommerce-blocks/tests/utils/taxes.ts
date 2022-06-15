/**
 * External dependencies
 */
import { withRestApi } from '@woocommerce/e2e-utils';
import type { TaxRate, ProductResponseItem } from '@woocommerce/types';

export async function showTaxes( onoff: boolean ): Promise< void > {
	await withRestApi.updateSettingOption(
		'general',
		'woocommerce_calc_taxes',
		{ value: onoff ? 'yes' : 'no' }
	);
}

export function getExpectedTaxes(
	taxRates: Array< TaxRate >,
	countryCode: string,
	products: Array< Partial< ProductResponseItem > > = []
): Array< { label: string; value: string } > {
	const taxRatesForCountry = taxRates.filter(
		( taxRate ) => taxRate.country === countryCode
	);

	const total = products.reduce(
		( previous, current ) =>
			parseFloat( previous.regular_price ) +
			parseFloat( current.regular_price ),
		{ regular_price: 0 }
	);

	return taxRatesForCountry.map( ( taxRate ) => {
		const taxCalc = (
			parseFloat( total ) *
			( parseFloat( taxRate.rate ) / 100 )
		).toFixed( 2 );

		return { label: taxRate.name, value: `$${ taxCalc }` };
	} );
}

export async function getTaxesFromCurrentPage(): Promise<
	Array< {
		label: string;
		value: string;
	} >
> {
	return await page.$$eval( '.wc-block-components-totals-taxes', ( nodes ) =>
		nodes.map( ( node ) => {
			const label = node.querySelector(
				'.wc-block-components-totals-item__label'
			)?.innerHTML;
			const value = node.querySelector(
				'.wc-block-components-totals-item__value'
			)?.innerHTML;
			return { label, value };
		} )
	);
}

export async function getTaxesFromOrderSummaryPage(
	taxRates: TaxRate
): Promise<
	Array< {
		label: string;
		value: string;
	} >
> {
	return await page.evaluate( ( taxRatesEval ) => {
		return Array.from(
			document.querySelectorAll(
				'.woocommerce-table--order-details > tfoot > tr'
			)
		)
			.filter( ( node ) => {
				const taxLabel =
					node.getElementsByTagName( 'th' )[ 0 ].innerHTML;
				return taxRatesEval.some(
					// We need to remove the ":" on the end of the string before we compare
					( taxRate ) => taxRate.name === taxLabel.slice( 0, -1 )
				);
			} )
			.map( ( node ) => {
				const label = node.getElementsByTagName( 'th' )[ 0 ].innerHTML;
				const value = node.getElementsByTagName( 'td' )[ 0 ].innerText;
				return {
					label: label.slice( 0, -1 ),
					value,
				};
			} );
	}, taxRates );
}
