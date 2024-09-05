/**
 * External dependencies
 */

import schema from '@wordpress/core-data';

export type UserPreferences = {
	activity_panel_inbox_last_read?: string;
	activity_panel_reviews_last_read?: string;
	android_app_banner_dismissed?: string;
	categories_report_columns?: string;
	coupons_report_columns?: string;
	customers_report_columns?: string;
	dashboard_chart_interval?: string;
	dashboard_chart_type?: string;
	dashboard_leaderboard_rows?: string;
	dashboard_sections?: string;
	help_panel_highlight_shown?: string;
	homepage_layout?: string;
	homepage_stats?: string;
	orders_report_columns?: string;
	products_report_columns?: string;
	revenue_report_columns?: string;
	task_list_tracked_started_tasks?: {
		[ key: string ]: number;
	};
	taxes_report_columns?: string;
	variable_product_tour_shown?: string;
	variable_product_block_tour_shown?: string;
	variations_report_columns?: string;
	local_attributes_notice_dismissed_ids?: number[];
	variable_items_without_price_notice_dismissed?: Record< number, string >;
	product_advice_card_dismissed?: {
		[ key: string ]: 'yes' | 'no';
	};
	launch_your_store_tour_hidden?: 'yes' | 'no' | '';
	coming_soon_banner_dismissed?: 'yes' | 'no' | '';
};

export type WoocommerceMeta = {
	[ key in keyof UserPreferences ]: string;
};

export type WCUser<
	T extends keyof schema.Schema.BaseUser< 'view' > = schema.Schema.ViewKeys.User
> = Pick<
	schema.Schema.BaseUser< 'view' >,
	schema.Schema.ViewKeys.User | T
> & {
	// https://github.com/woocommerce/woocommerce/blob/3eb1938f4a0d0a93c9bcaf2a904f96bd501177fc/plugins/woocommerce/src/Internal/Admin/WCAdminUser.php#L40-L58
	woocommerce_meta: WoocommerceMeta;
	is_super_admin: boolean;
};
