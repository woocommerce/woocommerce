/**
 * External dependencies
 */
import {
	create,
	registerFormatType,
	unregisterFormatType,
	toHTMLString,
} from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import { insertPersonalizationTag } from '../rich-text-utils';

const TAG = '[woocommerce/customer-first-name]';
const OBJ = '￼';
const NBSP = ' ';
const LINK_TYPE = 'woocommerce-email-editor/link-shortcode';

const withCaret = ( html: string, start?: number, end?: number ) => ( {
	...create( { html } ),
	start,
	end,
} );

describe( 'insertPersonalizationTag', () => {
	beforeAll( () => {
		registerFormatType( 'woocommerce-email-editor/shortcode', {
			name: 'woocommerce-email-editor/shortcode',
			title: 'Personalization Tags',
			className: 'woocommerce-email-editor-personalization-tags',
			tagName: 'span',
			attributes: {},
			interactive: false,
			edit: () => null,
		} as never );
		registerFormatType( LINK_TYPE, {
			name: LINK_TYPE,
			title: 'Personalization Tags Link',
			className: 'woocommerce-email-editor-personalization-tags-link',
			tagName: 'a',
			attributes: {
				'data-link-href': 'data-link-href',
				contenteditable: 'contenteditable',
				style: 'style',
			},
			interactive: true,
			edit: null,
		} as never );
		registerFormatType( 'core/bold', {
			name: 'core/bold',
			title: 'Bold',
			tagName: 'strong',
			className: null,
			edit: () => null,
		} as never );
		registerFormatType( 'core/italic', {
			name: 'core/italic',
			title: 'Italic',
			tagName: 'em',
			className: null,
			edit: () => null,
		} as never );
	} );

	afterAll( () => {
		[
			'woocommerce-email-editor/shortcode',
			LINK_TYPE,
			'core/bold',
			'core/italic',
		].forEach( ( name ) => unregisterFormatType( name ) );
	} );

	it( 'inserts the tag at a collapsed caret', () => {
		const result = insertPersonalizationTag(
			withCaret( 'Hello world', 5, 5 ),
			TAG
		);
		expect( result.text ).toBe( `Hello${ OBJ }${ NBSP } world` );
		expect( toHTMLString( { value: result } ) ).toContain(
			`Hello<!--${ TAG }-->${ NBSP } world`
		);
	} );

	it( 'replaces a non-collapsed selection', () => {
		const result = insertPersonalizationTag(
			withCaret( 'Hello world', 0, 5 ),
			TAG
		);
		expect( result.text ).toBe( `${ OBJ }${ NBSP } world` );
	} );

	it( 'appends at the end when there is no selection', () => {
		const result = insertPersonalizationTag( withCaret( 'Hello' ), TAG );
		expect( result.text ).toBe( `Hello${ OBJ }${ NBSP }` );
	} );

	it( 'places the caret right after the inserted content', () => {
		const result = insertPersonalizationTag(
			withCaret( 'Hello world', 5, 5 ),
			TAG
		);
		expect( result.start ).toBe( 7 );
		expect( result.end ).toBe( 7 );
		expect( result.activeFormats ).toEqual( [] );
	} );

	it( 'inserts after bold text with formats registered', () => {
		const result = insertPersonalizationTag(
			withCaret( '<strong>Hi</strong> there', 2, 2 ),
			TAG
		);
		expect( result.text ).toBe( `Hi${ OBJ }${ NBSP } there` );
		expect( toHTMLString( { value: result } ) ).toContain(
			`<strong>Hi</strong><!--${ TAG }-->${ NBSP } there`
		);
	} );

	it( 'inserts a link only over the link text', () => {
		const result = insertPersonalizationTag(
			withCaret( '<strong>Hi</strong> there', 3, 3 ),
			TAG,
			'Jane'
		);
		expect( result.text ).toBe( 'Hi Janethere' );
		expect( result.activeFormats ).toEqual( [] );
		expect( result.start ).toBe( 7 );
		expect( result.end ).toBe( 7 );
		const links = result.formats.map( ( formats ) =>
			( formats || [] ).find( ( format ) => format.type === LINK_TYPE )
		);
		const linkedIndexes = links.flatMap( ( link, index ) =>
			link ? [ index ] : []
		);
		expect( linkedIndexes ).toEqual( [ 3, 4, 5, 6 ] );
		expect( links[ 3 ].attributes ).toEqual( {
			'data-link-href': TAG,
			contenteditable: 'false',
			style: 'text-decoration: underline;',
		} );
		expect( toHTMLString( { value: result } ) ).toMatch(
			new RegExp(
				`<a [^>]*data-link-href="\\[${ 'woocommerce/customer-first-name' }\\]"[^>]*>Jane</a>`
			)
		);
	} );
} );
