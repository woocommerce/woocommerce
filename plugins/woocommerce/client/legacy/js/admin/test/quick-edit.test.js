/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'Product Quick Edit measurements', () => {
	let clickHandler;
	let fieldValues;
	let inlineValues;
	let editButton;

	const chain = () => {
		const object = {
			length: 0,
			add: () => object,
			attr: () => object,
			closest: () => object,
			datepicker: () => object,
			find: () => object,
			hide: () => object,
			is: () => false,
			on: () => object,
			parent: () => object,
			prop: () => object,
			removeAttr: () => object,
			show: () => object,
			text: () => '',
			toggle: () => object,
			trigger: () => object,
			val: () => object,
		};
		return object;
	};

	beforeEach( () => {
		jest.useFakeTimers();
		jest.resetModules();
		fieldValues = {};
		inlineValues = {};
		editButton = {};

		const measurementFields = new Map( [
			[ 'input[name="_weight"]', '_weight' ],
			[ 'input[name="_length"]', '_length' ],
			[ 'input[name="_width"]', '_width' ],
			[ 'input[name="_height"]', '_height' ],
		] );

		const jQueryMock = ( selector, context ) => {
			if ( 'function' === typeof selector ) {
				selector( jQueryMock );
				return;
			}

			if ( '#the-list' === selector ) {
				return {
					on: ( event, delegatedSelector, handler ) => {
						if ( 'click' === event && '.editinline' === delegatedSelector ) {
							clickHandler = handler;
						}
					},
				};
			}

			if ( selector === editButton ) {
				return { closest: () => ( { attr: () => 'post-1' } ) };
			}

			if ( '#woocommerce_inline_1' === selector ) {
				return {
					find: ( inlineSelector ) => ( {
						length: 0,
						text: () => inlineValues[ inlineSelector.slice( 1 ).trim() ] || '',
					} ),
				};
			}

			const field = measurementFields.get( selector );
			if ( field && '.inline-edit-row' === context ) {
				return {
					val: ( value ) => {
						fieldValues[ field ] = value;
					},
				};
			}

			return chain();
		};

		global.jQuery = jQueryMock;
		global.inlineEditPost = { revert: jest.fn() };
		global.woocommerce_admin = { decimal_point: '.' };
		global.woocommerce_quick_edit = { strings: { allow_reviews: '' } };
		require( '../quick-edit' );
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	test.each( [
		[ ',', '1.25', '1,25' ],
		[ '.', '1.25', '1.25' ],
		[ ',', '', '' ],
	] )(
		'populates measurements when the decimal separator is %s and the stored value is %j',
		( decimalPoint, storedValue, expectedValue ) => {
			woocommerce_admin.decimal_point = decimalPoint;
			inlineValues = {
				weight: storedValue,
				length: storedValue,
				width: storedValue,
				height: storedValue,
			};

			clickHandler.call( editButton );

			expect( fieldValues ).toEqual( {
				_weight: expectedValue,
				_length: expectedValue,
				_width: expectedValue,
				_height: expectedValue,
			} );
		}
	);
} );
