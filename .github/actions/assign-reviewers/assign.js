/**
 * Reviewer assignment for .github/workflows/automate-team-review-assignment.yml.
 *
 * Run the checks with:
 *   node .github/actions/assign-reviewers/test.js
 */

const fs = require( 'fs' );
const path = require( 'path' );

// path.matchesGlob() has no `dot` option, so a leaf such as
// `packages/js/components/.npmrc` would never match `**/*`. Renaming every
// segment-leading dot on both sides makes those segments ordinary again.
// The one case this cannot reproduce is a wildcard matching the empty string
// before a literal dot, so a file named exactly `.js` does not match
// `*{.js,.ts}`. Beware too when testing a pattern locally: path.matchesGlob()
// is case-insensitive on macOS and Windows, and case-sensitive on Linux.
const undot = ( value ) => value.replace( /(^|\/)\./g, '$1__dot__' );

// Whether a dot inside `{...}` starts a segment depends on where the group
// sits, so expand the groups first and rename each result on its own.
const expand = ( pattern ) => {
	const group = pattern.match( /\{([^{}]*)\}/ );

	if ( ! group ) {
		return [ undot( pattern ) ];
	}

	return group[ 1 ].split( ',' ).flatMap( ( alternative ) => expand(
		pattern.slice( 0, group.index ) + alternative + pattern.slice( group.index + group[ 0 ].length )
	) );
};

/**
 * Whether a pattern matches any of the given paths.
 *
 * @param {string}   pattern  A config key, as a glob.
 * @param {string[]} undotted Paths already passed through undot().
 * @return {boolean} True when at least one path matches.
 */
const matchesAny = ( pattern, undotted ) => {
	const globs = expand( pattern );

	return undotted.some( ( file ) => globs.some( ( glob ) => path.matchesGlob( file, glob ) ) );
};

/**
 * Reads the config and returns a lookup for the reviewers a key names.
 *
 * A value is one reviewer or a list of them. A bare value is a team slug,
 * `@login` marks a person. Every key is checked on load, so a bad edit fails
 * on the first run rather than when a pull request first touches its area.
 *
 * @param {string} file Path to the config, relative to the workspace.
 * @return {{ config: Object, owners: Function }} The parsed config and its lookup.
 */
const readConfig = ( file ) => {
	const config = JSON.parse( fs.readFileSync( file, 'utf8' ) );

	if ( ! config || typeof config !== 'object' || Array.isArray( config ) ) {
		throw new Error( 'The reviewer config must be a JSON object.' );
	}

	const owners = ( key ) => {
		const list = [].concat( config[ key ] );

		if ( ! list.length || list.some( ( owner ) => typeof owner !== 'string' || ! owner ) ) {
			throw new Error( `Config key '${ key }' does not name a reviewer.` );
		}

		return list;
	};

	Object.keys( config ).forEach( owners );

	return { config, owners };
};

/**
 * Requests reviews on the pull request that triggered the workflow.
 *
 * @param {Object} toolkit         The github-script toolkit.
 * @param {Object} toolkit.github  A configured octokit.
 * @param {Object} toolkit.context The workflow context.
 * @param {Object} toolkit.core    The actions core helpers.
 * @return {Promise<void>} Resolves once the reviews are requested.
 */
const assignReviewers = async ( { github, context, core } ) => {
	const { config, owners } = readConfig( process.env.CONFIG_FILE );
	const author = context.payload.pull_request.user.login;
	const pull = {
		owner: context.repo.owner,
		repo: context.repo.repo,
		pull_number: context.payload.pull_request.number,
	};

	// Both routers return the config values of the keys they matched. Every
	// match counts, so a pull request spanning several areas collects every
	// owner.
	const byChangedFiles = async () => {
		const files = await github.paginate( github.rest.pulls.listFiles, { ...pull, per_page: 100 } );
		const changed = files.map( ( file ) => undot( file.filename ) );

		return Object.keys( config )
			.filter( ( pattern ) => matchesAny( pattern, changed ) )
			.flatMap( owners );
	};

	const byAuthorTeam = async () => {
		const isOnTeam = async ( team ) => {
			try {
				const { data } = await github.rest.teams.getMembershipForUserInOrg( {
					org: context.repo.owner,
					team_slug: team,
					username: author,
				} );

				return data.state !== 'pending';
			} catch ( error ) {
				const message = `Could not read ${ author }'s membership of ${ team }: ${ error.message }`;

				// 404 just means "not a member". Anything else means the lookup
				// failed, so the routing below would quietly miss a team. Say so
				// rather than assigning nobody on a green run.
				if ( error.status === 404 ) {
					core.info( message );
				} else {
					core.setFailed( message );
				}

				return false;
			}
		};

		const matched = [];

		for ( const team of Object.keys( config ) ) {
			if ( await isOnTeam( team ) ) {
				matched.push( ...owners( team ) );
			}
		}

		return matched;
	};

	const routers = { 'changed-files': byChangedFiles, 'author-team': byAuthorTeam };
	const mode = process.env.MODE;

	if ( ! Object.hasOwn( routers, mode ) ) {
		throw new Error( `Unknown mode '${ mode }'.` );
	}

	const reviewers = new Set();
	const teams = new Set();

	for ( const owner of await routers[ mode ]() ) {
		if ( ! owner.startsWith( '@' ) ) {
			teams.add( owner );
		} else if ( owner.slice( 1 ).toLowerCase() !== author.toLowerCase() ) {
			reviewers.add( owner.slice( 1 ) );
		}
	}

	const matched = [ ...teams, ...[ ...reviewers ].map( ( reviewer ) => `@${ reviewer }` ) ];

	core.info( `Matched reviewers: ${ matched.join( ', ' ) || 'nobody' }.` );

	if ( ! reviewers.size && ! teams.size ) {
		return;
	}

	const request = ( body ) => github.rest.pulls.requestReviewers( { ...pull, ...body } );

	try {
		await request( { reviewers: [ ...reviewers ], team_reviewers: [ ...teams ] } );

		return;
	} catch ( error ) {
		// 422 is the API rejecting the whole batch over a single bad entry, so
		// ask one at a time instead of losing every reviewer to it. Any other
		// status is about the token or the rate limit, and asking again would
		// only spend more of the rate limit on the same answer.
		if ( error.status !== 422 ) {
			throw error;
		}

		core.warning( `Could not request the reviews together: ${ error.message }` );
	}

	const rejected = [];
	const failed = [];

	const alone = async ( body, name ) => request( body ).then(
		() => {},
		( error ) => {
			// 422 is the API saying it will not accept this reviewer, which means
			// the config names somebody who no longer exists or lost access. Every
			// other status is about the token or the rate limit, not the config.
			( error.status === 422 ? rejected : failed ).push( name );

			core.warning( `Could not request a review from ${ name }: ${ error.message }` );
		}
	);

	for ( const team of teams ) {
		await alone( { team_reviewers: [ team ] }, team );
	}

	for ( const reviewer of reviewers ) {
		await alone( { reviewers: [ reviewer ] }, reviewer );
	}

	if ( rejected.length ) {
		core.setFailed( `The API rejected these reviewers, check the config: ${ rejected.join( ', ' ) }.` );
	}

	if ( failed.length ) {
		core.setFailed( `Could not request a review from: ${ failed.join( ', ' ) }.` );
	}
};

module.exports = { assignReviewers, undot, matchesAny };
