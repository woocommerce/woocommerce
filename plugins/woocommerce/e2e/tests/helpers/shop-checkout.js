exports.ShopCheckoutHelper = class ShopCheckoutHelper {
	constructor( page ) {
		this.page = page;
	}

	async fillBillingInfo( email ) {
		await this.page.fill( '#billing_first_name', 'John' );
		await this.page.fill( '#billing_last_name', 'Doe' );
		await this.page.selectOption( '#billing_country', 'US' );
		await this.page.fill( '#billing_address_1', 'addr 1' );
		await this.page.fill( '#billing_address_2', 'addr 2' );
		await this.page.fill( '#billing_city', 'San Francisco' );
		await this.page.selectOption( '#billing_state', 'CA' );
		await this.page.fill( '#billing_postcode', '90147' );
		await this.page.fill( '#billing_phone', '555 555-5555' );
		await this.page.fill( '#billing_email', email );
	}
};
