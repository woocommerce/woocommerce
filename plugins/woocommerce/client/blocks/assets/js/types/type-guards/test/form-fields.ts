/**
 * Internal dependencies
 */
import { isFormFields } from '../form-fields';

describe( 'isFormFields', () => {
	it( 'should return true for valid FormFields object', () => {
		const validFormFields = {
			email: {
				label: 'Email address',
				optionalLabel: 'Email address (optional)',
				required: true,
				hidden: false,
				index: 0,
			},
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
		};

		expect( isFormFields( validFormFields ) ).toBe( true );
	} );

	it( 'should return true for FormFields with additional optional properties', () => {
		const formFieldsWithExtras = {
			email: {
				label: 'Email address',
				optionalLabel: 'Email address (optional)',
				required: true,
				hidden: false,
				index: 0,
				type: 'email',
				autocomplete: 'email',
				validation: [],
			},
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
				autocomplete: 'given-name',
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
			company: {
				label: 'Company',
				optionalLabel: 'Company (optional)',
				required: false,
				hidden: true,
				index: 30,
			},
		};

		expect( isFormFields( formFieldsWithExtras ) ).toBe( true );
	} );

	it( 'should return false for null', () => {
		expect( isFormFields( null ) ).toBe( false );
	} );

	it( 'should return false for undefined', () => {
		expect( isFormFields( undefined ) ).toBe( false );
	} );

	it( 'should return false for false (the original bug case)', () => {
		expect( isFormFields( false ) ).toBe( false );
	} );

	it( 'should return false for arrays', () => {
		expect( isFormFields( [] ) ).toBe( false );
		expect( isFormFields( [ 'test' ] ) ).toBe( false );
	} );

	it( 'should return false for primitives', () => {
		expect( isFormFields( 'string' ) ).toBe( false );
		expect( isFormFields( 123 ) ).toBe( false );
		expect( isFormFields( true ) ).toBe( false );
	} );

	it( 'should return false for empty object', () => {
		expect( isFormFields( {} ) ).toBe( false );
	} );

	it( 'should return false when missing required fields', () => {
		// Missing first_name
		const missingFirstName = {
			email: {
				label: 'Email address',
				optionalLabel: 'Email address (optional)',
				required: true,
				hidden: false,
				index: 0,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
		};
		expect( isFormFields( missingFirstName ) ).toBe( false );

		// Missing email
		const missingEmail = {
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
		};
		expect( isFormFields( missingEmail ) ).toBe( false );
	} );

	it( 'should return false when field values are not objects', () => {
		const invalidFieldValue = {
			email: 'invalid string instead of object',
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
		};
		expect( isFormFields( invalidFieldValue ) ).toBe( false );
	} );

	it( 'should return false when field has invalid property types', () => {
		// Invalid label type
		const invalidLabel = {
			email: {
				label: 123, // should be string
				optionalLabel: 'Email address (optional)',
				required: true,
				hidden: false,
				index: 0,
			},
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
		};
		expect( isFormFields( invalidLabel ) ).toBe( false );

		// Invalid required type
		const invalidRequired = {
			email: {
				label: 'Email address',
				optionalLabel: 'Email address (optional)',
				required: 'true', // should be boolean
				hidden: false,
				index: 0,
			},
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
		};
		expect( isFormFields( invalidRequired ) ).toBe( false );

		// Invalid index type
		const invalidIndex = {
			email: {
				label: 'Email address',
				optionalLabel: 'Email address (optional)',
				required: true,
				hidden: false,
				index: '0', // should be number
			},
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
		};
		expect( isFormFields( invalidIndex ) ).toBe( false );
	} );

	it( 'should return false when field has null values', () => {
		const nullFieldValue = {
			email: null,
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
		};
		expect( isFormFields( nullFieldValue ) ).toBe( false );
	} );

	it( 'should validate optional field properties when present', () => {
		const fieldsWithOptionalProps = {
			email: {
				label: 'Email address',
				optionalLabel: 'Email address (optional)',
				required: true,
				hidden: false,
				index: 0,
				type: 'email',
				autocomplete: 'email',
				autocapitalize: 'none',
			},
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
				autocomplete: 'given-name',
				autocapitalize: 'sentences',
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
		};
		expect( isFormFields( fieldsWithOptionalProps ) ).toBe( true );
	} );

	it( 'should reject fields with wrong optional property types', () => {
		const invalidAutocomplete = {
			email: {
				label: 'Email address',
				optionalLabel: 'Email address (optional)',
				required: true,
				hidden: false,
				index: 0,
				autocomplete: 123, // should be string
			},
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
		};
		expect( isFormFields( invalidAutocomplete ) ).toBe( false );
	} );

	it( 'should validate email field type if present', () => {
		const wrongEmailType = {
			email: {
				label: 'Email address',
				optionalLabel: 'Email address (optional)',
				required: true,
				hidden: false,
				index: 0,
				type: 'text', // should be 'email' when specified
			},
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
		};
		expect( isFormFields( wrongEmailType ) ).toBe( false );
	} );

	it( 'should validate phone field type if present', () => {
		const validWithPhone = {
			email: {
				label: 'Email address',
				optionalLabel: 'Email address (optional)',
				required: true,
				hidden: false,
				index: 0,
			},
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
			},
			phone: {
				label: 'Phone',
				optionalLabel: 'Phone (optional)',
				required: false,
				hidden: false,
				index: 100,
				type: 'tel',
			},
		};
		expect( isFormFields( validWithPhone ) ).toBe( true );

		const wrongPhoneType = {
			...validWithPhone,
			phone: {
				...validWithPhone.phone,
				type: 'text', // should be 'tel'
			},
		};
		expect( isFormFields( wrongPhoneType ) ).toBe( false );
	} );

	it( 'should accept fields with validation arrays', () => {
		const fieldsWithValidation = {
			email: {
				label: 'Email address',
				optionalLabel: 'Email address (optional)',
				required: true,
				hidden: false,
				index: 0,
				validation: [],
			},
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				index: 10,
				validation: [ { type: 'string', minLength: 1 } ],
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				index: 20,
				validation: false,
			},
		};
		expect( isFormFields( fieldsWithValidation ) ).toBe( true );
	} );

	it( 'should accept complete core fields structure matching PHP', () => {
		// This matches exactly what CheckoutFields::get_core_fields() returns
		const completeCoreFields = {
			email: {
				label: 'Email address',
				optionalLabel: 'Email address (optional)',
				required: true,
				hidden: false,
				autocomplete: 'email',
				autocapitalize: 'none',
				type: 'email',
				index: 0,
			},
			country: {
				label: 'Country/Region',
				optionalLabel: 'Country/Region (optional)',
				required: true,
				hidden: false,
				autocomplete: 'country',
				index: 1,
			},
			first_name: {
				label: 'First name',
				optionalLabel: 'First name (optional)',
				required: true,
				hidden: false,
				autocomplete: 'given-name',
				autocapitalize: 'sentences',
				index: 10,
			},
			last_name: {
				label: 'Last name',
				optionalLabel: 'Last name (optional)',
				required: true,
				hidden: false,
				autocomplete: 'family-name',
				autocapitalize: 'sentences',
				index: 20,
			},
			company: {
				label: 'Company',
				optionalLabel: 'Company (optional)',
				required: false,
				hidden: true,
				autocomplete: 'organization',
				autocapitalize: 'sentences',
				index: 30,
			},
			address_1: {
				label: 'Address',
				optionalLabel: 'Address (optional)',
				required: true,
				hidden: false,
				autocomplete: 'address-line1',
				autocapitalize: 'sentences',
				index: 40,
			},
			address_2: {
				label: 'Apartment, suite, etc.',
				optionalLabel: 'Apartment, suite, etc. (optional)',
				required: false,
				hidden: false,
				autocomplete: 'address-line2',
				autocapitalize: 'sentences',
				index: 50,
			},
			city: {
				label: 'City',
				optionalLabel: 'City (optional)',
				required: true,
				hidden: false,
				autocomplete: 'address-level2',
				autocapitalize: 'sentences',
				index: 70,
			},
			state: {
				label: 'State/County',
				optionalLabel: 'State/County (optional)',
				required: true,
				hidden: false,
				autocomplete: 'address-level1',
				autocapitalize: 'sentences',
				index: 80,
			},
			postcode: {
				label: 'Postal code',
				optionalLabel: 'Postal code (optional)',
				required: true,
				hidden: false,
				autocomplete: 'postal-code',
				autocapitalize: 'characters',
				index: 90,
			},
			phone: {
				label: 'Phone',
				optionalLabel: 'Phone (optional)',
				required: true,
				hidden: false,
				type: 'tel',
				autocomplete: 'tel',
				autocapitalize: 'characters',
				index: 100,
			},
		};
		expect( isFormFields( completeCoreFields ) ).toBe( true );
	} );
} );
