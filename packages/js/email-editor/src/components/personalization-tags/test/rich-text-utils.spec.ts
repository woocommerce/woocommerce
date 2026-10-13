/* global Node, HTMLElement */
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
import {
	getCursorPosition,
	insertPersonalizationTag,
} from '../rich-text-utils';

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

describe( 'getCursorPosition', () => {
	const COMMENT = `<span contenteditable="false" data-rich-text-comment="${ TAG }"><span>${ TAG }</span></span>`;
	const LINK =
		'<a data-link-href="[x]" contenteditable="false" style="text-decoration: underline;">link</a>';
	const FULL = `${ COMMENT }&nbsp;text <strong>bo<em>ld</em></strong> after ${ LINK } tail`;
	const created: HTMLElement[] = [];

	const makeField = ( innerHTML: string ) => {
		const el = document.createElement( 'p' );
		el.innerHTML = innerHTML;
		document.body.appendChild( el );
		created.push( el );
		return { el, ref: { current: el } };
	};

	const select = (
		startNode: Node,
		startOffset: number,
		endNode: Node = startNode,
		endOffset: number = startOffset
	) => {
		const range = document.createRange();
		range.setStart( startNode, startOffset );
		range.setEnd( endNode, endOffset );
		const selection = document.defaultView.getSelection();
		selection.removeAllRanges();
		selection.addRange( range );
	};

	const textNodeAfter = ( el: HTMLElement, index: number ) =>
		el.childNodes[ index ];

	beforeAll( () => {
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
	} );

	afterAll( () => {
		[ 'core/bold', 'core/italic', LINK_TYPE ].forEach( ( name ) =>
			unregisterFormatType( name )
		);
	} );

	afterEach( () => {
		created.splice( 0 ).forEach( ( node ) => node.remove() );
		document.defaultView.getSelection().removeAllRanges();
	} );

	// Children of FULL: 0 comment, 1 " text ", 2 strong, 3 " after ", 4 a, 5 " tail".
	it.each( [
		[ 'after the comment', 1, 3, 4 ],
		[ 'after the bold element', 3, 3, 14 ],
		[ 'after the link', 5, 2, 24 ],
	] )( 'reads the offset %s', ( _name, childIndex, offset, expected ) => {
		const { el, ref } = makeField( FULL );
		select( textNodeAfter( el, childIndex ), offset );
		expect( getCursorPosition( ref ) ).toEqual( {
			start: expected,
			end: expected,
		} );
	} );

	it( 'reads the offset inside bold and nested italic text', () => {
		const { el, ref } = makeField( FULL );
		const strong = el.childNodes[ 2 ];
		select( strong.childNodes[ 0 ], 2 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 9, end: 9 } );
		select( strong.childNodes[ 1 ].childNodes[ 0 ], 1 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 10, end: 10 } );
	} );

	it( 'reads the offset after an italic-only lead', () => {
		const { el, ref } = makeField( '<em>lead</em> rest' );
		select( el.childNodes[ 1 ], 1 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 5, end: 5 } );
	} );

	it( 'reads element-container ranges', () => {
		const { el, ref } = makeField( FULL );
		select( el, 0 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 0, end: 0 } );
		select( el, 1 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 1, end: 1 } );
		select( el, 5 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 22, end: 22 } );
		select( el, el.childNodes.length );
		expect( getCursorPosition( ref ) ).toEqual( { start: 27, end: 27 } );
	} );

	it( 'treats a caret inside the comment as right after it', () => {
		const { el, ref } = makeField( FULL );
		select( el.childNodes[ 0 ].childNodes[ 0 ].childNodes[ 0 ], 5 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 1, end: 1 } );
	} );

	it( 'reads a non-collapsed selection', () => {
		const { el, ref } = makeField( FULL );
		select( el.childNodes[ 2 ].childNodes[ 0 ], 1, el.childNodes[ 5 ], 2 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 8, end: 24 } );
	} );

	it( 'counts a selected comment as one character', () => {
		const { el, ref } = makeField( FULL );
		select( el, 0, el.childNodes[ 1 ], 2 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 0, end: 3 } );
	} );

	it( 'clamps a selection that starts before the field', () => {
		const { el, ref } = makeField( FULL );
		const button = document.createElement( 'button' );
		document.body.insertBefore( button, el );
		created.push( button );
		select( button, 0, el.childNodes[ 3 ], 3 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 0, end: 14 } );
	} );

	it( 'clamps a selection that ends after the field', () => {
		const { el, ref } = makeField( FULL );
		const button = document.createElement( 'button' );
		document.body.appendChild( button );
		created.push( button );
		select( el.childNodes[ 2 ].childNodes[ 0 ], 1, button, 0 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 8, end: 27 } );
	} );

	it( 'returns null when the selection is outside the field', () => {
		const { ref } = makeField( FULL );
		const other = makeField( 'other' );
		select( other.el.childNodes[ 0 ], 2 );
		expect( getCursorPosition( ref ) ).toBeNull();
	} );

	it( 'returns null when there is no selection', () => {
		const { ref } = makeField( FULL );
		expect( getCursorPosition( ref ) ).toBeNull();
	} );

	it( 'keeps plain text before formatting correct', () => {
		const { el, ref } = makeField( 'abc <strong>bold</strong>' );
		select( el.childNodes[ 0 ], 2 );
		expect( getCursorPosition( ref ) ).toEqual( { start: 2, end: 2 } );
	} );
} );
