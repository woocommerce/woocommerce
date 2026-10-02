/**
 * @jest-environment jest-fixed-jsdom
 */

// Locale fixture. `default` mirrors a country with a required text state (for
// example Russia, which has no entry in states.php and no country locale).
// CY, US and AT are locale-hidden: their state is hidden no matter what the
// state input looks like, which is how core treats countries it does not
// collect a state for.
const localeFixture = {
	default: {
		city: { required: true },
		country: { required: true },
		postcode: { required: true },
		state: { required: true },
	},
	CY: { state: { required: false, hidden: true } },
	US: { state: { required: false, hidden: true } },
	AT: { state: { required: false, hidden: true } },
};

const localeFieldsFixture = {
	country: '#billing_country_field',
	postcode: '#billing_postcode_field',
	city: '#billing_city_field',
	state: '#billing_state_field',
};

describe( 'address-i18n state visibility', () => {
	let bodyEventHandlers;
	let countryToStateChangingHandler;
	let fields;

	// Build a chainable field mock. Every method returns the field so the
	// handler can keep chaining the way jQuery allows.
	function createFieldMock( options = {} ) {
		const field = {};
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
			addClass: jest.fn( () => field ),
			removeClass: jest.fn( () => field ),
			data: jest.fn( () => field ),
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
				if ( selector === 'input[type="hidden"]' ) {
					return { length: options.hiddenInput ? 1 : 0 };
				}
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
					selector.indexOf( 'label .required[aria-hidden="true"]' ) !==
					-1
				) {
					return { length: 1 };
				}
				if ( selector.indexOf( 'label .required' ) !== -1 ) {
					return { length: 1, remove: jest.fn() };
				}
				return { length: 0, remove: jest.fn(), append: jest.fn() };
			} ),
		} );

		return { field, input };
	}

	beforeEach( () => {
		bodyEventHandlers = {};

		// A country without a states.php entry keeps a text input, so the row
		// holds no hidden input. Options can flip that per test.
		fields = {
			city: createFieldMock(),
			country: createFieldMock(),
			postcode: createFieldMock(),
			state: createFieldMock(),
		};

		const thisform = {
			find: jest.fn( ( selector ) => {
				if ( selector.indexOf( 'postcode' ) !== -1 ) {
					return fields.postcode.field;
				}
				if ( selector.indexOf( 'city' ) !== -1 ) {
					return fields.city.field;
				}
				if ( selector.indexOf( 'state' ) !== -1 ) {
					return fields.state.field;
				}
				if ( selector.indexOf( 'country' ) !== -1 ) {
					return fields.country.field;
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

	test( 'shows the state row when moving from a locale-hidden country to one without a locale entry', () => {
		// CY is locale-hidden but has no states.php entry, so the state stays a
		// text input. Switching to RU (no locale entry, state required) used to
		// leave the row hidden, blocking checkout on a required field.
		selectCountry( 'CY' );
		fields.state.field.show.mockClear();
		fields.state.field.hide.mockClear();

		selectCountry( 'RU' );

		expect( fields.state.field.show ).toHaveBeenCalled();
		expect( fields.state.field.hide ).not.toHaveBeenCalled();
	} );

	test( 'hides and clears the state row when moving to a locale-hidden country', () => {
		selectCountry( 'US' );
		fields.state.field.show.mockClear();
		fields.state.field.hide.mockClear();
		fields.state.input.val.mockClear();

		selectCountry( 'CY' );

		expect( fields.state.field.hide ).toHaveBeenCalled();
		expect( fields.state.field.show ).not.toHaveBeenCalled();
		expect( fields.state.input.val ).toHaveBeenCalledWith( '' );
	} );

	test( 'leaves a hidden-input state row to country-select when the locale does not hide it', () => {
		// An empty states list renders a hidden input, and country-select owns
		// that row's visibility. address-i18n must not show it.
		fields.state = createFieldMock( { hiddenInput: true } );
		selectCountry( 'RU' );

		expect( fields.state.field.show ).not.toHaveBeenCalled();
	} );

	test( 'shows non-state fields the locale does not hide', () => {
		selectCountry( 'US' );
		fields.city.field.show.mockClear();

		selectCountry( 'RU' );

		expect( fields.city.field.show ).toHaveBeenCalled();
	} );

	test( 'hides non-state fields the locale marks hidden', () => {
		const hiddenLocale = {
			default: localeFixture.default,
			ZZ: { city: { hidden: true } },
		};
		global.window.wc_address_i18n_params.locale =
			JSON.stringify( hiddenLocale );
		jest.resetModules();
		require( '../address-i18n' );
		countryToStateChangingHandler =
			bodyEventHandlers[ 'country_to_state_changing' ];

		selectCountry( 'ZZ' );

		expect( fields.city.field.hide ).toHaveBeenCalled();
	} );
} );
