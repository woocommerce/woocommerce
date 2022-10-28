/**
 * Internal dependencies
 */
import { getReportTableQuery, getRequestQuery } from './utils';

export type ReportItemsEndpoint =
	| 'customers'
	| 'products'
	| 'varitations'
	| 'orders'
	| 'categories'
	| 'taxes'
	| 'coupons'
	| 'stock'
	| 'downloads'
	| 'performance_indicator';

export type ReportStatEndpoint =
	| 'products'
	| 'variations'
	| 'revenue'
	| 'orders'
	| 'taxes'
	| 'coupons'
	| 'customers';

export type ReportQueryParams = ReturnType< typeof getReportTableQuery >;
export type ReportStatQueryParams = ReturnType< typeof getRequestQuery >;

export type CustomerReport = {
	/** Customer ID. */
	id: number;
	/** User ID. */
	user_id: number;
	/** Name. */
	name: string;
	/** Username. */
	username: string;
	/** Country / Region. */
	country: string;
	/** City. */
	city: string;
	/** Region. */
	state: string;
	/** Postal code. */
	postcode: string;
	/** Date registered. */
	date_registered: string | null;
	/** Date registered GMT. */
	date_registered_gmt: string | null;
	/** Date last active. */
	date_last_active: string | null;
	/** Date last active GMT. */
	date_last_active_gmt: string | null;
	/** Order count. */
	orders_count: number;
	/** Total spend. */
	total_spend: number;
	/** Avg order value. */
	avg_order_value: number;
};

export type ProductReport = {
	/** Product ID. */
	product_id: number;
	/** Number of items sold. */
	items_sold: number;
	/** Total Net sales of all items sold. */
	net_revenue: number;
	/** Number of orders product appeared in. */
	orders_count: number;
	extended_info: {
		/** Product name. */
		name: string;
		/** Product price. */
		price: number;
		/** Product image. */
		image: string;
		/** Product link. */
		permalink: string;
		/** Product category IDs. */
		category_ids: Array< number >;
		/** Product inventory status. */
		stock_status: string;
		/** Product inventory quantity. */
		stock_quantity: number;
		/** Product inventory threshold for low stock. */
		low_stock_amount: number;
		/** Product variations IDs. */
		variations: Array< number >;
		/** Product SKU. */
		sku: string;
	};
};

export type VariationReport = {
	/** Product ID. */
	product_id: number;
	/** Product ID. */
	variation_id: number;
	/** Number of items sold. */
	items_sold: number;
	/** Total Net sales of all items sold. */
	net_revenue: number;
	/** Number of orders product appeared in. */
	orders_count: number;
	extended_info: {
		/** Product name. */
		name: string;
		/** Product price. */
		price: number;
		/** Product image. */
		image: string;
		/** Product link. */
		permalink: string;
		/** Product attributes. */
		attributes: Array< {
			id: number;
			name: string;
			position: number;
			visible: boolean;
			variation: boolean;
			options: string[];
		} >;
		/** Product inventory status. */
		stock_status: string;
		/** Product inventory quantity. */
		stock_quantity: number;
		/** Product inventory threshold for low stock. */
		low_stock_amount: number;
	};
};

export type OrderReport = {
	/** Order ID. */
	order_id: number;
	/** Order Number. */
	order_number: string;
	/** Date the order was created, in the site's timezone. */
	date_created: string | null;
	/** Date the order was created, as GMT. */
	date_created_gmt: string | null;
	/** Order status. */
	status: string;
	/** Customer ID. */
	customer_id: number;
	/** Number of items sold. */
	num_items_sold: number;
	/** Net total revenue. */
	net_total: number;
	/** Net total revenue (formatted). */
	total_formatted: string;
	/** Returning or new customer. */
	customer_type: string;
	extended_info: {
		/** List of order product IDs, names, quantities. */
		products: Array< {
			id: string;
			name: string;
			quantity: string;
		} >;
		/** List of order coupons. */
		coupons: Array< {
			id: string;
			code: string;
		} >;
		/** Order customer information. */
		customer: {
			customer_id: number;
			user_id: string;
			username: string;
			first_name: string;
			last_name: string;
			email: string;
			date_last_active: string;
			date_registered: string;
			country: string;
			postcode: string;
			city: string;
			state: string;
		};
	};
};

export type CategoriesReport = {
	/** Category ID. */
	category_id: number;
	/** Amount of items sold. */
	items_sold: number;
	/** Total sales. */
	net_revenue: number;
	/** Number of orders. */
	orders_count: number;
	/** Amount of products. */
	products_count: number;
	extended_info: {
		/** Category name. */
		name: string;
	};
};

export type TaxesReport = {
	/** Tax rate ID. */
	tax_rate_id: number;
	/** Tax rate name. */
	name: string;
	/** Tax rate. */
	tax_rate: number;
	/** Country / Region. */
	country: string;
	/** State. */
	state: string;
	/** Priority. */
	priority: number;
	/** Total tax. */
	total_tax: number;
	/** Order tax. */
	order_tax: number;
	/** Shipping tax. */
	shipping_tax: number;
	/** Number of orders. */
	orders_count: number;
};

export type CouponReport = {
	/** Coupon ID. */
	coupon_id: number;
	/** Net discount amount. */
	amount: number;
	/** Number of orders. */
	orders_count: number;
	/** undefined */
	extended_info: {
		/** Coupon code. */
		code: string;
		/** Coupon creation date. */
		date_created: string | null;
		/** Coupon creation date in GMT. */
		date_created_gmt: string | null;
		/** Coupon expiration date. */
		date_expires: string | null;
		/** Coupon expiration date in GMT. */
		date_expires_gmt: string | null;
		/** Coupon discount type. */
		discount_type: 'percent' | 'fixed_cart' | 'fixed_product';
	};
};

export type StockReport = {
	/** Unique identifier for the resource. */
	id: number;
	/** Product parent ID. */
	parent_id: number;
	/** Product name. */
	name: string;
	/** Unique identifier. */
	sku: string;
	/** Stock status. */
	stock_status: 'instock' | 'outofstock' | 'onbackorder';
	/** Stock quantity. */
	stock_quantity: number;
	/** Manage stock. */
	manage_stock: boolean;
};

export type DownloadReport = {
	/** ID. */
	id: number;
	/** Product ID. */
	product_id: number;
	/** The date of the download, in the site's timezone. */
	date: string | null;
	/** The date of the download, as GMT. */
	date_gmt: string | null;
	/** Download ID. */
	download_id: string;
	/** File name. */
	file_name: string;
	/** File URL. */
	file_path: string;
	/** Order ID. */
	order_id: number;
	/** Order Number. */
	order_number: string;
	/** User ID for the downloader. */
	user_id: number;
	/** User name of the downloader. */
	username: string;
	/** IP address for the downloader. */
	ip_address: string;
};

export type PerformanceIndicatorReport = {
	/** Unique identifier for the resource. */
	stat:
		| 'revenue/total_sales'
		| 'revenue/net_revenue'
		| 'revenue/shipping'
		| 'revenue/refunds'
		| 'revenue/gross_sales'
		| 'orders/orders_count'
		| 'orders/avg_order_value'
		| 'products/items_sold'
		| 'variations/items_sold'
		| 'coupons/amount'
		| 'coupons/orders_count'
		| 'taxes/total_tax'
		| 'taxes/order_tax'
		| 'taxes/shipping_tax'
		| 'downloads/download_count';
	/** The specific chart this stat referrers to. */
	chart: string;
	/** Human readable label for the stat. */
	label: string;
};

export type ReportItemObject = {
	data:
		| CustomerReport
		| ProductReport
		| VariationReport
		| CouponReport
		| TaxesReport
		| StockReport
		| DownloadReport
		| OrderReport
		| CategoriesReport
		| PerformanceIndicatorReport;
	totalResults: number;
	totalPages: number;
};

export type ReportItemObjectInfer< T > = {
	data: T extends 'customers'
		? CustomerReport
		: T extends 'products'
		? ProductReport
		: T extends 'variations'
		? VariationReport
		: T extends 'orders'
		? OrderReport
		: T extends 'categories'
		? CategoriesReport
		: T extends 'taxes'
		? TaxesReport
		: T extends 'coupons'
		? CouponReport
		: T extends 'stock'
		? StockReport
		: T extends 'downloads'
		? DownloadReport
		: T extends 'performance_indicators'
		? PerformanceIndicatorReport
		: never;
	totalResults: number;
	totalPages: number;
};

type SubTotals = {
	/** Number of product items sold. */
	items_sold: number;
	/** Net sales. */
	net_revenue: number;
	/** Number of orders. */
	orders_count: number;
};

type Interval = {
	/** Type of interval. */
	interval: 'day' | 'week' | 'month' | 'year';
	/** The date the report start, in the site's timezone. */
	date_start: string | null;
	/** The date the report start, as GMT. */
	date_start_gmt: string | null;
	/** The date the report end, in the site's timezone. */
	date_end: string | null;
	/** The date the report end, as GMT. */
	date_end_gmt: string | null;
	/** Interval subtotals. */
	subtotals: SubTotals & {
		/** Reports data grouped by segment condition. */
		segments: Array< Segment >;
	};
};

export type Segment = {
	/** Segment identificator. */
	segment_id: number;
	/** Human readable segment label, either product or variation name. */
	segment_label: 'day' | 'week' | 'month' | 'year';
	/** Interval subtotals. */
	subtotals: SubTotals;
};

export type ProductReportStat = {
	totals: {
		/** Number of product items sold. */
		items_sold: number;
		/** Net sales. */
		net_revenue: number;
		/** Number of orders. */
		orders_count: number;
		/** Reports data grouped by segment condition. */
		segments: Array< Segment >;
	};
	intervals: Array< Interval >;
};

export type VariationsReportStat = {
	totals: {
		/** Number of variation items sold. */
		items_sold: number;
		/** Net sales. */
		net_revenue: number;
		/** Number of orders. */
		orders_count: number;
		/** Reports data grouped by segment condition. */
		segments: Array< Segment >;
	};
	intervals: Array< Interval >;
};

export type RevenueReportStat = {
	totals: {
		/** Total sales. */
		total_sales: number;
		/** Net sales. */
		net_revenue: number;
		/** Amount discounted by coupons. */
		coupons: number;
		/** Unique coupons count. */
		coupons_count: number;
		/** Total of shipping. */
		shipping: number;
		/** Total of taxes. */
		taxes: number;
		/** Total of returns. */
		refunds: number;
		/** Number of orders. */
		orders_count: number;
		/** Items sold. */
		num_items_sold: number;
		/** Products sold. */
		products: number;
		/** Gross sales. */
		gross_sales: number;
		/** Reports data grouped by segment condition. */
		segments: Array< Segment >;
	};
	intervals: Array< Interval >;
};

export type OrderReportStat = {
	totals: {
		/** Number of downloads. */
		download_count: number;
	};
	intervals: Array< Interval >;
};

export type TaxesReportStat = {
	totals: {
		/** Total tax. */
		total_tax: number;
		/** Order tax. */
		order_tax: number;
		/** Shipping tax. */
		shipping_tax: number;
		/** Number of orders. */
		orders_count: number;
		/** Amount of tax codes. */
		tax_codes: number;
		/** Reports data grouped by segment condition. */
		segments: Array< Segment >;
	};
	intervals: Array< Interval >;
};

export type CouponsReportStat = {
	totals: {
		/** Net discount amount. */
		amount: number;
		/** Number of coupons. */
		coupons_count: number;
		/** Number of discounted orders. */
		orders_count: number;
		/** Reports data grouped by segment condition. */
		segments: Array< Segment >;
	};
	intervals: Array< Interval >;
};

export type CustomersReportStat = {
	totals: {
		/** Number of customers. */
		customers_count: number;
		/** Average number of orders. */
		avg_orders_count: number;
		/** Average total spend per customer. */
		avg_total_spend: number;
		/** Average AOV per customer. */
		avg_avg_order_value: number;
	};
	intervals: Array< Interval >;
};

export type ReportStatObject = {
	data:
		| ProductReportStat
		| VariationsReportStat
		| RevenueReportStat
		| OrderReportStat
		| TaxesReportStat
		| CouponsReportStat
		| CustomersReportStat;
	totalResults: number;
	totalPages: number;
};

export type ReportStatObjectInfer< T > = {
	data: T extends 'products'
		? ProductReportStat
		: T extends 'variations'
		? VariationsReportStat
		: T extends 'revenue'
		? RevenueReportStat
		: T extends 'orders'
		? OrderReportStat
		: T extends 'taxes'
		? TaxesReportStat
		: T extends 'coupons'
		? CouponsReportStat
		: T extends 'customers'
		? CustomersReportStat
		: never;
	totalResults: number;
	totalPages: number;
};

export type ReportState = {
	itemErrors: {
		[ resourceName: string ]: unknown;
	};
	items: {
		[ resourceName: string ]: ReportItemObject;
	};
	statErrors: {
		[ resourceName: string ]: unknown;
	};
	stats: {
		[ resourceName: string ]: ReportStatObject;
	};
};
