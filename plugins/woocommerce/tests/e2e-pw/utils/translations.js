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

const testWithTranslation = test.extend( {
	page: async ( { page }, use ) => {
		console.log( 'Inside testWithTranslation' );
		if ( LANGUAGE ) {
			console.log( 'LANGUAGE=', LANGUAGE );
		}

		// Backup the original locator function
		const originalLocator = await page.locator.bind( page );
		const originalGetByRole = await page.getByRole.bind( page );
		const originalGetByPlaceholder = page.getByPlaceholder.bind( page );

		// Override the page.locator function
		page.locator = ( selector ) => {
			// Custom logic: Check or modify the selector, log information, etc.
			console.log( `Testwithtranslation Using selector: ${ selector }` );

			const textPrefix = '=';
			if ( selector.includes( textPrefix ) ) {
				// Find the position of 'text='
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
				console.log('selector does not include text=');
				const translatedText = getTranslationFor( selector );
				console.log( 'translatedText=', translatedText );
				
				selector = translatedText
					? translatedText
					: selector;
			}

			console.log( 'selector=', selector );
			console.log( 'return originalLocator' );
			// Use the original locator function with possibly modified selector
			return originalLocator( selector );
		};

		// Override the page.getByRole function
		page.getByRole = async (role, options) => {
			console.log(`Testwithtranslation Using role: ${role}`);

			// Example: Modify options or perform checks
			// options = yourCustomLogic(options);
			console.log('options=',options);

			// Use the original getByRole function with possibly altered role or options
			return originalGetByRole(role, options);
		};

		// Override the page.getByPlaceholder function
		page.getByPlaceholder = async (placeholder) => {
			console.log(`Using placeholder: ${placeholder}`);

			const translatedText = getTranslationFor( placeholder );
				console.log( 'translatedText=', translatedText );
				
				placeholder = translatedText
					? translatedText
					: placeholder;


			// // Override the fill method of the returned object
			// originalPlaceholderElement.fill = async (value) => {
			// 	console.log(`Filling placeholder "${placeholder}" with value: ${value}`);
				
			// 	// Add additional logic or modifications for 'fill' here...
			// 	// ...

			// 	// You might call the original fill method or define your custom logic for fill
			// 	return await originalPlaceholderElement.fill(value);
			// };

			// Use the original getByPlaceholder function with possibly modified placeholder
			return originalGetByPlaceholder(placeholder);
		};

		console.log( 'await use( page )' );
		await use( page );

		console.log( 'page.locator = originalLocator' );
		// Restore the original locator functions after the test
		page.locator = originalLocator;
		page.getByRole = originalGetByRole;
		page.getByPlaceholder = originalGetByPlaceholder;

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
	// async toHaveText( received, argument ) {
	// 	// Your custom logic here
	// 	console.log( `Expecting: ${ argument }` );

	// 	// Retrieve the text content of the element
	// 	const actualText = await received.textContent();

	// 	console.log( `actualText: ${ actualText }` );

	// 	const translatedValue = getTranslationFor( argument );
	// 	console.log( `translatedValue: ${ translatedValue }` );
	// 	console.log('received=',await received.textContent());
	// 	console.log('received=',await received.actualValue());
		

	// 	console.log('await received.textContent()=',await received.textContent());


	// 	// You might delegate to the original toHaveText or create your custom assertion logic
	// 	const pass = ( await received.textContent() ) === translatedValue;

	// 	if ( pass ) {
	// 		return {
	// 			message: () =>
	// 				`expected ${ received } not to have text ${ argument }`,
	// 			pass: true,
	// 		};
	// 	} else {
	// 		return {
	// 			message: () =>
	// 				`expected ${ received } to have text ${ argument }`,
	// 			pass: false,
	// 		};
	// 	}
	// },
	// async toBeVisible(received) {
	// 	// Your custom logic here
	// 	console.log(`Expecting element to be visible`);
	
	// 	// Using the Playwright `isVisible()` function to check visibility
	// 	const pass = await received.isVisible();
	
	// 	if (pass) {
	// 	  return {
	// 		message: () => `expected element not to be visible`,
	// 		pass: true,
	// 	  };
	// 	} else {
	// 	  return {
	// 		message: () => `expected element to be visible`,
	// 		pass: false,
	// 	  };
	// 	}
	//   },
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
