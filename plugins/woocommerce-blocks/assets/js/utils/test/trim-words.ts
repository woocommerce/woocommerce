/**
 * External dependencies
 */
import {
	appendMoreText,
	removeTags,
	trimCharacters,
	trimWords,
} from '@woocommerce/utils';

describe( 'trim-words', () => {
	describe( 'removeTags', () => {
		it( 'Removes HTML tags from a string', () => {
			const string = '<div><a href="/index.php">trim-words.ts</a></div>';
			const trimmedString = removeTags( string );
			expect( trimmedString ).toEqual( 'trim-words.ts' );
		} );
	} );
	describe( 'appendMoreText', () => {
		it( 'Removes trailing punctuation and appends some characters to a string', () => {
			const string = 'trim-words.ts,';
			const appendedString = appendMoreText( string, '...' );
			expect( appendedString ).toEqual( 'trim-words.ts...' );
		} );
	} );
	describe( 'trimWords', () => {
		const testContent =
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.';
		it( 'Limits words in string and returns trimmed version', () => {
			const trimmedString = trimWords( testContent, 3 );
			expect( trimmedString ).toBe(
				'<p>Lorem ipsum dolor&hellip;</p>\n'
			);
		} );
		it( 'Limits words in string and returns trimmed version with custom moreText', () => {
			const trimmedString = trimWords( testContent, 4, '... read more.' );
			expect( trimmedString ).toEqual(
				'<p>Lorem ipsum dolor sit... read more.</p>\n'
			);
		} );
		it( 'Limits words in string and returns trimmed version without autop', () => {
			const trimmedString = trimWords(
				testContent,
				3,
				'&hellip;',
				false
			);
			expect( trimmedString ).toEqual( 'Lorem ipsum dolor&hellip;' );
		} );
		it( 'does not append anything if the text is shorter than the trim limit', () => {
			const trimmedString = trimWords( testContent, 100 );
			expect( trimmedString ).toEqual( '<p>' + testContent + '</p>\n' );
		} );
	} );
	describe( 'trimCharacters', () => {
		const testContent = 'Lorem ipsum dolor sit amet.';

		it( 'Limits characters in string and returns trimmed version including spaces', () => {
			const result = trimCharacters( testContent, 10 );
			expect( result ).toEqual( '<p>Lorem ipsu&hellip;</p>\n' );
		} );
		it( 'Limits characters in string and returns trimmed version excluding spaces', () => {
			const result = trimCharacters( testContent, 10, false );
			expect( result ).toEqual( '<p>Lorem ipsum&hellip;</p>\n' );
		} );
		it( 'Limits characters in string and returns trimmed version with custom moreText', () => {
			const result = trimCharacters(
				testContent,
				10,
				false,
				'... read more.'
			);
			expect( result ).toEqual( '<p>Lorem ipsum... read more.</p>\n' );
		} );
		it( 'Limits characters in string and returns trimmed version without autop', () => {
			const result = trimCharacters(
				testContent,
				10,
				false,
				'... read more.',
				false
			);
			expect( result ).toEqual( 'Lorem ipsum... read more.' );
		} );

		it( 'does not append anything if the text is shorter than the trim limit', () => {
			const trimmedString = trimCharacters( testContent, 1000 );
			expect( trimmedString ).toEqual( '<p>' + testContent + '</p>\n' );
		} );
	} );
} );
