/**
 * Suppress console messages
 */

const messagesToSuppress = [];

/**
 * Check whether the text is from a console message that should be suppressed
 *
 * @param text
 * @returns {boolean}
 */
const consoleShouldSuppress = ( text ) => {
	let shouldSuppress = false;
	for ( let m = 0; m < messagesToSuppress.length; m++ ) {
		if ( text.indexOf( messagesToSuppress[ m ].text ) < 0 ) {
			continue;
		}

		shouldSuppress = messagesToSuppress[ m ].logged;
		messagesToSuppress[ m ].logged = true;
		break;
	}
	return shouldSuppress;
};

/**
 * Add a partial string match to suppress console logging of repetitive messages
 * @param searchString
 * @param logFirstInstance
 */
const addConsoleSuppression = ( searchString, logFirstInstance = true ) => {
	messagesToSuppress.push( {
		text: searchString,
		logged: ! Boolean( logFirstInstance ),
	});
};

/**
 * Remove a partial string match from suppressed console logging
 * @param searchString
 */
const removeConsoleSuppression = ( searchString ) => {
	let toRemove = -1;
	for ( let m = 0; m < messagesToSuppress.length; m++ ) {
		if ( messagesToSuppress[ m ].text == searchString ) {
			toRemove = m;
			break;
		}
	}
	if ( toRemove >= 0 ) {
		messagesToSuppress.splice( toRemove, 1 );
	}
};

module.exports = {
	consoleShouldSuppress,
	addConsoleSuppression,
	removeConsoleSuppression,
};
