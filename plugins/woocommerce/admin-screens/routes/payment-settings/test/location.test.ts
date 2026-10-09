/**
 * External dependencies
 */
import type { Form } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { resolveLocation, subPageFieldId } from '../location';

const page = { type: 'panel', openAs: { type: 'page' } };

const makeForm = ( advancedLayout: unknown = page ) =>
	( {
		layout: { type: 'regular' as const },
		fields: [
			{ id: 'payments', label: 'Payments', children: [ 'enabled' ] },
			{
				id: 'statement',
				label: 'Bank statement',
				children: [
					{
						id: 'advanced',
						label: 'Advanced settings',
						layout: advancedLayout,
						children: [
							'descriptor',
							{
								id: 'rules',
								label: 'Descriptor rules',
								layout: page,
								children: [ 'suffix' ],
							},
						],
					},
				],
			},
		],
	} ) as Form;

const form = makeForm();

describe( 'resolveLocation', () => {
	it( 'replaces sub-pages on the main page with a link field', () => {
		const location = resolveLocation( form );

		expect( location?.subPages ).toEqual( [] );
		expect( location?.form.fields ).toEqual( [
			{ id: 'payments', label: 'Payments', children: [ 'enabled' ] },
			{
				id: 'statement',
				label: 'Bank statement',
				children: [ subPageFieldId( 'advanced' ) ],
			},
		] );
		expect( location?.subPageLinks ).toEqual( [
			{ id: 'advanced', label: 'Advanced settings' },
		] );
	} );

	it( 'covers only the fields on the current page', () => {
		expect( resolveLocation( form )?.fieldIds ).toEqual( [ 'enabled' ] );
		expect( resolveLocation( form, 'advanced' )?.fieldIds ).toEqual( [
			'descriptor',
		] );
		expect( resolveLocation( form, 'advanced/rules' )?.fieldIds ).toEqual( [
			'suffix',
		] );
	} );

	it( 'counts a field given with its own layout', () => {
		const location = resolveLocation( {
			fields: [
				{
					id: 'support',
					label: 'Support',
					children: [
						{
							id: 'support_description',
							layout: {
								type: 'regular',
								labelPosition: 'none',
							},
						},
						'email',
					],
				},
			],
		} );

		expect( location?.fieldIds ).toEqual( [
			'support_description',
			'email',
		] );
	} );

	it( 'drops a sub-page without a button, since the extension links to it', () => {
		const location = resolveLocation(
			makeForm( {
				type: 'panel',
				openAs: { type: 'page', button: false },
			} )
		);

		expect( location?.form.fields?.[ 1 ] ).toEqual( {
			id: 'statement',
			label: 'Bank statement',
			children: [],
		} );
		expect( location?.subPageLinks ).toEqual( [] );
	} );

	it( 'shows a sub-page on its own path, with links to the sub-pages inside it', () => {
		const location = resolveLocation( form, 'advanced' );

		expect( location?.subPages.map( ( group ) => group.id ) ).toEqual( [
			'advanced',
		] );
		expect( location?.form.fields ).toEqual( [
			'descriptor',
			subPageFieldId( 'rules' ),
		] );
	} );

	it( 'opens a sub-page inside another one', () => {
		const location = resolveLocation( form, 'advanced/rules' );

		expect( location?.subPages.map( ( group ) => group.id ) ).toEqual( [
			'advanced',
			'rules',
		] );
		expect( location?.form.fields ).toEqual( [ 'suffix' ] );
	} );

	it.each( [
		[ 'no layout', undefined ],
		[ 'a panel that opens as a modal', { type: 'panel', openAs: 'modal' } ],
		[ 'a card', { type: 'card' } ],
	] )( 'keeps a group with %s inline', ( _, layout ) => {
		const location = resolveLocation( {
			fields: [
				'enabled',
				{
					id: 'advanced',
					label: 'Advanced',
					layout,
					children: [ 'descriptor' ],
				},
			],
		} as Form );

		expect( location?.form.fields ).toHaveLength( 2 );
		expect( location?.subPageLinks ).toEqual( [] );
		expect( location?.fieldIds ).toEqual( [ 'enabled', 'descriptor' ] );
	} );

	it.each( [
		[ 'an unknown sub-page', 'missing' ],
		[ 'a group that is not a sub-page', 'statement' ],
		[ 'a nested sub-page without its parent', 'rules' ],
		[ 'an unknown segment', 'advanced/rules/extra' ],
	] )( 'returns nothing for %s', ( _, path ) => {
		expect( resolveLocation( form, path ) ).toBeUndefined();
	} );
} );
