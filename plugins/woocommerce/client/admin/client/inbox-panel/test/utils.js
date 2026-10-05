import { describe, expect, it, test } from 'vitest';

/**
 * Internal dependencies
 */
import { truncateRenderableHTML, truncate } from '../utils';

describe( 'truncateRenderableHTML', () => {
	test( 'it should recover malformed HTML when truncated', () => {
		const malformed = '<div>this is a test sentence</asdf>';

		expect( truncateRenderableHTML( malformed, 7 ) ).toBe(
			'<div>this is</div>...'
		);
	} );

	test( 'it should not truncate if the length does not exceed', () => {
		const sample = '<div>this is a test sentence</div>';
		expect( truncateRenderableHTML( sample, sample.length ) ).toBe(
			sample
		);
	} );

	test( 'it should consider &nbsp; as a single space', () => {
		const samplewithSpace = '<div>this &nbsp;&nbsp; is</div>';
		// this(4 chars) + space (1 char) + &nbsp;&nbsp;(2 chars) = 7
		expect( truncateRenderableHTML( samplewithSpace, 7 ) ).toBe(
			'<div>this &nbsp;&nbsp;</div>...'
		);
	} );

	test( 'it should not count nested tags as text', () => {
		const sampleWithNestedTags = '<div>this <br/><br/> is</div>';
		// this (4 chars) + space (1 char) + space (1char) + is (2 chars)) = 8
		expect( truncateRenderableHTML( sampleWithNestedTags, 8 ) ).toBe(
			'<div>this <br/><br/> is</div>'
		);
	} );

	test( 'it should truncate properly with nested tags', () => {
		const sampleWithNestedTags = '<div>this is <div>a</div></div>';
		// this (4 chars) + space (1 char) + is (2char) + space (1 char) = 7
		expect( truncateRenderableHTML( sampleWithNestedTags, 7 ) ).toBe(
			'<div>this is</div>...'
		);
	} );

	test( 'it should work with unicode text', () => {
		const sampleWithUnicode = '<div>테스트 입니다.</div>';
		expect( truncateRenderableHTML( sampleWithUnicode, 3 ) ).toBe(
			'<div>테스트</div>...'
		);
	} );

	test( 'it should preserve whole words when truncated', () => {
		const sample = '<div>this is a test sentence</div>';
		// it should return 'this is a' (9 chars) when length 11 is given
		// since 'this is a t' (11 chars) cannot include 'test' word without
		// breaking the word.
		expect( truncateRenderableHTML( sample, 11 ) ).toBe(
			'<div>this is a</div>...'
		);
	} );

	test( 'it should preserve whole words with emoji when truncated', () => {
		const sample = '<div>🏳️‍🌈this is a test sentence</div>';
		// it should return '🏳️‍🌈this is a' (10 chars) when length 12 is given
		// since '🏳️‍🌈this is a t' (12 chars) cannot include 'test' word without
		// breaking the word.
		expect( truncateRenderableHTML( sample, 12 ) ).toBe(
			'<div>🏳️‍🌈this is a</div>...'
		);
	} );

	test( 'it should work with multi-char letters', () => {
		const sampleWithUnicode = '<div>🏳️‍🌈</div>';
		expect( truncateRenderableHTML( sampleWithUnicode, 1 ) ).toBe(
			'<div>🏳️‍🌈</div>'
		);

		const hindiSample = '<div>अनुच्छेद</div>';
		expect( truncateRenderableHTML( hindiSample, 5 ) ).toBe(
			'<div>अनुच्छेद</div>'
		);

		expect( truncateRenderableHTML( hindiSample, 3 ) ).toBe(
			'<div>अनुच्</div>...'
		);

		const demonicSample = '<div>Z͑ͫ̓ͪ̂ͫ̽͏̴̙̤̞͉͚̯̞̠͍A̴̵̜̰͔ͫ͗͢L̠ͨͧͩ͘G̴̻͈͍͔̹̑͗̎̅͛́Ǫ̵̹̻̝̳͂̌̌͘!͖̬̰̙̗̿̋ͥͥ̂ͣ̐́́͜͞</div>';
		expect( truncateRenderableHTML( demonicSample, 6 ) ).toBe(
			'<div>Z͑ͫ̓ͪ̂ͫ̽͏̴̙̤̞͉͚̯̞̠͍A̴̵̜̰͔ͫ͗͢L̠ͨͧͩ͘G̴̻͈͍͔̹̑͗̎̅͛́Ǫ̵̹̻̝̳͂̌̌͘!͖̬̰̙̗̿̋ͥͥ̂ͣ̐́́͜͞</div>'
		);
	} );
} );

describe( 'truncate', () => {
	it( 'should truncate letters and return truncated string', () => {
		expect( truncate( 'this is a test sentence'.split( '' ), 4 ) ).toBe(
			'this'
		);
	} );

	it( 'should not contain end-space', () => {
		expect( truncate( 'this is a test sentence'.split( '' ), 5 ) ).toBe(
			'this'
		);
	} );

	it( 'should preserve whole words', () => {
		// "this i" doesn't preserve whole words, so it should be truncated to "this"
		expect( truncate( 'this is a test sentence'.split( '' ), 6 ) ).toBe(
			'this'
		);
		expect( truncate( 'this is a test sentence'.split( '' ), 7 ) ).toBe(
			'this is'
		);
	} );
} );
