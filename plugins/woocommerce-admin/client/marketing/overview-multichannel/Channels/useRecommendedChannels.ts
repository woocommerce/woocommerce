/**
 * Internal dependencies
 */
import { RecommendedChannel } from './types';

type UseRecommendedChannels = {
	loading: boolean;
	data: Array< RecommendedChannel >;
};

export const useRecommendedChannels = (): UseRecommendedChannels => {
	// TODO: call API here to get data.
	// The following are just dummy data for testing now.
	return {
		loading: false,
		data: [
			{
				title: 'Facebook for WooCommerce',
				description:
					'List your products and create ads on Facebook and Instagram.',
				url: 'https://woocommerce.com/products/facebook/?utm_source=marketingtab&utm_medium=product&utm_campaign=wcaddons',
				direct_install: true,
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/facebook.svg',
				product: 'facebook-for-woocommerce',
				plugin: 'facebook-for-woocommerce/facebook-for-woocommerce.php',
				categories: [ 'marketing' ],
				subcategories: [
					{ slug: 'sales-channels', name: 'Sales channels' },
				],
				tags: [
					{
						slug: 'built-by-woocommerce',
						name: 'Built by WooCommerce',
					},
				],
			},
			{
				title: 'Amazon, eBay & Walmart Integration for WooCommerce',
				description:
					'Get the official Amazon, eBay and Walmart extension and create, sync and manage multichannel listings directly from WooCommerce.',
				url: 'https://woocommerce.com/products/amazon-ebay-integration/?utm_source=marketingtab&utm_medium=product&utm_campaign=wcaddons',
				direct_install: false,
				icon: 'https://woocommerce.com/wp-content/plugins/wccom-plugins/marketing-tab-rest-api/icons/amazon-ebay.svg',
				product: 'amazon-ebay-integration',
				plugin: 'woocommerce-amazon-ebay-integration/woocommerce-amazon-ebay-integration.php',
				categories: [ 'marketing' ],
				subcategories: [
					{ slug: 'sales-channels', name: 'Sales channels' },
				],
				tags: [],
			},
		],
	};
};
