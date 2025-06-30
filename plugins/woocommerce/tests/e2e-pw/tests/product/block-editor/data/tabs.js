/*
 * Tabs (groups) define the main menu (header) of the product edit page.
 * Each tab has a name and a note text that is displayed below the tab name.
 */
const tabs = [
	{
		name: 'General',
		noteText:
			"This product has options, such as size or color. You can manage each variation's images, downloads, and other details individually.",
	},
	{
		name: 'Inventory',
		noteText:
			"This product has options, such as size or color. You can now manage each variation's inventory and other details individually.",
	},
	{
		name: 'Shipping',
		noteText:
			"This product has options, such as size or color. You can now manage each variation's shipping settings and other details individually.",
	},
];

module.exports = tabs;
