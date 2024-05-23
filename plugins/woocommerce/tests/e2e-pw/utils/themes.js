const { exec } = require( 'node:child_process' );
const qit = require('/qitHelpers');

export const DEFAULT_THEME = 'twentytwentythree';

export const activateTheme = ( themeName ) => {
	return qit.wp( `theme activate ${ themeName }` );
};
