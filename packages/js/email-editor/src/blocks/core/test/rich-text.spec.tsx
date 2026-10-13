/* global HTMLElement */
import * as React from 'react';

/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { create, registerFormatType, RichTextData } from '@wordpress/rich-text';
import type { RichTextValue } from '@wordpress/rich-text/build-types/types';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { extendRichTextFormats } from '../rich-text';

let capturedTagPopover: {
	onUpdate: ( o: string, u: string, anchor?: HTMLElement ) => void;
};
let capturedLinkPopover: {
	onUpdate: ( el: HTMLElement, tag: string, text: string ) => void;
};

jest.mock( '@wordpress/rich-text', () => ( {
	...jest.requireActual( '@wordpress/rich-text' ),
	registerFormatType: jest.fn(),
	unregisterFormatType: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	store: {},
	privateApis: {},
	BlockControls: ( { children }: { children: React.ReactNode } ) => children,
} ) );

jest.mock( '@wordpress/components', () => ( {
	ToolbarButton: ( {
		onClick,
		title,
	}: {
		onClick: () => void;
		title: string;
	} ) => <button onClick={ onClick }>{ title }</button>,
	ToolbarGroup: ( { children }: { children: React.ReactNode } ) => children,
} ) );

jest.mock( '../../../store', () => ( {
	storeName: 'email-editor',
} ) );

jest.mock( '../../../events', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock(
	'../../../components/personalization-tags/personalization-tags-modal',
	() => ( {
		PersonalizationTagsModal: ( {
			isOpened,
			onInsert,
		}: {
			isOpened: boolean;
			onInsert: ( tag: string, linkText: string | null ) => void;
		} ) =>
			isOpened ? (
				<>
					<button
						onClick={ () =>
							onInsert(
								'[woocommerce/customer-first-name]',
								null
							)
						}
					>
						insert-tag
					</button>
					<button
						onClick={ () =>
							onInsert(
								'[woocommerce/customer-first-name]',
								'Jane'
							)
						}
					>
						insert-link
					</button>
				</>
			) : null,
	} )
);

jest.mock(
	'../../../components/personalization-tags/personalization-tags-popover',
	() => ( {
		PersonalizationTagsPopover: ( props: unknown ) => {
			capturedTagPopover = props as typeof capturedTagPopover;
			return null;
		},
	} )
);

jest.mock(
	'../../../components/personalization-tags/personalization-tags-link-popover',
	() => ( {
		PersonalizationTagsLinkPopover: ( props: unknown ) => {
			capturedLinkPopover = props as typeof capturedLinkPopover;
			return null;
		},
	} )
);

const registerFormatTypeMock = registerFormatType as jest.Mock;

describe( 'extendRichTextFormats', () => {
	beforeEach( () => {
		registerFormatTypeMock.mockClear();
		extendRichTextFormats();
	} );

	it( 'registers the personalization tags format as non-interactive', () => {
		// Blocks using `withoutInteractiveFormatting` (e.g. the Button block)
		// drop interactive formats entirely, which would hide the
		// Personalization Tags toolbar button there.
		expect( registerFormatTypeMock ).toHaveBeenCalledWith(
			'woocommerce-email-editor/shortcode',
			expect.objectContaining( {
				interactive: false,
				edit: expect.any( Function ),
			} )
		);
	} );

	it( 'registers the link format as interactive', () => {
		// The link format renders a real `a` element and is applied
		// programmatically via `applyFormat`, so unlike the shortcode format it
		// has no toolbar `edit` component that the interactive flag could hide.
		expect( registerFormatTypeMock ).toHaveBeenCalledWith(
			'woocommerce-email-editor/link-shortcode',
			expect.objectContaining( { interactive: true, edit: null } )
		);
	} );
} );

type EditProps = {
	contentRef: { current: unknown };
	value: RichTextValue;
	onChange: ( value: RichTextValue ) => void;
};

describe( 'PersonalizationTagsButton insertion', () => {
	const TAG = '[woocommerce/customer-first-name]';
	const updateBlockAttributes = jest.fn();
	let Edit: React.ComponentType< EditProps >;

	const valueFromHTML = ( html: string, start?: number, end?: number ) => {
		const p = document.createElement( 'p' );
		p.innerHTML = html;
		const data = RichTextData.fromHTMLElement( p );
		return {
			data,
			value: {
				text: data.text,
				formats: data.formats,
				replacements: data.replacements,
				start,
				end,
			},
		};
	};

	const setup = (
		attributes: object,
		value: RichTextValue,
		onChange = jest.fn()
	) => {
		( useSelect as jest.Mock ).mockImplementation( () => attributes );
		( useDispatch as jest.Mock ).mockReturnValue( {
			updateBlockAttributes,
		} );
		render(
			<Edit
				contentRef={ { current: document.createElement( 'p' ) } }
				value={ value }
				onChange={ onChange }
			/>
		);
		return onChange;
	};

	const clickInsert = ( which: 'insert-tag' | 'insert-link' ) => {
		fireEvent.click( screen.getByText( 'Personalization Tags' ) );
		fireEvent.click( screen.getByText( which ) );
	};

	beforeEach( () => {
		updateBlockAttributes.mockClear();
		registerFormatTypeMock.mockClear();
		extendRichTextFormats();
		Edit = registerFormatTypeMock.mock.calls.find(
			( c ) => c[ 0 ] === 'woocommerce-email-editor/shortcode'
		)[ 1 ].edit;
	} );

	it( 'inserts at the caret in whitespace-padded content', () => {
		const { data, value } = valueFromHTML( ' We’d love ', 4, 4 );
		const onChange = setup(
			{ content: { originalHTML: ' We’d love ', toString: () => data } },
			value
		);
		clickInsert( 'insert-tag' );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		const result = onChange.mock.calls[ 0 ][ 0 ];
		expect( result.text ).toBe( 'We’d\ufffc\u00a0 love ' );
		expect( result.start ).toBe( 6 );
		expect( result.end ).toBe( 6 );
		expect( result.activeFormats ).toEqual( [] );
		expect( updateBlockAttributes ).not.toHaveBeenCalled();
	} );

	it( 'writes through onChange for captions', () => {
		const { value } = valueFromHTML( 'Caption', 3, 3 );
		const onChange = setup( { url: 'x', caption: 'Caption' }, value );
		clickInsert( 'insert-tag' );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		expect( updateBlockAttributes ).not.toHaveBeenCalled();
	} );

	it( 'writes through onChange for the Button block', () => {
		const { value } = valueFromHTML( 'Buy', 1, 1 );
		const onChange = setup( { text: 'Buy' }, value );
		clickInsert( 'insert-tag' );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		expect( updateBlockAttributes ).not.toHaveBeenCalled();
	} );

	it( 'inserts a link at the caret after bold text', () => {
		const { value } = valueFromHTML( '<strong>Hi</strong> there', 3, 3 );
		const onChange = setup( { content: '' }, value );
		clickInsert( 'insert-link' );
		const result = onChange.mock.calls[ 0 ][ 0 ];
		expect( result.text ).toBe( 'Hi Janethere' );
		const linked = result.formats.map(
			( formats: Array< { type: string; attributes: object } > ) =>
				( formats || [] ).find(
					( f ) =>
						f.type === 'woocommerce-email-editor/link-shortcode'
				)?.attributes[ 'data-link-href' ]
		);
		expect( linked ).toEqual(
			[
				undefined,
				undefined,
				undefined,
				TAG,
				TAG,
				TAG,
				TAG,
				undefined,
			].slice( 0, result.formats.length )
		);
	} );

	it( 'appends at the end without a caret', () => {
		const { value } = valueFromHTML( 'Hello' );
		const onChange = setup( { content: 'Hello' }, value );
		clickInsert( 'insert-tag' );
		expect( onChange.mock.calls[ 0 ][ 0 ].text ).toBe(
			'Hello\ufffc\u00a0'
		);
	} );
} );

describe( 'PersonalizationTagsButton editing', () => {
	const TAG_SPAN = ( tag: string ) =>
		`<span contenteditable="false" data-rich-text-comment="${ tag }"><span>${ tag }</span></span>`;
	const FIRST = '[woocommerce/customer-first-name]';
	const LAST = '[woocommerce/customer-last-name]';
	const updateBlockAttributes = jest.fn();
	let Edit: React.ComponentType< EditProps >;
	let p: HTMLElement;

	const mountValue = ( html: string ) => {
		p = document.createElement( 'p' );
		p.innerHTML = html;
		document.body.appendChild( p );
		return create( {
			element: p,
			__unstableIsEditableTree: true,
		} ) as unknown as RichTextValue;
	};

	const setup = (
		attributes: object,
		value: RichTextValue,
		container: unknown = p
	) => {
		const onChange = jest.fn();
		( useSelect as jest.Mock ).mockImplementation( () => attributes );
		( useDispatch as jest.Mock ).mockReturnValue( {
			updateBlockAttributes,
		} );
		render(
			<Edit
				contentRef={ { current: container } }
				value={ value }
				onChange={ onChange }
			/>
		);
		return onChange;
	};

	const twoTags = () =>
		`Hi ${ TAG_SPAN( FIRST ) }&nbsp;and ${ TAG_SPAN( FIRST ) } end`;
	const tagOf = ( value: RichTextValue, i: number ) =>
		(
			value.replacements[ i ] as unknown as {
				attributes: Record< string, string >;
			}
		 )?.attributes[ 'data-rich-text-comment' ];

	beforeEach( () => {
		updateBlockAttributes.mockClear();
		registerFormatTypeMock.mockClear();
		extendRichTextFormats();
		Edit = registerFormatTypeMock.mock.calls.find(
			( c ) => c[ 0 ] === 'woocommerce-email-editor/shortcode'
		)[ 1 ].edit;
	} );

	afterEach( () => {
		p?.remove();
		document.body.innerHTML = '';
	} );

	it( 'edits the clicked tag when identical tags exist', () => {
		const value = mountValue( twoTags() );
		const snapshot = value.replacements.slice();
		const snapshotObjects = JSON.stringify( value.replacements );
		const onChange = setup( { content: 'x' }, value );
		const second = p.querySelectorAll< HTMLElement >(
			'[data-rich-text-comment]'
		)[ 1 ];
		capturedTagPopover.onUpdate(
			'woocommerce/customer-first-name',
			'woocommerce/customer-last-name',
			second
		);
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		const result = onChange.mock.calls[ 0 ][ 0 ];
		const idx = [ ...value.text ]
			.map( ( c, i ) => ( c === '\ufffc' ? i : -1 ) )
			.filter( ( i ) => i >= 0 );
		expect( tagOf( result, idx[ 0 ] ) ).toBe( FIRST );
		expect( tagOf( result, idx[ 1 ] ) ).toBe( LAST );
		expect( result.text ).toBe( value.text );
		expect( result.activeFormats ).toEqual( [] );
		expect( value.replacements ).toEqual( snapshot );
		value.replacements.forEach( ( r, i ) => {
			expect( r ).toBe( snapshot[ i ] );
		} );
		expect( JSON.stringify( value.replacements ) ).toBe( snapshotObjects );
		expect( updateBlockAttributes ).not.toHaveBeenCalled();
	} );

	it( 'edits a tag in a caption field through onChange', () => {
		const value = mountValue( `Cap ${ TAG_SPAN( FIRST ) }` );
		const onChange = setup( { url: 'x', caption: 'Cap' }, value );
		capturedTagPopover.onUpdate(
			'a',
			'woocommerce/customer-last-name',
			p.querySelector< HTMLElement >( '[data-rich-text-comment]' )
		);
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		expect( updateBlockAttributes ).not.toHaveBeenCalled();
	} );

	it( 'edits a tag in whitespace-collapsed content', () => {
		const source = document.createElement( 'p' );
		source.innerHTML = ' Hi <!--[woocommerce/customer-first-name]--> ';
		const data = RichTextData.fromHTMLElement( source );
		const value = {
			text: data.text,
			formats: data.formats,
			replacements: data.replacements,
		} as RichTextValue;
		p = document.createElement( 'p' );
		p.innerHTML = `Hi ${ TAG_SPAN( FIRST ) }`;
		document.body.appendChild( p );
		const onChange = setup( { content: ' Hi ' }, value );
		capturedTagPopover.onUpdate(
			'a',
			'woocommerce/customer-last-name',
			p.querySelector< HTMLElement >( '[data-rich-text-comment]' )
		);
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		const result = onChange.mock.calls[ 0 ][ 0 ];
		expect( result.text ).toBe( data.text );
		expect( tagOf( result, data.text.indexOf( '\ufffc' ) ) ).toBe( LAST );
		expect( updateBlockAttributes ).not.toHaveBeenCalled();
	} );

	it( 'ignores stale anchors and non-comment positions', () => {
		const value = mountValue( `Hi ${ TAG_SPAN( FIRST ) } <b>bold</b>` );
		const onChange = setup( { content: 'x' }, value );
		const detached = document.createElement( 'span' );
		expect( () =>
			capturedTagPopover.onUpdate( 'a', 'b', detached )
		).not.toThrow();
		capturedTagPopover.onUpdate(
			'a',
			'b',
			p.querySelector< HTMLElement >( 'b' )
		);
		expect( onChange ).not.toHaveBeenCalled();
	} );

	it( 'ignores a single-character replacement that is not a tag', () => {
		const value = mountValue( `Hi <img src="x.png"> there` );
		const onChange = setup( { content: 'x' }, value );
		capturedTagPopover.onUpdate(
			'a',
			'b',
			p.querySelector< HTMLElement >( 'img' )
		);
		expect( onChange ).not.toHaveBeenCalled();
	} );

	describe( 'link editing', () => {
		const LINK = '[woocommerce/shop-url]';
		const NEW = '[woocommerce/my-account-url]';
		const linkFormats = ( result: RichTextValue ) =>
			result.formats.map( ( f ) =>
				(
					( f || [] ) as unknown as Array< {
						type: string;
						attributes?: Record< string, string >;
						unregisteredAttributes?: Record< string, string >;
					} >
				 ).map( ( x ) => x.type )
			);

		const expectUpdated = ( result: RichTextValue ) => {
			expect( result.text ).toBe( 'Go to my account now' );
			const types = linkFormats( result );
			expect( types.flat() ).toEqual(
				Array( 13 ).fill( 'woocommerce-email-editor/link-shortcode' )
			);
			for ( let i = 0; i < result.formats.length; i++ ) {
				const f = ( result.formats[ i ] || [] ) as unknown as Array< {
					attributes: Record< string, string >;
				} >;
				expect( f.length ).toBe( i >= 3 && i < 16 ? 1 : 0 );
				if ( f.length ) {
					expect( f[ 0 ].attributes[ 'data-link-href' ] ).toBe( NEW );
				}
			}
			expect( result.activeFormats ).toEqual( [] );
		};

		afterEach( () => {
			(
				jest.requireActual( '@wordpress/rich-text' ) as {
					unregisterFormatType: ( n: string ) => void;
				}
			 ).unregisterFormatType( 'core/link' );
		} );

		it.each( [ [ true ], [ false ] ] )(
			'edits a class-less template link (core/link registered: %s)',
			( withCoreLink ) => {
				if ( withCoreLink ) {
					(
						jest.requireActual( '@wordpress/rich-text' ) as {
							registerFormatType: (
								n: string,
								s: object
							) => void;
						}
					 ).registerFormatType( 'core/link', {
						name: 'core/link',
						title: 'Link',
						tagName: 'a',
						className: null,
						attributes: { url: 'href', target: 'target' },
						edit: () => null,
					} );
				}
				const value = mountValue(
					`Go <a data-link-href="${ LINK }" contenteditable="false" style="text-decoration: underline;">here</a> now`
				);
				expect(
					JSON.stringify( value.formats[ 3 ] ).includes(
						'unregisteredAttributes'
					)
				).toBe( withCoreLink );
				const onChange = setup( { content: 'x' }, value );
				capturedLinkPopover.onUpdate(
					p.querySelector< HTMLElement >( 'a' ),
					NEW,
					'to my account'
				);
				expect( onChange ).toHaveBeenCalledTimes( 1 );
				expectUpdated( onChange.mock.calls[ 0 ][ 0 ] );
				expect( updateBlockAttributes ).not.toHaveBeenCalled();
			}
		);

		it( 'edits a link created by insertPersonalizationTag', () => {
			const { insertPersonalizationTag } = jest.requireActual(
				'../../../components/personalization-tags/rich-text-utils'
			);
			const base = create( { html: 'Go  now' } ) as RichTextValue;
			const linked = insertPersonalizationTag(
				{ ...base, start: 3, end: 3 },
				LINK,
				'here'
			) as RichTextValue;
			p = document.createElement( 'p' );
			p.innerHTML = `Go <a data-link-href="${ LINK }" contenteditable="false" style="text-decoration: underline;">here</a> now`;
			document.body.appendChild( p );
			const onChange = setup( { content: 'x' }, linked );
			capturedLinkPopover.onUpdate(
				p.querySelector< HTMLElement >( 'a' ),
				NEW,
				'to my account'
			);
			expect( onChange ).toHaveBeenCalledTimes( 1 );
			expectUpdated( onChange.mock.calls[ 0 ][ 0 ] );
		} );

		it( 'edits a link on the Button block through onChange', () => {
			const value = mountValue(
				`Go <a data-link-href="${ LINK }" contenteditable="false">here</a> now`
			);
			const onChange = setup( { text: 'x' }, value );
			capturedLinkPopover.onUpdate(
				p.querySelector< HTMLElement >( 'a' ),
				NEW,
				'to my account'
			);
			expect( onChange ).toHaveBeenCalledTimes( 1 );
			expect( updateBlockAttributes ).not.toHaveBeenCalled();
		} );

		it( 'ignores stale link anchors', () => {
			const value = mountValue( 'Go <b>here</b> now' );
			const onChange = setup( { content: 'x' }, value );
			expect( () =>
				capturedLinkPopover.onUpdate(
					document.createElement( 'a' ),
					NEW,
					'x'
				)
			).not.toThrow();
			capturedLinkPopover.onUpdate(
				p.querySelector< HTMLElement >( 'b' ),
				NEW,
				'x'
			);
			expect( onChange ).not.toHaveBeenCalled();
		} );
	} );
} );
