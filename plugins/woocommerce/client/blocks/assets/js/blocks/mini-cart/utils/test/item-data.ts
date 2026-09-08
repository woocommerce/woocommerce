/**
 * Internal dependencies
 */
import {
	ItemData,
	getEntryFieldRaw,
	isEntryHiddenFlag,
	isItemDataEntryVisible,
	isLastVisibleEntry,
	buildCartItemDataAttr,
} from '../item-data';

/**
 * Builds a well-formed, visible `item_data`-shaped entry.
 */
const entry = ( key: string, value = 'value', overrides = {} ): ItemData => ( {
	key,
	value,
	...overrides,
} );

/**
 * Builds an entry with a truthy `hidden` flag, otherwise well-formed.
 */
const hidden = ( key: string, value = 'value' ): ItemData =>
	entry( key, value, { hidden: true } );

/**
 * Builds a malformed entry: no usable name and no usable value.
 */
const malformed = (): ItemData => ( { key: '' } );

describe( 'getEntryFieldRaw tests', () => {
	test( 'returns an empty string for an undefined entry', () => {
		expect( getEntryFieldRaw( undefined, 'name' ) ).toBe( '' );
		expect( getEntryFieldRaw( undefined, 'value' ) ).toBe( '' );
	} );

	test( 'name: prefers key over attribute over name', () => {
		expect(
			getEntryFieldRaw(
				{ key: 'the-key', attribute: 'the-attribute' } as ItemData,
				'name'
			)
		).toBe( 'the-key' );
		expect(
			getEntryFieldRaw(
				{
					name: 'the-name',
					attribute: 'the-attribute',
				} as ItemData,
				'name'
			)
		).toBe( 'the-attribute' );
		expect(
			getEntryFieldRaw( { name: 'the-name' } as ItemData, 'name' )
		).toBe( 'the-name' );
	} );

	test( 'name: falls back to an empty string when nothing usable is present', () => {
		expect( getEntryFieldRaw( { key: '' } as ItemData, 'name' ) ).toBe(
			''
		);
	} );

	test( 'value: prefers display over value', () => {
		expect(
			getEntryFieldRaw(
				{ key: 'k', display: 'the-display', value: 'the-value' },
				'value'
			)
		).toBe( 'the-display' );
		expect(
			getEntryFieldRaw( { key: 'k', value: 'the-value' }, 'value' )
		).toBe( 'the-value' );
	} );

	test( 'value: falls back to an empty string when nothing usable is present', () => {
		expect( getEntryFieldRaw( { key: 'k' }, 'value' ) ).toBe( '' );
	} );
} );

describe( 'isEntryHiddenFlag tests', () => {
	test.each( [
		[ true, true ],
		[ 'true', true ],
		[ '1', true ],
		[ 1, true ],
		[ false, false ],
		[ undefined, false ],
		[ 0, false ],
		[ 'yes', false ],
	] )( 'hidden: %p -> %p', ( hiddenValue, expected ) => {
		expect(
			isEntryHiddenFlag( { key: 'k', hidden: hiddenValue } as ItemData )
		).toBe( expected );
	} );

	test( 'returns false for an undefined entry', () => {
		expect( isEntryHiddenFlag( undefined ) ).toBe( false );
	} );
} );

describe( 'isItemDataEntryVisible tests', () => {
	test( 'true for an entry with a usable name and no hidden flag', () => {
		expect( isItemDataEntryVisible( { key: 'Color' } as ItemData ) ).toBe(
			true
		);
	} );

	test( 'true for an entry with only a usable value and no hidden flag', () => {
		expect(
			isItemDataEntryVisible( { key: '', value: 'Red' } as ItemData )
		).toBe( true );
	} );

	test( 'false for an entry with neither a usable name nor value, regardless of hidden', () => {
		expect( isItemDataEntryVisible( { key: '' } as ItemData ) ).toBe(
			false
		);
		expect(
			isItemDataEntryVisible( {
				key: '',
				hidden: true,
			} as ItemData )
		).toBe( false );
		expect(
			isItemDataEntryVisible( {
				key: '',
				hidden: false,
			} as ItemData )
		).toBe( false );
	} );

	test( 'false for an undefined entry', () => {
		expect( isItemDataEntryVisible( undefined ) ).toBe( false );
	} );

	test.each( [ true, 'true', '1', 1 ] )(
		'false for a well-formed entry whose hidden flag is %p',
		( hiddenValue ) => {
			expect(
				isItemDataEntryVisible( {
					key: 'Color',
					value: 'Red',
					hidden: hiddenValue,
				} as ItemData )
			).toBe( false );
		}
	);

	test.each( [ false, undefined ] )(
		'true for a well-formed entry whose hidden flag is %p',
		( hiddenValue ) => {
			expect(
				isItemDataEntryVisible( {
					key: 'Color',
					value: 'Red',
					hidden: hiddenValue,
				} as ItemData )
			).toBe( true );
		}
	);
} );

describe( 'isLastVisibleEntry tests', () => {
	test( 'undefined items list -> true', () => {
		expect( isLastVisibleEntry( undefined, entry( 'Color' ) ) ).toBe(
			true
		);
	} );

	test( 'empty items list -> true', () => {
		expect( isLastVisibleEntry( [], entry( 'Color' ) ) ).toBe( true );
	} );

	test( 'single-entry list, entry is visible -> true for that entry', () => {
		const only = entry( 'Color' );
		expect( isLastVisibleEntry( [ only ], only ) ).toBe( true );
	} );

	test( 'single-entry list, entry is malformed -> true (no visible items)', () => {
		const only = malformed();
		expect( isLastVisibleEntry( [ only ], only ) ).toBe( true );
	} );

	test( 'all-visible list: only the last entry is the last-visible one', () => {
		const first = entry( 'Color' );
		const middle = entry( 'Size' );
		const last = entry( 'Material' );
		const items = [ first, middle, last ];

		expect( isLastVisibleEntry( items, first ) ).toBe( false );
		expect( isLastVisibleEntry( items, middle ) ).toBe( false );
		expect( isLastVisibleEntry( items, last ) ).toBe( true );
	} );

	test( 'malformed entry in first position is skipped', () => {
		const bad = malformed();
		const visible1 = entry( 'Color' );
		const visible2 = entry( 'Size' );
		const items = [ bad, visible1, visible2 ];

		expect( isLastVisibleEntry( items, bad ) ).toBe( false );
		expect( isLastVisibleEntry( items, visible1 ) ).toBe( false );
		expect( isLastVisibleEntry( items, visible2 ) ).toBe( true );
	} );

	test( 'malformed entry in middle position is skipped', () => {
		const visible1 = entry( 'Color' );
		const bad = malformed();
		const visible2 = entry( 'Size' );
		const items = [ visible1, bad, visible2 ];

		expect( isLastVisibleEntry( items, visible1 ) ).toBe( false );
		expect( isLastVisibleEntry( items, bad ) ).toBe( false );
		expect( isLastVisibleEntry( items, visible2 ) ).toBe( true );
	} );

	test( 'malformed entry in last position does not suppress the real last visible entry', () => {
		const visible1 = entry( 'Color' );
		const visible2 = entry( 'Size' );
		const bad = malformed();
		const items = [ visible1, visible2, bad ];

		expect( isLastVisibleEntry( items, visible1 ) ).toBe( false );
		expect( isLastVisibleEntry( items, visible2 ) ).toBe( true );
		expect( isLastVisibleEntry( items, bad ) ).toBe( false );
	} );

	test( 'multiple consecutive malformed entries at the end are all skipped', () => {
		const visible1 = entry( 'Color' );
		const bad1 = malformed();
		const bad2 = malformed();
		const items = [ visible1, bad1, bad2 ];

		expect( isLastVisibleEntry( items, visible1 ) ).toBe( true );
		expect( isLastVisibleEntry( items, bad1 ) ).toBe( false );
		expect( isLastVisibleEntry( items, bad2 ) ).toBe( false );
	} );

	test( 'single hidden entry at the end is skipped', () => {
		const visible1 = entry( 'Color' );
		const hiddenLast = hidden( 'Size' );
		const items = [ visible1, hiddenLast ];

		expect( isLastVisibleEntry( items, visible1 ) ).toBe( true );
		expect( isLastVisibleEntry( items, hiddenLast ) ).toBe( false );
	} );

	test( 'multiple consecutive hidden entries at the end are all skipped', () => {
		const visible1 = entry( 'Color' );
		const hidden1 = hidden( 'Size' );
		const hidden2 = hidden( 'Material' );
		const items = [ visible1, hidden1, hidden2 ];

		expect( isLastVisibleEntry( items, visible1 ) ).toBe( true );
		expect( isLastVisibleEntry( items, hidden1 ) ).toBe( false );
		expect( isLastVisibleEntry( items, hidden2 ) ).toBe( false );
	} );

	test( 'mixed trailing malformed + hidden entries, malformed then hidden', () => {
		const visible1 = entry( 'Color' );
		const bad = malformed();
		const hiddenLast = hidden( 'Size' );
		const items = [ visible1, bad, hiddenLast ];

		expect( isLastVisibleEntry( items, visible1 ) ).toBe( true );
		expect( isLastVisibleEntry( items, bad ) ).toBe( false );
		expect( isLastVisibleEntry( items, hiddenLast ) ).toBe( false );
	} );

	test( 'mixed trailing malformed + hidden entries, hidden then malformed', () => {
		const visible1 = entry( 'Color' );
		const hiddenMiddle = hidden( 'Size' );
		const bad = malformed();
		const items = [ visible1, hiddenMiddle, bad ];

		expect( isLastVisibleEntry( items, visible1 ) ).toBe( true );
		expect( isLastVisibleEntry( items, hiddenMiddle ) ).toBe( false );
		expect( isLastVisibleEntry( items, bad ) ).toBe( false );
	} );
} );

describe( 'buildCartItemDataAttr tests', () => {
	test( 'never returns null or undefined', () => {
		expect( buildCartItemDataAttr( undefined ) ).not.toBeNull();
		expect( buildCartItemDataAttr( undefined ) ).not.toBeUndefined();
		expect( buildCartItemDataAttr( malformed() ) ).not.toBeNull();
	} );

	test( 'malformed/empty entry produces empty normalized values', () => {
		expect( buildCartItemDataAttr( undefined ) ).toEqual( {
			name: '',
			value: '',
			className: 'wc-block-components-product-details__',
		} );
		expect( buildCartItemDataAttr( malformed() ) ).toEqual( {
			name: '',
			value: '',
			className: 'wc-block-components-product-details__',
		} );
	} );

	test( 'plain text entry', () => {
		expect(
			buildCartItemDataAttr( {
				key: 'Gift Message',
				value: 'Happy Birthday!',
			} )
		).toEqual( {
			name: 'Gift Message:',
			value: 'Happy Birthday!',
			className: 'wc-block-components-product-details__gift-message',
		} );
	} );

	test( 'HTML-in-display value entry', () => {
		expect(
			buildCartItemDataAttr( {
				key: 'Engraving',
				value: 'Best Wishes',
				display: '<em>Best Wishes</em>',
			} )
		).toEqual( {
			name: 'Engraving:',
			value: '<em>Best Wishes</em>',
			className: 'wc-block-components-product-details__engraving',
		} );
	} );

	test( 'entity-encoded value entry', () => {
		expect(
			buildCartItemDataAttr( {
				key: 'Size',
				value: '1 &lt; 2',
			} )
		).toEqual( {
			name: 'Size:',
			value: '1 < 2',
			className: 'wc-block-components-product-details__size',
		} );
	} );

	test( 'entity-encoded tag entry', () => {
		expect(
			buildCartItemDataAttr( {
				key: 'Note',
				value: '&lt;b&gt;important&lt;/b&gt;',
			} )
		).toEqual( {
			name: 'Note:',
			value: '<b>important</b>',
			className: 'wc-block-components-product-details__note',
		} );
	} );

	test( 'ampersand in attribute value entry (variation-style)', () => {
		expect(
			buildCartItemDataAttr( {
				raw_attribute: 'Shade',
				name: 'Shade',
				value: 'Red &amp; Blue',
			} as ItemData )
		).toEqual( {
			name: 'Shade:',
			value: 'Red & Blue',
			className: 'wc-block-components-product-details__shade',
		} );
	} );

	test( 'derives className from the decoded name: camelCase splitting, tag stripping, and whitespace/underscore/ampersand collapsing', () => {
		expect(
			buildCartItemDataAttr( {
				key: 'giftMessage <b>Type</b> foo_bar & baz',
				value: 'x',
			} )
		).toEqual( {
			name: 'giftMessage <b>Type</b> foo_bar & baz:',
			value: 'x',
			className:
				'wc-block-components-product-details__gift-message-type-foo-bar-baz',
		} );
	} );

	test( 'explicitly-hidden well-formed entry is normalized without visibility state', () => {
		expect(
			buildCartItemDataAttr( {
				key: 'Color',
				value: 'Red',
				hidden: true,
			} )
		).toEqual( {
			name: 'Color:',
			value: 'Red',
			className: 'wc-block-components-product-details__color',
		} );
	} );
} );
