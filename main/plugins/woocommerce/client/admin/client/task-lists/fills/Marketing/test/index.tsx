/**
 * External dependencies
 */
import { Extension } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { transformExtensionToPlugin, getMarketingExtensionLists } from '..';

const basicPlugins: Extension[] = [
	{
		key: 'basic-plugin',
		name: 'Basic Plugin',
		description: 'Basic plugin description',
		manage_url: '#',
		image_url: 'basic.jpeg',
		is_built_by_wc: true,
		is_visible: true,
	},
];

const reachPlugins: Extension[] = [
	{
		key: 'reach-plugin',
		name: 'Reach Plugin',
		description: 'Reach plugin description',
		manage_url: '#',
		image_url: 'reach.jpeg',
		is_built_by_wc: false,
		is_visible: true,
	},
];

const growPlugins: Extension[] = [
	{
		key: 'grow-plugin',
		name: 'Grow Plugin',
		description: 'Grow plugin description',
		manage_url: '#',
		image_url: 'grow.jpeg',
		is_built_by_wc: false,
		is_visible: true,
	},
	{
		key: 'grow-plugin-two:extra',
		name: 'Grow Plugin 2',
		description: 'Grow plugin 2 description',
		manage_url: '#',
		image_url: 'grow2.jpeg',
		is_built_by_wc: false,
		is_visible: true,
	},
];

const extensionLists = [
	{
		key: 'obw/basics',
		plugins: basicPlugins,
		title: 'Basics',
	},
	{
		key: 'task-list/reach',
		plugins: reachPlugins,
		title: 'Reach',
	},
	{
		key: 'task-list/grow',
		plugins: growPlugins,
		title: 'Grow',
	},
];

describe( 'transformExtensionToPlugin', () => {
	test( 'should return the formatted extension', () => {
		const plugin = transformExtensionToPlugin( basicPlugins[ 0 ], [], [] );
		expect( plugin ).toEqual( {
			description: 'Basic plugin description',
			slug: 'basic-plugin',
			imageUrl: 'basic.jpeg',
			isActive: false,
			isInstalled: false,
			isBuiltByWC: true,
			manageUrl: '#',
			name: 'Basic Plugin',
		} );
	} );

	test( 'should get the plugin slug when a colon exists', () => {
		const plugin = transformExtensionToPlugin( growPlugins[ 1 ], [], [] );
		expect( plugin.slug ).toEqual( 'grow-plugin-two' );
	} );

	test( 'should mark the plugin as active when in the active plugins', () => {
		const plugin = transformExtensionToPlugin(
			basicPlugins[ 0 ],
			[ 'basic-plugin' ],
			[]
		);
		expect( plugin.isActive ).toBeTruthy();
	} );

	test( 'should mark the plugin as installed when in the installed plugins', () => {
		const plugin = transformExtensionToPlugin(
			basicPlugins[ 0 ],
			[],
			[ 'basic-plugin' ]
		);
		expect( plugin.isInstalled ).toBeTruthy();
	} );
} );

describe( 'getMarketingExtensionLists', () => {
	test( 'should only return the allowed lists', () => {
		const [ , lists ] = getMarketingExtensionLists(
			extensionLists,
			[],
			[]
		);

		expect( lists.length ).toBe( 2 );
		expect( lists[ 0 ].key ).toBe( 'task-list/grow' );
		expect( lists[ 1 ].key ).toBe( 'task-list/reach' );
	} );

	test( 'should separate installed plugins', () => {
		const [ installed ] = getMarketingExtensionLists(
			extensionLists,
			[],
			[ 'grow-plugin' ]
		);

		expect( installed.length ).toBe( 1 );
		expect( installed[ 0 ].slug ).toBe( 'grow-plugin' );
	} );

	test( 'should not include installed plugins in the extensions list', () => {
		const [ , lists ] = getMarketingExtensionLists(
			extensionLists,
			[],
			[ 'grow-plugin' ]
		);

		expect( lists[ 1 ].plugins?.length ).toBe( 1 );
	} );

	test( 'should only include allowed list plugins in the installed list', () => {
		const [ installed ] = getMarketingExtensionLists(
			extensionLists,
			[],
			[ 'basic-plugin' ]
		);

		expect( installed.length ).toBe( 0 );
	} );
} );
