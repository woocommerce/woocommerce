/**
 * Uses the GitHub API to find the previous version of a given version.
 *
 * @param {string} version
 * @param {{ github: Octokit, context: { repo: { owner: string, repo: string } } }} options
 * @returns {Promise<string>} The previous version.
 */
const findPreviousVersion = async ( version, { github, context } ) => {
  if ( version.endsWith( '-dev' ) || version.endsWith( '-beta.1' ) ) {
    throw new Error( `Version '${ version }' is the first version in the cycle.` );
  }

  // Match all versions in cycle.
  const matchingRef = version.replace( /\.\d+(-.*)?$/, '' );
  var allTags = (
    await github.request(
      'GET /repos/{owner}/{repo}/git/matching-refs/tags/{ref}', {
        owner: context.repo.owner,
        repo: context.repo.repo,
        ref: matchingRef,
      }
    )
  ).data.map( ( t ) => t.ref.replace( 'refs/tags/', '' ) );

  // Filter out unnecessary tags.
  allTags = allTags.filter(
    ( t ) => {
      // If version is -rc.1 or a beta we want betas.
      if ( version.endsWith( '-rc.1' ) || version.includes( '-beta' ) ) {
        return t.includes( '-beta' );
      }

      // If version is .0 (stable) or an RC we want RCs.
      if ( version.endsWith( '.0' ) || version.includes( '-rc' ) ) {
        return t.includes( '-rc' );
      }

      return ! t.includes( '-' );
    }
  );

  if ( 0 === allTags.length) {
    throw new Error( `Cannot determine previous version for '${ version }'.` );
  }

  // Insert current version in array when necessary.
  if ( ! version.endsWith( '-rc.1' ) && ! version.endsWith( '.0' ) && ! allTags.includes( version ) ) {
    allTags.push( version );
  }

  console.log( allTags );

  // Sort tags.
  allTags.sort( (a, b) => Number( a.split( '.' ).at( -1 ) ) - Number( b.split( '.' ).at( -1 ) ) );

  return ( version.endsWith( '-rc.1' ) || version.endsWith( '.0' ) )
    ? allTags.at( -1 )
    : allTags.at( allTags.indexOf( version ) - 1 );
};

/**
 * Reads the `$db_updates` array from a file.
 * @param {string} path
 * @returns {Map<string, string[]>} The `$db_updates` array with version as key and set of callbacks.
 */
const readDbUpdatesFromString = ( fileContent ) => {
    const dbUpdatesMatch = fileContent.match( /\$db_updates\s*=\s*array\s*\((.*?)\);/s );
    const dbUpdatesContent = dbUpdatesMatch[1];

    const versionPattern = new RegExp( `['"](?<version>\\d+\\.\\d+\\.\\d+(?:-.*?)?)['"]\\s*=>\\s*array\\s*\\((?<callbacks>.*?)\\)`, 'gs' );
    const versionMatch = [ ...dbUpdatesContent.matchAll( versionPattern ) ];

    const dbUpdates = new Map();
    for ( const m of versionMatch ) {
        dbUpdates.set(
          m.groups.version,
          m.groups.callbacks
            .split( ',' )
            .map( c => c.replace( /['"]/g, '' ) )
            .map( c => c.trim() )
            .filter( c => c.length > 0 )
            .sort()
        );
    }

    return dbUpdates;
};

/**
 * Reads a file from a given ref.
 *
 * @param {string} ref
 * @param {string} path
 * @param {{ github: Octokit, context: { repo: { owner: string, repo: string } } }} options
 * @returns {Promise<string>} The file content.
 */
const readFileFromRef = async ( ref, path, { github, context } ) => {
    const file = await github.rest.repos.getContent(
        {
            owner: context.repo.owner,
            repo: context.repo.repo,
            path,
            ref
        }
    );

    return Buffer.from( file.data.content, 'base64' ).toString();
};

/**
 * Performs a check to ensure db udpates are correct in a given ref.
 *
 * @param {string} currentRef
 * @param {{ github: Octokit, context: { repo: { owner: string, repo: string } } }} options
 * @returns {Promise<void>}
 */
const run = async ( currentRef, { github, context } ) => {
  // Find version number in ref.
  const version = (
    await readFileFromRef( currentRef, 'plugins/woocommerce/woocommerce.php', { github, context } )
  ).match( /(?<=Version: )(.+)/ )[0];

  if ( version.endsWith( '-dev' ) || version.endsWith( '-beta.1' ) ) {
    console.log( `Skipping db updates check for '-dev' or '-beta.1'.` );
    return;
  }

  // Previous version.
  const previousRef = await findPreviousVersion( version, { github, context } );
  const previousFile = await readFileFromRef( previousRef, 'plugins/woocommerce/includes/class-wc-install.php', { github, context } );
  const previousDbUpdates = readDbUpdatesFromString( previousFile );

  // Read current wc-install.php.
  const currentFile = await readFileFromRef( currentRef, 'plugins/woocommerce/includes/class-wc-install.php', { github, context } );
  const currentDbUpdates = readDbUpdatesFromString( currentFile );

  console.log( `Comparing versions '${ previousRef }' and '${ version }'  (ref: '${ currentRef }').` );

  // Compare db updates.
  const key1 = Array.from( previousDbUpdates.keys() ).pop();
  const [ base1, suffix1 ] = key1.split( '-' );

  const key2 = Array.from( currentDbUpdates.keys() ).pop();
  const [ base2, suffix2 ] = key2.split( '-' );

  // If the keys are identical, ensure no new callback was added. Removing callbacks is allowed.
  if ( key1 === key2 ) {
    const updates1 = previousDbUpdates.get( key1 );
    const updates2 = currentDbUpdates.get( key2 );

    updates2.forEach(
      ( e ) => {
        if ( ! updates1.includes( e ) ) {
          throw new Error( `A new db update callback was added under key '${ key1 }' in version '${ version }'. Add them under a new key instead (e.g. '${ base1 }-${ Number( suffix1 || 0 ) + 1 }').` );
        }
      }
    );

    return;
  }

  // If the basis are different, the new one must have a higher patch number.
  if ( base1 !== base2 && ( Number( base2.split( '.' ).at( -1 )  ) > Number( base1.split( '.' ).at( -1 ) ) ) ) {
    return;
  }

  // If the basis are equal, the new one must have a higher suffix.
  if ( base1 === base2 && ( Number( suffix2 || 0 ) > Number( suffix1 || 0 ) ) ) {
    return;
  }

  throw new Error( `DB update callbacks under key '${ key1 }' were removed in version '${ version }'. Use an empty array if necessary to maintain the correct order.` );
};

module.exports = { readDbUpdatesFromString, run };
