/*
The data returned by /reports/products will contain a product_id ref
And as such will require data layer logic for products to fully build the table

[
  {
    "product_id": 20,
    "items_sold": 100,
    "net_revenue": 999.99,
		"orders_count": 54,
		"name": 'Product name',
    "_links": {
      "product": [
        {
          "href": "https://example.com/wp-json/wc-analytics/products/20"
        }
      ]
    }
  }
]

*/
export default [
	{
		product_id: 20,
		items_sold: 123456789,
		net_revenue: 9876543.215,
		orders_count: 54,
		name: 'awesome shirt',
		_links: {
			product: [
				{
					href: 'https://example.com/wp-json/wc-analytics/products/20',
				},
			],
		},
	},
	{
		product_id: 22,
		items_sold: 90,
		net_revenue: 875,
		orders_count: 41,
		name: 'awesome pants',
		_links: {
			product: [
				{
					href: 'https://example.com/wp-json/wc-analytics/products/22',
				},
			],
		},
	},
	{
		product_id: 23,
		items_sold: 55,
		net_revenue: 75.75,
		orders_count: 28,
		name: 'awesome hat',
		_links: {
			product: [
				{
					href: 'https://example.com/wp-json/wc-analytics/products/23',
				},
			],
		},
	},
	{
		product_id: 24,
		items_sold: 10,
		net_revenue: 24.5,
		orders_count: 14,
		name: 'awesome sticker',
		_links: {
			product: [
				{
					href: 'https://example.com/wp-json/wc-analytics/products/24',
				},
			],
		},
	},
	{
		product_id: 25,
		items_sold: 1,
		net_revenue: 0.99,
		orders_count: 1,
		name: 'awesome button',
		_links: {
			product: [
				{
					href: 'https://example.com/wp-json/wc-analytics/products/25',
				},
			],
		},
	},
];
