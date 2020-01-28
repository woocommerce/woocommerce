'use strict';

const pkg = require( '../../package.json' );
const Config = require( 'merge-config' );

const config = new Config();

const changelogSrcTypes = {
	MILESTONE: 'MILESTONE',
	ZENHUB: 'ZENHUB_RELEASE',
};

const DEFAULTS = {
	labelPrefix: 'type:',
	skipLabel: 'no-changelog',
	defaultPrefix: 'dev',
	changelogSrcType: changelogSrcTypes.MILESTONE,
	devNoteLabel: 'dev-note',
	repo: '',
	ghApiToken: '',
	zhApiToken: '',
};

pkg.changelog = pkg.changelog || DEFAULTS;

config.merge( { ...DEFAULTS, ...pkg.changelog } );
config.env( [ 'GH_API_TOKEN', 'ZH_API_TOKEN' ] );
config.argv( Object.keys( DEFAULTS ) );

const REPO = config.get( 'repo' );

if ( ! REPO ) {
	throw new Error(
		"The 'repo' configuration value is not set. This script requires the\n" +
			'repository namespace used as the source for the changelog entries.'
	);
}

module.exports = {
	pkg: {
		...pkg,
		changelog: config.get(),
	},
	REPO,
	changelogSrcTypes,
};
