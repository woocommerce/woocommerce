import * as React from 'react';

/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { registerFormatType, RichTextData } from '@wordpress/rich-text';
import type { RichTextValue } from '@wordpress/rich-text/build-types/types';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { extendRichTextFormats } from '../rich-text';

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
		PersonalizationTagsPopover: () => null,
	} )
);

jest.mock(
	'../../../components/personalization-tags/personalization-tags-link-popover',
	() => ( {
		PersonalizationTagsLinkPopover: () => null,
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
