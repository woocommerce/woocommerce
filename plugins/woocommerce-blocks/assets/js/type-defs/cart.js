/**
 * @typedef {Object} CartTotalItem
 *
 * @property {string} label  Label for total item
 * @property {number} value  The value of the total item
 */

/**
 * @typedef {Object} CartShippingOption
 *
 * @property {string} name          Name of the shipping rate
 * @property {string} description   Description of the shipping rate.
 * @property {string} price         Price of the shipping rate (in subunits)
 * @property {string} rate_id       The ID of the shipping rate.
 * @property {string} delivery_time The delivery time of the shipping rate
 */

/**
 * @typedef {Object} CartShippingAddress
 *
 * @property {string} first_name First name of recipient
 * @property {string} last_name  Last name of recipient
 * @property {string} company    Company name for the shipping address
 * @property {string} address_1  First line of the shipping address
 * @property {string} address_2  Second line of the shipping address
 * @property {string} city       The city of the shipping address
 * @property {string} state      The state of the shipping address (ISO or name)
 * @property {string} postcode   The postal or zip code for the shipping address
 * @property {string} country    The country for the shipping address (ISO)
 */

/**
 * @typedef {Object} CartBillingAddress
 *
 * @property {string} first_name First name of billing customer
 * @property {string} last_name  Last name of billing customer
 * @property {string} company    Company name for the billing address
 * @property {string} address_1  First line of the billing address
 * @property {string} address_2  Second line of the billing address
 * @property {string} city       The city of the billing address
 * @property {string} state      The state of the billing address (ISO or name)
 * @property {string} postcode   The postal or zip code for the billing address
 * @property {string} country    The country for the billing address (ISO)
 * @property {string} phone      The phone number for the billing address
 * @property {string} email      The email for the billing address
 */

/**
 * @typedef {Object} CartData
 *
 * @property {Array}      coupons       Coupons applied to cart.
 * @property {Array}      shippingRates array of selected shipping rates
 * @property {Array}      items         Items in the cart.
 * @property {number}     itemsCount    Number of items in the cart.
 * @property {number}     itemsWeight   Weight of items in the cart.
 * @property {boolean}    needsShipping True if the cart needs shipping.
 * @property {CartTotals} totals        Cart total amounts.
 */

/**
 * @typedef {Object} CartTotals
 *
 * @property {string} currency_code               Currency code (in ISO format)
 *                                                for returned prices.
 * @property {string} currency_symbol             Currency symbol for the
 *                                                currency which can be used to
 *                                                format returned prices.
 * @property {number} currency_minor_unit         Currency minor unit (number of
 *                                                digits after the decimal
 *                                                separator) for returned
 *                                                prices.
 * @property {string} currency_decimal_separator  Decimal separator for the
 *                                                currency which can be used to
 *                                                format returned prices.
 * @property {string} currency_thousand_separator Thousand separator for the
 *                                                currency which can be used to
 *                                                format returned prices.
 * @property {string} currency_prefix             Price prefix for the currency
 *                                                which can be used to format
 *                                                returned prices.
 * @property {string} currency_suffix             Price prefix for the currency
 *                                                which can be used to format
 *                                                returned prices.
 * @property {number} total_items                 Total price of items in the
 *                                                cart.
 * @property {number} total_items_tax             Total tax on items in the
 *                                                cart.
 * @property {number} total_fees                  Total price of any applied
 *                                                fees.
 * @property {number} total_fees_tax              Total tax on fees.
 * @property {number} total_discount              Total discount from applied
 *                                                coupons.
 * @property {number} total_discount_tax          Total tax removed due to
 *                                                discount from applied coupons.
 * @property {number} total_shipping              Total price of shipping.
 * @property {number} total_shipping_tax          Total tax on shipping.
 * @property {number} total_price                 Total price the customer will
 *                                                pay.
 * @property {number} total_tax                   Total tax applied to items and
 *                                                shipping.
 * @property {Array}  tax_lines                   Lines of taxes applied to
 *                                                items and shipping.
 */

export {};
