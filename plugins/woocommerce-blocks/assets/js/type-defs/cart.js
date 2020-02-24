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

export {};
