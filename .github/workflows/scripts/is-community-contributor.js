const getUserFromContext = ( types, context ) => {
	for ( const type of types ) {
		if ( context.payload[ type ] && context.payload[ type ].user && context.payload[ type ].user.login ) {
			return context.payload[ type ].user;
		}
	}
	return false;
};

const userIsBot = user => user.type && user.type === 'Bot';

const userInOrg = async ( github, username, org ) => {
	console.log( 'Checking for user %s in org %s', username, org );
	try {
		// First attempt to check memberships.
		const result = await github.request( 'GET /orgs/{org}/memberships/{username}', {
			org,
			username,
		} );

		if ( result && Math.floor( result.status / 100 ) === 2 ) {
			console.log( 'User %s found in %s via membership check', username, org );
			return true;
		}
	} catch ( error ) {
		// A 404 status means the user is definitively not in the organization.
		if ( error.status === 404 ) {
			console.log( 'User %s NOT found in %s via membership check', username, org );
			return false;
		}

		try {
			// For anything else, we should also check public memberships.
			const result = await github.request( 'GET /orgs/{org}/public_members/{username}', {
				org,
				username,
			} );

			if ( result && Math.floor( result.status / 100 ) === 2 ) {
				console.log( 'User %s found in %s via public membership check', username, org );
				return true;
			}
		} catch ( error ) {
			if ( error.status === 404 ) {
				console.log( 'User %s NOT found in %s via public membership check', username, org );
				return false;
			}
		}
	}
	// If we've gotten to this point, status is unknown.
	throw new Error( "Unable to determine user's membership in org" );
};

const userInNonCommunityOrgs = async ( orgs, github, username ) => {
	let checked = 0;
	for ( const org of orgs ) {
		try {
			const isUserInOrg = await userInOrg( github, username, org );
			if ( isUserInOrg ) {
				return true;
			}
			// If no error was thrown, increment checked.
			checked++;
		} catch( e ) {} // Do nothing.
	}

	if ( checked !== orgs.length ) {
		throw new Error( "Unable to check user's membership in all orgs." );
	}

	return false;
};

const isCommunityContributor = async ( { github, context, config } ) => {
	const user = getUserFromContext( config.types, context );
	if ( ! user ) {
		throw new Error( 'Unable to get user from context' );
	}
	console.log( 'Checking user %s', user.login );

	if ( userIsBot( user ) ) {
		// Bots are not community contributors.
		console.log( 'User detected as bot, skipping' );
		return false;
	}

	const isInNonCommunityOrgs = await userInNonCommunityOrgs( config.orgs, github, user.login );

	return ! isInNonCommunityOrgs;
};

module.exports = isCommunityContributor;