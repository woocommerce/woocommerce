const { LANGUAGE } = process.env;

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

function getTranslationFor(textToTranslate) {
	let langCode = 'en_US';

	if ( LANGUAGE ) {
		if ( [ 'en_US', 'en_GB', 'es_ES', 'fr_FR', 'ar_AR' ].includes( LANGUAGE ) ) {
			langCode = LANGUAGE;
		} else {
			console.log( 'LANGUAGE input must be ar_AR, en_GB, en_US, es_ES or fr_FR' );
		}
	}
	return languageObject[ langCode ][ textToTranslate ];
}

module.exports = {
	getTranslationFor,
};