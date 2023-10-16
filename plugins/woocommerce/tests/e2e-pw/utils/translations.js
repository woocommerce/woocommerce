const { LANGUAGE } = process.env;
const { test, expect } = require( '@playwright/test' );

// If a value is passed for LANGAUGE then it can only be one of 'en_US', 'en_GB', 'es_ES', 'fr_FR', 'ar_AR'
if (
	LANGUAGE &&
	! [ 'en_US', 'en_GB', 'es_ES', 'fr_FR', 'ar_AR' ].includes( LANGUAGE )
) {
	console.log( 'LANGUAGE input must be ar_AR, en_GB, en_US, es_ES or fr_FR' );
	process.exit( 1 );
}

const { ar_AR } = require( './../test-data/language/ar-AR' );
const { en_GB } = require( './../test-data/language/en-GB' );
const { en_US } = require( './../test-data/language/en-US' );
const { es_ES } = require( './../test-data/language/es-ES' );
const { fr_FR } = require( './../test-data/language/fr-FR' );

const languageObject = {
	ar_AR,
	en_US,
	en_GB,
	es_ES,
	fr_FR,
};

function getTranslationFor( textToTranslate ) {
	let langCode = 'en_US';

	// if LANGUAGE variable is set then perform translation
	if ( LANGUAGE ) {
		langCode = LANGUAGE;
	}

	// if no translation exists then return the original value
	const translatedValue = languageObject[ langCode ][ textToTranslate ];
	const returnValue = translatedValue ? translatedValue : textToTranslate;

	return returnValue;
}

class LocatorWrapper {
	constructor( originalLocator ) {
		this.originalLocator = originalLocator;
	}

	async filter( options ) {
		console.log( 'Custom logic before calling the original filter method' );

		// Your custom logic here
		console.log( `Filtering with options: ${ JSON.stringify( options ) }` );

		// Example: Modify options or perform checks
		console.log( 'options=', options );

		if ( options.hasText ) {
			console.log( 'options.hasText=', options.hasText );
			const translatedOptionName = getTranslationFor( options.hasText );
			console.log( 'translatedOptionName=', translatedOptionName );
			options.hasText = translatedOptionName
				? translatedOptionName
				: options.hasText;
			console.log( 'options.hasText after=', options.hasText );
			console.log( 'options after=', options );
		}
		console.log( 'options after=', options );

		// Your custom logic here
		console.log( `Filtering with options: ${ JSON.stringify( options ) }` );

		// Assuming your custom filter logic returns an ElementHandle
		const filterLocator = await this.originalLocator.filter( options );
		console.log( 'filterLocator=', filterLocator );
		console.log( 'typeof filterLocator=', typeof filterLocator );
		console.log( 'filterLocator=', filterLocator );
		console.log( 'typeof filterLocator.elementHandle', typeof filterLocator.elementHandle );

		//const locator = this.originalLocator._page.locator(selector);
		return filterLocator;
	}

	// Forward other methods to the original locator
	async fill( value ) {
		return await this.originalLocator.fill( value );
	}
	async blur() {
		return await this.originalLocator.blur();
	}
	async click() {
		return await this.originalLocator.click();
	}
}

const testWithTranslation = test.extend( {
	page: async ( { page }, use ) => {
		console.log( 'Inside testWithTranslation' );
		if ( LANGUAGE ) {
			console.log( 'LANGUAGE=', LANGUAGE );
		}

		// Backup the original locator function
		const originalLocator = page.locator.bind( page );
		const originalGetByRole = page.getByRole.bind( page );
		const originalGetByPlaceholder = page.getByPlaceholder.bind( page );
		const originalGetByText = page.getByText.bind( page );

		// Override the page.locator function
		page.locator = ( selector ) => {
			const original = originalLocator( selector );

			//this overridden getByRole never seems to be called
			original.getByRole = ( role, options ) => {
				console.log( `Custom getByRole using role: ${ role }` );
				console.log( `Custom getByRole using options: ${ options }` );

				return original.getByRole( role, options );
			};

			// Custom logic: Check or modify the selector, log information, etc.
			console.log( `Testwithtranslation Using selector: ${ selector }` );

			const textPrefix = '=';
			if ( selector.includes( textPrefix ) ) {
				// Find the position of '='
				const textPosition = selector.indexOf( textPrefix );
				const initialText = selector.substring(
					0,
					textPosition + textPrefix.length
				);
				const extractedText = selector.substring(
					textPosition + textPrefix.length
				);
				const translatedText = getTranslationFor( extractedText );
				console.log( 'textPosition=', textPosition );
				console.log( 'initialText=', initialText );
				console.log( 'extractedText=', extractedText );

				selector = translatedText
					? initialText + translatedText
					: initialText + extractedText;
			} else {
				console.log( 'selector does not include text=' );
				const translatedText = getTranslationFor( selector );
				console.log( 'translatedText=', translatedText );

				selector = translatedText ? translatedText : selector;
			}

			console.log( 'selector=', selector );
			console.log( 'return originalLocator' );
			// Use the original locator function with possibly modified selector
			//return originalLocator( selector );

			// Use the original locator function to get a Locator object
			const locator = originalLocator( selector );

			// Wrap the Locator object with your custom wrapper
			return new LocatorWrapper( locator );
		};

		// Override the page.getByRole function
		page.getByRole = function ( role, options ) {
			console.log( `Testwithtranslation Using role: ${ role }` );

			// Example: Modify options or perform checks
			// options = yourCustomLogic(options);
			console.log( 'options=', options );

			if ( options.name ) {
				console.log( 'options.name=', options.name );
				const translatedOptionName = getTranslationFor( options.name );
				console.log( 'translatedOptionName=', translatedOptionName );
				options.name = translatedOptionName
					? translatedOptionName
					: options.name;
				console.log( 'options.name after=', options.name );
			}
			console.log( 'options after=', options );

			// Use the original getByRole function with possibly altered role or options
			return originalGetByRole( role, options );
		};

		// Override the page.getByPlaceholder function
		page.getByPlaceholder = ( placeholder ) => {
			console.log( `Using placeholder: ${ placeholder }` );

			const translatedText = getTranslationFor( placeholder );
			console.log( 'translatedText=', translatedText );

			placeholder = translatedText ? translatedText : placeholder;
			console.log( 'placeholder=', placeholder );

			return originalGetByPlaceholder( placeholder );
		};

		// Override the page.getByPlaceholder function
		page.getByText = ( text ) => {
			console.log( `Using text: ${ text }` );

			const translatedText = getTranslationFor( text );
			console.log( 'translatedText=', translatedText );

			text = translatedText ? translatedText : text;
			console.log( 'text=', text );

			return originalGetByText( text );
		};

		console.log( 'await use( page )' );
		await use( page );

		console.log( 'page.locator = originalLocator' );
		// Restore the original locator functions after the test
		page.locator = originalLocator;
		page.getByRole = originalGetByRole;
		page.getByPlaceholder = originalGetByPlaceholder;
		page.getByText = originalGetByText;
	},
} );

// custom Expect
const expectWithTranslation = expect.extend( {
	async toHaveValue( received, argument ) {
		// Your custom logic here
		console.log( `Expecting value: ${ argument }` );

		// Retrieve the value of the element
		const actualValue = await received.inputValue();
		console.log( `actualValue: ${ actualValue }` );

		const translatedValue = getTranslationFor( argument );
		console.log( `translatedValue: ${ translatedValue }` );

		// You might delegate to the original toHaveValue or create your custom assertion logic
		const pass = actualValue === translatedValue;

		if ( pass ) {
			return {
				message: () =>
					`expected ${ received } not to have value ${ argument }`,
				pass: true,
			};
		} else {
			return {
				message: () =>
					`expected ${ received } to have value ${ argument }`,
				pass: false,
			};
		}
	},
	async toContainText( received, argument ) {
		// Your custom logic here
		console.log( `Expecting to contain text: ${ argument }` );

		// Retrieve the text content of the element
		const actualText = await received.textContent();

		const translatedValue = getTranslationFor( argument );
		console.log( `translatedValue: ${ translatedValue }` );

		// Check if the actual text contains the expected text snippet
		const pass = actualText.includes( translatedValue );

		if ( pass ) {
			return {
				message: () =>
					`expected ${ received } not to contain text ${ argument }`,
				pass: true,
			};
		} else {
			return {
				message: () =>
					`expected ${ received } to contain text ${ argument }`,
				pass: false,
			};
		}
	},
} );

module.exports = {
	getTranslationFor,
	testWithTranslation,
	expectWithTranslation,
};
