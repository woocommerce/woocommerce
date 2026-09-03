export type Subscription = {
	product_key: string;
	product_id: number;
	product_name: string;
	product_url: string;
	product_icon: string;
	product_slug: string;
	product_type: string;
	documentation_url: string;
	zip_slug: string;
	key_type: string;
	key_type_label: string;
	autorenew: boolean;
	connections: string[];
	legacy_connections: string[];
	shares: SubscriptionShare[];
	lifetime: boolean;
	expires: number;
	expired: boolean;
	expiring: boolean;
	sites_max: number;
	sites_active: number;
	maxed: boolean;
	order_id: number;
	product_keys_all: string[];
	product_status: string;
	active: boolean;
	local: SubscriptionLocal;
	has_updates: boolean;
	version: string;
	subscription_installed: boolean;
	subscription_available: boolean;
	is_installable: boolean;
	included_in_host_plan?: boolean;
	is_agency?: boolean;
	is_shared: boolean;
	owner_email: string;
	has_changelog: boolean;
};

export interface SubscriptionLocal {
	installed: boolean;
	installable: boolean;
	active: boolean;
	version: string;
	type: string;
	slug: string;
	path: string;
	/** Whether WordPress will auto-update this plugin, mirroring the Plugins screen. */
	auto_update: boolean;
	/** Whether auto-updates can be changed from here: not forced, not disabled site-wide, and the user may update plugins. */
	auto_update_manageable: boolean;
}

export interface SubscriptionShare {
	share_id: string;
	product_key: string;
	user_id: string;
	subscription_item_id: string;
	status: string;
	created: string;
}

export enum StatusLevel {
	Warning = 'warning',
	Error = 'error',
	Info = 'info',
}

export type MySubscriptionsTable = 'installed' | 'available';
