/* global Node, HTMLDivElement */
/**
 * External dependencies
 */
import * as React from 'react';
import { act, fireEvent, render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import { RichTextWithButton } from '../rich-text-with-button';

const TAG = '[woocommerce/customer-first-name]';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: () => [],
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

jest.mock( '@wordpress/components', () => ( {
	BaseControl: ( {
		label,
		children,
	}: {
		label: React.ReactNode;
		children: React.ReactNode;
	} ) => (
		<div>
			{ label }
			{ children }
		</div>
	),
	Button: ( { onClick, title }: React.ComponentProps< 'button' > ) => (
		<button onClick={ onClick }>{ title }</button>
	),
} ) );

jest.mock( '@wordpress/block-editor', () => {
	const { forwardRef } = jest.requireActual( 'react' );
	return {
		RichText: forwardRef(
			(
				{ value }: { value: string },
				ref: React.Ref< HTMLDivElement >
			) => (
				<div
					ref={ ref }
					contentEditable
					suppressContentEditableWarning
					tabIndex={ -1 }
					data-testid="field"
					dangerouslySetInnerHTML={ { __html: value } }
				/>
			)
		),
	};
} );

jest.mock( '../personalization-tags-modal', () => ( {
	PersonalizationTagsModal: ( {
		onInsert,
	}: {
		onInsert: ( tag: string, linkText: string | null ) => void;
	} ) => (
		<button
			onClick={ () =>
				onInsert( '[woocommerce/customer-first-name]', null )
			}
		>
			insert-tag
		</button>
	),
} ) );

jest.mock( '../personalization-tags-popover', () => ( {
	PersonalizationTagsPopover: () => null,
} ) );

jest.mock( '../../../store', () => ( { storeName: 'email-editor' } ) );
jest.mock( '../../../events', () => ( {
	recordEvent: jest.fn(),
	recordEventOnce: jest.fn(),
} ) );

const NBSP = ' ';

function Harness( {
	initial,
	onWrite,
}: {
	initial: string;
	onWrite: ( value: string ) => void;
} ) {
	const [ value, setValue ] = React.useState( initial );
	return (
		<>
			<input data-testid="outside-input" />
			<button data-testid="outside-button">outside</button>
			<RichTextWithButton
				label="Subject"
				placeholder=""
				attributeName="subject"
				attributeValue={ value }
				updateProperty={ ( _name, updated ) => {
					onWrite( updated );
					setValue( updated );
				} }
			/>
		</>
	);
}

const placeCaret = ( node: Node, offset: number ) => {
	const range = document.createRange();
	range.setStart( node, offset );
	range.collapse( true );
	const selection = document.defaultView.getSelection();
	selection.removeAllRanges();
	selection.addRange( range );
	act( () => {
		document.dispatchEvent( new Event( 'selectionchange' ) );
	} );
};

describe( 'RichTextWithButton caret tracking', () => {
	let written: string[];

	const setup = ( initial: string ) => {
		written = [];
		render(
			<Harness
				initial={ initial }
				onWrite={ ( value ) => written.push( value ) }
			/>
		);
		return screen.getByTestId( 'field' );
	};

	const insertTag = () => fireEvent.click( screen.getByText( 'insert-tag' ) );

	afterEach( () => {
		document.defaultView.getSelection().removeAllRanges();
	} );

	it( 'inserts at the caret placed after formatted text', () => {
		const field = setup( 'Hello <strong>there</strong> friend' );
		act( () => field.focus() );
		placeCaret( field.childNodes[ 2 ], 3 );

		insertTag();

		expect( written ).toEqual( [
			`Hello <strong>there</strong> fr<!--${ TAG }-->iend`,
		] );
	} );

	it( 'keeps the stored position when focus moves to another input', () => {
		const field = setup( 'Hello <strong>there</strong> friend' );
		act( () => field.focus() );
		placeCaret( field.childNodes[ 2 ], 3 );

		const input = screen.getByTestId( 'outside-input' );
		act( () => input.focus() );
		placeCaret( input.parentElement, 0 );

		insertTag();

		expect( written[ 0 ] ).toBe(
			`Hello <strong>there</strong> fr<!--${ TAG }-->iend`
		);
	} );

	it( 'ignores selection changes while the field is not focused', () => {
		const field = setup( 'Hello <strong>there</strong> friend' );
		act( () => field.focus() );
		placeCaret( field.childNodes[ 2 ], 3 );

		act( () => screen.getByTestId( 'outside-button' ).focus() );
		document.defaultView.getSelection().selectAllChildren( document.body );
		act( () => {
			document.dispatchEvent( new Event( 'selectionchange' ) );
		} );

		insertTag();

		expect( written[ 0 ] ).toBe(
			`Hello <strong>there</strong> fr<!--${ TAG }-->iend`
		);
	} );

	it( 'places a second tag right after the first one', () => {
		const field = setup( 'Hello <strong>there</strong> friend' );
		act( () => field.focus() );
		placeCaret( field.childNodes[ 2 ], 3 );

		insertTag();
		insertTag();

		expect( written[ 1 ] ).toBe(
			`Hello <strong>there</strong> fr<!--${ TAG }--><!--${ TAG }-->iend`
		);
	} );

	it( 'appends at the end when no caret was stored', () => {
		setup( 'Hello <strong>there</strong> friend' );

		insertTag();

		expect( written[ 0 ] ).toBe(
			`Hello <strong>there</strong> friend<!--${ TAG }-->`
		);
		expect( NBSP ).toHaveLength( 1 );
	} );
} );
