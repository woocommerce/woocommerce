/**
 * Internal dependencies
 */
import { bind } from '../bind';

const setup = ( mask: string, initial = '' ) => {
	const input = document.createElement( 'input' );
	input.value = initial;
	document.body.appendChild( input );
	input.focus();
	const onChange = jest.fn();
	const bound = bind( input, { mask, onChange } );
	const edit = ( value: string, caret: number, inputType = 'insertText' ) => {
		input.value = value;
		input.setSelectionRange( caret, caret );
		input.dispatchEvent( new window.InputEvent( 'input', { inputType } ) );
	};
	const type = ( text: string ) => {
		for ( const char of text ) {
			const caret = input.selectionStart ?? input.value.length;
			edit(
				input.value.slice( 0, caret ) +
					char +
					input.value.slice( caret ),
				caret + char.length
			);
		}
	};
	const backspace = () => {
		const caret = input.selectionStart ?? input.value.length;
		const length =
			Array.from( input.value.slice( 0, caret ) ).at( -1 )?.length ?? 0;
		edit(
			input.value.slice( 0, caret - length ) + input.value.slice( caret ),
			caret - length,
			'deleteContentBackward'
		);
	};
	const last = () => onChange.mock.calls.at( -1 )?.[ 0 ];
	return { input, bound, type, backspace, edit, last };
};

describe( 'bind', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'formats as the user types and reports the unmasked value', () => {
		const { input, type, last } = setup( '+00 [000] (000) {000}' );
		type( '346' );
		expect( input.value ).toBe( '+34 [6' );
		expect( input.selectionStart ).toBe( 6 );
		expect( last() ).toBe( '346' );
		type( '97745564' );
		expect( input.value ).toBe( '+34 [697] (745) {564}' );
		expect( last() ).toBe( '34697745564' );
	} );

	it( 'lets the user type literals', () => {
		const { input, type, backspace, last } = setup( '+00 [000]' );
		type( '+34 [6' );
		expect( input.value ).toBe( '+34 [6' );
		expect( last() ).toBe( '346' );
		backspace();
		backspace();
		expect( input.value ).toBe( '+34 ' );
		expect( last() ).toBe( '34' );
	} );

	it( 'deletes through inserted literals', () => {
		const { input, type, backspace, last } = setup( '000-000' );
		type( '1234' );
		expect( input.value ).toBe( '123-4' );
		backspace();
		expect( input.value ).toBe( '123' );
		expect( last() ).toBe( '123' );
		type( '456' );
		backspace();
		expect( input.value ).toBe( '123-45' );
	} );

	it( 'deletes a typed character with its trailing inserted literals', () => {
		const { input, type, backspace, last } = setup( '{000}' );
		type( '123' );
		expect( input.value ).toBe( '{123}' );
		backspace();
		expect( input.value ).toBe( '{12' );
		expect( last() ).toBe( '12' );
	} );

	it( 'shows text as typed when it stops fitting and recovers', () => {
		const { input, type, backspace, last } = setup( '000-000' );
		type( '123a' );
		expect( input.value ).toBe( '123a' );
		expect( last() ).toBe( '123a' );
		backspace();
		expect( input.value ).toBe( '123' );
		expect( last() ).toBe( '123' );
	} );

	it( 'edits in the middle and keeps the caret', () => {
		const { input, edit, type, last } = setup( '000-000' );
		type( '1245' );
		edit( '12-45', 2 );
		edit( '123-45', 3 );
		expect( input.value ).toBe( '123-45' );
		expect( input.selectionStart ).toBe( 4 );
		expect( last() ).toBe( '12345' );
	} );

	it( 'accepts a pasted or autofilled formatted value', () => {
		const { input, edit, last } = setup( '000.000.000-00' );
		edit( '123.456.789-01', 14 );
		expect( input.value ).toBe( '123.456.789-01' );
		expect( last() ).toBe( '12345678901' );
	} );

	it.each( [
		[ '+3', '3' ],
		[ '34 [6', '346' ],
		[ '34[6', '346' ],
		[ '+34 [697] (745) {564}', '34697745564' ],
	] )( 'consumes literals in pasted text %p', ( value, raw ) => {
		const { edit, last } = setup( '+00 [000] (000) {000}' );
		edit( value, value.length, 'insertFromPaste' );
		expect( last() ).toBe( raw );
	} );

	it( 'treats the initial value as raw slot values', () => {
		const { input } = setup( '10000', '1234' );
		expect( input.value ).toBe( '11234' );
	} );

	it( 'formats the initial value and values set from outside', () => {
		const { input, bound, last } = setup( '000-000', '123456' );
		expect( input.value ).toBe( '123-456' );
		expect( last() ).toBeUndefined();
		bound.setValue( '654321' );
		expect( input.value ).toBe( '654-321' );
	} );

	it( 'stops after destroy', () => {
		const { input, bound, type } = setup( '000-000' );
		bound.destroy();
		type( '1234' );
		expect( input.value ).toBe( '1234' );
	} );

	it( 'keeps raw values distinct from literals through later edits', () => {
		const { input, bound, type, backspace, last } = setup( '10000' );
		bound.setValue( '1234' );
		expect( input.value ).toBe( '11234' );
		backspace();
		expect( input.value ).toBe( '1123' );
		expect( last() ).toBe( '123' );
		type( '45' );
		expect( input.value ).toBe( '12345' );
		expect( last() ).toBe( '12345' );
	} );

	it( 'keeps the caret after a supplementary-plane letter', () => {
		const { input, type, backspace, last } = setup( 'a-0' );
		type( '𐐀1' );
		expect( input.value ).toBe( '𐐀-1' );
		expect( input.selectionStart ).toBe( 4 );
		backspace();
		backspace();
		expect( input.value ).toBe( '' );
		expect( last() ).toBe( '' );
	} );

	it( 'deletes a whole code point beside inserted literals', () => {
		const { input, type, backspace, last, edit } = setup( '{*}' );
		type( '😀' );
		backspace();
		expect( input.value ).toBe( '' );
		expect( last() ).toBe( '' );
		type( '😀' );
		edit( '😀}', 0, 'deleteContentForward' );
		expect( input.value ).toBe( '' );
		expect( last() ).toBe( '' );
	} );

	it( 'keeps fallback text in order and replaces Unicode selections', () => {
		const { input, type, edit, last } = setup( '000-000' );
		type( '😀123' );
		expect( input.value ).toBe( '😀123' );
		edit( '4123', 1, 'insertReplacementText' );
		expect( input.value ).toBe( '412-3' );
		expect( last() ).toBe( '4123' );
		expect( input.selectionStart ).toBe( 1 );
	} );
} );
