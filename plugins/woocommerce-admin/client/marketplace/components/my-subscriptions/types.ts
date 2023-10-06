export type Subscription = {
	product_key: string;
	product_id: number;
	product_name: string;
	product_url: string;
	product_icon: string;
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
};

export interface SubscriptionLocal {
	installed: boolean;
	active: boolean;
	version: string;
}

export interface SubscriptionShare {
	share_id: string;
	product_key: string;
	user_id: string;
	subscription_item_id: string;
	status: string;
	created: string;
}
