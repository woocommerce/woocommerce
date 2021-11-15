/**
 * A standard tax rate.
 *
 * For more details on the tax rate properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#tax-rate-properties
 *
 */
const taxRate = {
	name: 'Standard Rate',
	rate: '10.0000',
	class: 'standard',
};

const getExampleTaxRate = () => {
	return taxRate;
};

module.exports = {
	getExampleTaxRate,
};
