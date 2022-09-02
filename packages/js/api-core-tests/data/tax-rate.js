/**
 * A standard tax rate.
 *
 * For more details on the tax rate properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#tax-rate-properties
 *
 */
const standardTaxRate = {
	name: 'Standard Rate',
	rate: '10.0000',
	class: 'standard',
};

const reducedTaxRate = {
	name: 'Reduced Rate',
	rate: '1.0000',
	class: 'reduced-rate',
};

const zeroTaxRate = {
	name: 'Zero Rate',
	rate: '0.0000',
	class: 'zero-rate',
};

const getTaxRateExamples = () => {
	return { standardTaxRate, reducedTaxRate, zeroTaxRate };
};

module.exports = {
	getTaxRateExamples,
};
