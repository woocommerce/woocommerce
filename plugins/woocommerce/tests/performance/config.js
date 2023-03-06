/* eslint-disable no-undef */
export const base_url = __ENV.URL || 'http://localhost:8086';
export const base_host = __ENV.HOST || 'localhost:8086';

export const admin_username = __ENV.A_USER || 'admin';
export const admin_password = __ENV.A_PW || 'password';
export const admin_acc_login = __ENV.A_ACC_LOGIN || false;

export const customer_username =
	__ENV.C_USER || 'customer@woocommercecoree2etestsuite.com';
export const customer_password = __ENV.C_PW || 'password';
export const customer_user_id = __ENV.C_UID || '2';

export const cot_status = __ENV.COT || false;

export const admin_orders_base_url = 'edit.php?post_type=shop_order';
export const cot_admin_orders_base_url = 'admin.php?page=wc-orders';

export const addresses_customer_billing_first_name = 'John';
export const addresses_customer_billing_last_name = 'Doe';
export const addresses_customer_billing_company = 'Automattic';
export const addresses_customer_billing_country = 'US';
export const addresses_customer_billing_address_1 = 'addr 1';
export const addresses_customer_billing_address_2 = 'addr 2';
export const addresses_customer_billing_city = 'San Francisco';
export const addresses_customer_billing_state = 'CA';
export const addresses_customer_billing_postcode = '94107';
export const addresses_customer_billing_phone = '123456789';
export const addresses_customer_billing_email = 'john.doe@example.com';

export const addresses_guest_billing_first_name = 'John';
export const addresses_guest_billing_last_name = 'Doe';
export const addresses_guest_billing_company = 'Automattic';
export const addresses_guest_billing_country = 'US';
export const addresses_guest_billing_address_1 = 'addr 1';
export const addresses_guest_billing_address_2 = 'addr 2';
export const addresses_guest_billing_city = 'San Francisco';
export const addresses_guest_billing_state = 'CA';
export const addresses_guest_billing_postcode = '94107';
export const addresses_guest_billing_phone = '123456789';
export const addresses_guest_billing_email = 'john.doe@example.com';

export const payment_method = 'cod';

export const product_sku = __ENV.P_SKU || 'woo-beanie';
export const product_url = __ENV.P_URL || 'beanie';
export const product_id = __ENV.P_ID || '13';
export const product_search_term = __ENV.P_TERM || 'beanie';
export const product_category = __ENV.P_CAT || 'Accessories';

export const coupon_code = __ENV.P_COUPON || 'testing';

export const add_product_title = 'Test Product';
export const add_product_regular_price = '12';

export const think_time_min = '1';
export const think_time_max = '4';
