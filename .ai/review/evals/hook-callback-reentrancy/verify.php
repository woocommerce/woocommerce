<?php
/** Bounded checks of the reduced fixtures, without WordPress or model calls. */
final class Reentrancy_Fixture_Check {
    public static function same( $expected, $actual, $message ) {
        if ( $expected !== $actual ) {
            throw new RuntimeException( $message );
        }
    }
}

final class Fixture_Recursion_Limit extends RuntimeException {}

if ( ! isset( $argv[1], $argv[2] ) ) {
    foreach ( array( 'G41A', 'G41B', 'G41C', 'G41D' ) as $case ) {
        foreach ( array( 'before', 'after' ) as $side ) {
            passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' ' . $case . ' ' . $side, $status );
            if ( 0 !== $status ) {
                exit( $status );
            }
        }
    }
    echo "All eight fixture snapshots behaved as expected.\n";
    exit( 0 );
}

$case = $argv[1];
$side = $argv[2];
if ( ! in_array( $case, array( 'G41A', 'G41B', 'G41C', 'G41D' ), true ) || ! in_array( $side, array( 'before', 'after' ), true ) ) {
    throw new InvalidArgumentException( 'Unknown fixture' );
}

$filters = array();
$hook_errors = array();
$guid_depth = 0;
$nested_guids = array();
$metadata_reads = 0;
$fail_next_read = false;

function add_filter( $name, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['filters'][ $name ][ $priority ][] = array( $callback, $accepted_args );
}

function apply_filters( $name, $value, ...$args ) {
    $priorities = $GLOBALS['filters'][ $name ] ?? array();
    ksort( $priorities );
    foreach ( $priorities as $callbacks ) {
        foreach ( $callbacks as list( $callback, $accepted_args ) ) {
            try {
                $value = $callback( ...array_slice( array( $value, ...$args ), 0, $accepted_args ) );
            } catch ( Throwable $error ) {
                if ( 'get_post_metadata' === $name ) {
                    $GLOBALS['hook_errors'][] = $error->getMessage();
                }
                throw $error;
            }
        }
    }
    return $value;
}

function get_post_type( $id ) {
    return 104 === $id ? 'post' : 'attachment';
}

function get_the_guid( $id ) {
    $GLOBALS['guid_depth']++;
    try {
        if ( $GLOBALS['guid_depth'] > 8 ) {
            throw new Fixture_Recursion_Limit( 'Mutual recursion exceeded eight GUID calls' );
        }
        $stored = 103 === $id ? 'rml://shortcut' : 'https://example.test/attachment-' . $id;
        $result = apply_filters( 'get_the_guid', $stored, $id );
        if ( $GLOBALS['guid_depth'] > 1 ) {
            $GLOBALS['nested_guids'][] = $result;
        }
        return $result;
    } finally {
        $GLOBALS['guid_depth']--;
    }
}

function get_post_meta( $id, $key, $single = false ) {
    $GLOBALS['metadata_reads']++;
    if ( $GLOBALS['fail_next_read'] ) {
        $GLOBALS['fail_next_read'] = false;
        throw new RuntimeException( 'Injected provider failure' );
    }
    $value = apply_filters( 'get_post_metadata', null, $id, $key, $single );
    if ( null !== $value ) {
        return $single && is_array( $value ) ? reset( $value ) : $value;
    }
    $stored = 101 === $id ? 'woocommerce_uploads/private.pdf' : '2026/photo.jpg';
    return $single ? $stored : array( $stored );
}

if ( 'G41D' !== $case ) {
    // A media plugin resolves virtual attachments while filtering the stored file path.
    add_filter( 'get_post_metadata', static function ( $value, $object_id, $meta_key, $single ) {
        if ( '_wp_attached_file' === $meta_key && $single ) {
            $guid = get_the_guid( $object_id );
            if ( is_string( $guid ) && 0 === strpos( $guid, 'rml://' ) ) {
                return 'shared/library-file.jpg';
            }
        }
        return $value;
    }, 10, 4 );
}

require __DIR__ . '/fixtures/' . $case . '/' . $side . '.php';

$should_recurse = ( 'G41A' === $case && 'after' === $side ) || ( 'G41C' === $case && 'before' === $side );
if ( $should_recurse ) {
    $caught = false;
    try {
        get_the_guid( 101 );
    } catch ( Fixture_Recursion_Limit $error ) {
        $caught = true;
    }
    Reentrancy_Fixture_Check::same( true, $caught, 'Expected bounded mutual recursion' );
    Reentrancy_Fixture_Check::same( 0, $guid_depth, 'Stack depth did not unwind' );
} elseif ( 'G41B' === $case ) {
    $expected = 'before' === $side
        ? array( '', '', '' )
        : array( '', 'https://example.test/attachment-101', 'https://example.test/attachment-101' );
    Reentrancy_Fixture_Check::same( $expected, ( new Attachment_Guid_Renderer() )->render( array( 101, 101, 101 ) ), 'Wrong results after a caught provider exception' );
    Reentrancy_Fixture_Check::same( array( 'Media provider temporarily unavailable' ), $hook_errors, 'Expected exactly one provider failure before successful later reads' );
} elseif ( 'G41C' === $case ) {
    Reentrancy_Fixture_Check::same( '', get_the_guid( 101 ), 'Outer call must hide the downloadable attachment' );
    Reentrancy_Fixture_Check::same( array( 'https://example.test/attachment-101' ), $nested_guids, 'Nested call must preserve its input' );
    Reentrancy_Fixture_Check::same( 'https://example.test/attachment-102', get_the_guid( 102 ), 'Ordinary attachment changed' );
    Reentrancy_Fixture_Check::same( 'rml://shortcut', get_the_guid( 103 ), 'Metadata short-circuit changed' );
    $reads = $metadata_reads;
    Reentrancy_Fixture_Check::same( 'https://example.test/attachment-104', get_the_guid( 104 ), 'Non-attachment changed' );
    Reentrancy_Fixture_Check::same( 'https://example.test/attachment-0', get_the_guid( 0 ), 'Invalid ID changed' );
    Reentrancy_Fixture_Check::same( $reads, $metadata_reads, 'Cheap checks must precede the filtered read' );
    $fail_next_read = true;
    $caught = false;
    try {
        get_the_guid( 101 );
    } catch ( RuntimeException $error ) {
        Reentrancy_Fixture_Check::same( 'Injected provider failure', $error->getMessage(), 'Wrong injected exception' );
        $caught = true;
    }
    Reentrancy_Fixture_Check::same( true, $caught, 'Expected injected exception' );
    Reentrancy_Fixture_Check::same( '', get_the_guid( 101 ), 'Guard did not reset after exception' );
    Reentrancy_Fixture_Check::same( '', get_the_guid( 101 ), 'Guard did not reset after normal return' );
} elseif ( 'G41D' === $case ) {
    foreach ( array( array( 'legacy-media://item', 'media://item' ), array( 'https://example.test/image', 'https://example.test/image' ), array( null, null ), array( false, false ), array( '', '' ) ) as list( $input, $expected ) ) {
        Reentrancy_Fixture_Check::same( $expected, apply_filters( 'get_the_guid', $input, 101 ), 'Argument-only refactor changed its result' );
    }
    Reentrancy_Fixture_Check::same( 0, $metadata_reads, 'Argument-only callback read metadata' );
} else {
    Reentrancy_Fixture_Check::same( 'https://example.test/attachment-101', get_the_guid( 101 ), 'Baseline must preserve its input' );
    Reentrancy_Fixture_Check::same( 0, $metadata_reads, 'Baseline unexpectedly read metadata' );
}
echo 'PASS: ' . $case . ' ' . $side . "\n";
