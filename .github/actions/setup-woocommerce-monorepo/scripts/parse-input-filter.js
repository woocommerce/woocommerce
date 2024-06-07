const args = process.argv.slice( 2 );
if ( args.length != 1 ) {
    console.error( 'Filters must be passed as a single string!' );
    process.exit( -1 );
}

// Boolean inputs should not be processed.
if ( args[0] === 'true' || args[0] === 'false' ) {
    process.exit();
}

// Read all of the given filters and return the full filter options string.
const filterLines = args[0].split( "\n" );
let output = '';
for ( const line of filterLines ) {
    if ( line === '' ) {
        continue;
    }

    if ( output !== '' ) {
        output += ' ';
    }
    output += `--filter='${ line }'`;
}

console.log( output );
process.exit();
