/**
 * @jest-environment jest-fixed-jsdom
 */

// Locale fixture. `default.phone.required` mirrors a store whose phone field is
// set to required, which is how a country without its own phone override falls
// back. AL only relabels state. ZZ hides the phone field.
const localeFixture = {
	default: {
		city: { required: true },
		phone: { required: true, type: 'tel' },
	},
	AL: { state: { label: 'County' } },
	ZZ: { phone: { required: false, hidden: true } },
};

const localeFieldsFixture = {
	city: '#billing_city_field',
	phone: '#billing_phone_field, #shipping_phone_field',
};

describe( 'address-i18n phone required state', () => {
	let bodyEventHandlers;
	let countryToStateChangingHandler;
	let fields;

	// Build a chainable field mock. Every method returns the field so the
	// handler can keep chaining the way jQuery allows. Class and data state are
	// tracked so required-state changes can be asserted.
	function createFieldMock( options = {} ) {
		const field = {};
		const dataStore = {};
		const classes = new Set();

		if ( options.required ) {
			classes.add( 'validate-required' );
		}

		const input = {
			val: jest.fn( () => field ),
			attr: jest.fn( () => field ),
		};
		const label = {
			length: 1,
			html: jest.fn( () => label ),
			append: jest.fn( () => label ),
			remove: jest.fn( () => label ),
		};

		Object.assign( field, {
			length: 1,
			hide: jest.fn( () => field ),
			show: jest.fn( () => field ),
			addClass: jest.fn( ( value ) => {
				value.split( ' ' ).forEach( ( name ) => classes.add( name ) );
				return field;
			} ),
			removeClass: jest.fn( ( value ) => {
				value.split( ' ' ).forEach( ( name ) => classes.delete( name ) );
				return field;
			} ),
			hasClass: jest.fn( ( value ) => classes.has( value ) ),
			data: jest.fn( ( name, value ) => {
				// Single-argument reads return the stored value; writes return
				// the field so chaining works.
				if ( value === undefined ) {
					return dataStore[ name ];
				}
				dataStore[ name ] = value;
				return field;
			} ),
			text: jest.fn( () => field ),
			attr: jest.fn( ( name, value ) => {
				// Single-argument reads return the stored value (undefined for
				// data-o_class on first run); writes return the field.
				if ( value === undefined ) {
					return options[ name ];
				}
				return field;
			} ),
			find: jest.fn( ( selector ) => {
				if ( selector === ':input' ) {
					return input;
				}
				if ( selector === 'label' ) {
					return label;
				}
				if ( selector.indexOf( 'label .optional' ) !== -1 ) {
					return {
						length: options.optionalLabel ? 1 : 0,
						remove: jest.fn(),
						append: jest.fn(),
					};
				}
				if (
					selector.indexOf( 'label .required[aria-hidden="true"]' ) !== -1
				) {
					return { length: options.requiredMark ? 1 : 0, append: jest.fn() };
				}
				if ( selector.indexOf( 'label .required' ) !== -1 ) {
					return { length: options.requiredMark ? 1 : 0, remove: jest.fn() };
				}
				return { length: 0, remove: jest.fn(), append: jest.fn() };
			} ),
		} );

		return { field, input };
	}

	// A field rendered by the server, optionally with the markers
	// woocommerce_form_field() would attach for a required or optional field.
	function createRenderedField( { required } ) {
		return createFieldMock( {
			required,
			optionalLabel: ! required,
			requiredMark: required,
		} );
	}

	beforeEach( () => {
		bodyEventHandlers = {};

		fields = {
			city: createRenderedField( { required: false } ),
			phone: createRenderedField( { required: false } ),
		};

		const thisform = {
			find: jest.fn( ( selector ) => {
				if ( selector.indexOf( 'postcode' ) !== -1 ) {
					return createFieldMock().field;
				}
				if ( selector.indexOf( 'city' ) !== -1 ) {
					return fields.city.field;
				}
				if ( selector.indexOf( 'state' ) !== -1 ) {
					return createFieldMock().field;
				}
				if ( selector.indexOf( 'phone' ) !== -1 ) {
					return fields.phone.field;
				}
				return createFieldMock().field;
			} ),
		};
		// Expose the wrapper so a test can fire the handler against it.
		fields.thisform = thisform;

		const mockBody = {
			on: jest.fn( ( event, handler ) => {
				bodyEventHandlers[ event ] = handler;
				return mockBody;
			} ),
			trigger: jest.fn( () => mockBody ),
		};

		const jQueryMock = jest.fn( ( selectorOrCallback ) => {
			if ( typeof selectorOrCallback === 'function' ) {
				selectorOrCallback( jQueryMock );
				return jQueryMock;
			}
			if ( selectorOrCallback === document.body ) {
				return mockBody;
			}
			// The fieldset sort block: nothing under test, so each() is a no-op.
			return { each: jest.fn() };
		} );
		jQueryMock.each = jest.fn( ( object, callback ) => {
			Object.keys( object ).forEach( ( key ) =>
				callback( key, object[ key ] )
			);
			return object;
		} );
		jQueryMock.extend = jest.fn( ( deep, target, ...sources ) => {
			sources.forEach( ( source ) => {
				if ( source && typeof source === 'object' ) {
					Object.assign( target, source );
				}
			} );
			return target;
		} );

		global.window.jQuery = jQueryMock;
		global.window.$ = jQueryMock;
		global.jQuery = jQueryMock;
		global.$ = jQueryMock;

		global.window.wc_address_i18n_params = {
			locale: JSON.stringify( localeFixture ),
			locale_fields: JSON.stringify( localeFieldsFixture ),
			i18n_optional_text: 'optional',
		};

		jest.resetModules();
		require( '../address-i18n' );

		countryToStateChangingHandler =
			bodyEventHandlers[ 'country_to_state_changing' ];
	} );

	afterEach( () => {
		delete global.window.wc_address_i18n_params;
		jest.clearAllMocks();
	} );

	function selectCountry( country ) {
		if ( ! countryToStateChangingHandler ) {
			throw new Error( 'country_to_state_changing handler was not bound' );
		}
		countryToStateChangingHandler( {}, country, fields.thisform );
	}

	test( 'keeps an optional phone field optional when the country changes', () => {
		// The locale says phone is required, but the merchant set it optional
		// with a filter, so the server rendered it optional. Changing the
		// country must not re-add required.
		selectCountry( 'AL' );

		expect( fields.phone.field.addClass ).not.toHaveBeenCalledWith(
			'validate-required'
		);
		expect( fields.phone.field.hasClass( 'validate-required' ) ).toBe( false );
		expect( fields.phone.field.hide ).not.toHaveBeenCalled();
	} );

	test( 'leaves a required phone field required when the country changes', () => {
		fields.phone = createRenderedField( { required: true } );
		fields.thisform.find = jest.fn( ( selector ) => {
			if ( selector.indexOf( 'phone' ) !== -1 ) {
				return fields.phone.field;
			}
			return createFieldMock().field;
		} );

		selectCountry( 'AL' );

		expect( fields.phone.field.hasClass( 'validate-required' ) ).toBe( true );
		expect( fields.phone.field.removeClass ).not.toHaveBeenCalled();
		expect( fields.phone.field.hide ).not.toHaveBeenCalled();
	} );

	test( 'clears required and hides the phone field when the locale hides it', () => {
		fields.phone = createRenderedField( { required: true } );
		fields.thisform.find = jest.fn( ( selector ) => {
			if ( selector.indexOf( 'phone' ) !== -1 ) {
				return fields.phone.field;
			}
			return createFieldMock().field;
		} );

		selectCountry( 'ZZ' );

		expect( fields.phone.field.removeClass ).toHaveBeenCalledWith(
			'validate-required woocommerce-invalid woocommerce-invalid-required-field'
		);
		expect( fields.phone.field.hasClass( 'validate-required' ) ).toBe( false );
		expect( fields.phone.field.hide ).toHaveBeenCalled();
	} );

	test( 'restores required after leaving a locale that hides the phone field', () => {
		// A required phone is hidden by ZZ, which clears required, then the
		// shopper picks AL, which does not hide it. The server-rendered state
		// must come back, otherwise required is lost for the rest of the session.
		fields.phone = createRenderedField( { required: true } );
		fields.thisform.find = jest.fn( ( selector ) => {
			if ( selector.indexOf( 'phone' ) !== -1 ) {
				return fields.phone.field;
			}
			return createFieldMock().field;
		} );

		selectCountry( 'ZZ' );
		expect( fields.phone.field.hasClass( 'validate-required' ) ).toBe( false );

		selectCountry( 'AL' );

		expect( fields.phone.field.hasClass( 'validate-required' ) ).toBe( true );
		expect( fields.phone.field.hide ).toHaveBeenCalledTimes( 1 );
		expect( fields.phone.field.show ).toHaveBeenCalled();
	} );

	test( 'does not re-add required to an optional phone after a hidden locale', () => {
		selectCountry( 'ZZ' );
		selectCountry( 'AL' );

		expect( fields.phone.field.addClass ).not.toHaveBeenCalledWith(
			'validate-required'
		);
		expect( fields.phone.field.hasClass( 'validate-required' ) ).toBe( false );
	} );

	test( 'still applies required to non-phone fields from the locale', () => {
		// Guards the phone-only scope of the change: city is rendered optional
		// and the locale default requires it, so a country change re-adds it.
		selectCountry( 'AL' );

		expect( fields.city.field.addClass ).toHaveBeenCalledWith(
			'validate-required'
		);
	} );
} );
