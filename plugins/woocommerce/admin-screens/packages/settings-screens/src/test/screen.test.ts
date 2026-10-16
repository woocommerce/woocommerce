/**
 * Internal dependencies
 */
import { getScreen } from '../screen';

describe( 'getScreen', () => {
	it( 'names the entity after the area and screen ID', () => {
		const screen = getScreen( {
			id: 'example',
			area: 'payments',
			title: 'Example settings',
			restPath: '/example/v1/settings',
		} );

		expect( screen.entity ).toEqual( {
			kind: 'woo_settings_payments',
			name: 'example',
			baseURL: '/example/v1/settings',
		} );
	} );

	it( 'links a main screen back to the Payments list', () => {
		const { breadcrumbs } = getScreen( {
			id: 'example',
			area: 'payments',
			title: 'Example settings',
			restPath: '/example/v1/settings',
		} );

		expect( breadcrumbs.map( ( item ) => item.label ) ).toEqual( [
			'Payments',
			'Example settings',
		] );
		const url = new URL( breadcrumbs[ 0 ]?.to ?? '' );
		expect( url.searchParams.get( 'page' ) ).toBe( 'wc-settings' );
		expect( url.searchParams.get( 'tab' ) ).toBe( 'checkout' );
		expect( breadcrumbs[ 1 ]?.to ).toBeUndefined();
	} );

	it( 'links a sub-page to its parent screen through the router', () => {
		const { breadcrumbs } = getScreen( {
			id: 'example/advanced',
			area: 'payments',
			title: 'Advanced settings',
			restPath: '/example/v1/settings',
			parent: { id: 'example', title: 'Example settings' },
		} );

		expect( breadcrumbs[ 1 ] ).toEqual( {
			label: 'Example settings',
			to: '/settings/example',
		} );
	} );
} );
