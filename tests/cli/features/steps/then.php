<?php

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

$steps->Then( '/^the return code should be (\d+)$/',
	function ( $world, $return_code ) {
		if ( $return_code != $world->result->return_code ) {
			throw new RuntimeException( $world->result );
		}
	}
);

$steps->Then( '/^(STDOUT|STDERR) should (be|contain|not contain):$/',
	function ( $world, $stream, $action, PyStringNode $expected ) {

		$stream = strtolower( $stream );

		$expected = $world->replace_variables( (string) $expected );

		checkString( $world->result->$stream, $expected, $action, $world->result );
	}
);

$steps->Then( '/^(STDOUT|STDERR) should be a number$/',
	function ( $world, $stream ) {

		$stream = strtolower( $stream );

		assertNumeric( trim( $world->result->$stream, "\n" ) );
	}
);

$steps->Then( '/^(STDOUT|STDERR) should not be a number$/',
	function ( $world, $stream ) {

		$stream = strtolower( $stream );

		assertNotNumeric( trim( $world->result->$stream, "\n" ) );
	}
);

$steps->Then( '/^STDOUT should be a table containing rows:$/',
	function ( $world, TableNode $expected ) {
		$output      = $world->result->stdout;
		$actual_rows = explode( "\n", rtrim( $output, "\n" ) );

		$expected_rows = array();
		foreach ( $expected->getRows() as $row ) {
			$expected_rows[] = $world->replace_variables( implode( "\t", $row ) );
		}

		compareTables( $expected_rows, $actual_rows, $output );
	}
);

$steps->Then( '/^STDOUT should end with a table containing rows:$/',
	function ( $world, TableNode $expected ) {
		$output      = $world->result->stdout;
		$actual_rows = explode( "\n", rtrim( $output, "\n" ) );

		$expected_rows = array();
		foreach ( $expected->getRows() as $row ) {
			$expected_rows[] = $world->replace_variables( implode( "\t", $row ) );
		}

		$start = array_search( $expected_rows[0], $actual_rows );

		if ( false === $start )
			throw new \Exception( $world->result );

		compareTables( $expected_rows, array_slice( $actual_rows, $start ), $output );
	}
);

$steps->Then( '/^STDOUT should be JSON containing:$/',
	function ( $world, PyStringNode $expected ) {
		$output = $world->result->stdout;
		$expected = $world->replace_variables( (string) $expected );

		if ( !checkThatJsonStringContainsJsonString( $output, $expected ) ) {
			throw new \Exception( $world->result );
		}
});

$steps->Then( '/^STDOUT should be a JSON array containing:$/',
	function ( $world, PyStringNode $expected ) {
		$output = $world->result->stdout;
		$expected = $world->replace_variables( (string) $expected );

		$actualValues = json_decode( $output );
		$expectedValues = json_decode( $expected );

		$missing = array_diff( $expectedValues, $actualValues );
		if ( !empty( $missing ) ) {
			throw new \Exception( $world->result );
		}
});

$steps->Then( '/^STDOUT should be CSV containing:$/',
	function ( $world, TableNode $expected ) {
		$output = $world->result->stdout;

		$expected_rows = $expected->getRows();
		foreach ( $expected as &$row ) {
			foreach ( $row as &$value ) {
				$value = $world->replace_variables( $value );
			}
		}

		if ( ! checkThatCsvStringContainsValues( $output, $expected_rows ) )
			throw new \Exception( $world->result );
	}
);

$steps->Then( '/^STDOUT should be YAML containing:$/',
	function ( $world, PyStringNode $expected ) {
		$output = $world->result->stdout;
		$expected = $world->replace_variables( (string) $expected );

		if ( !checkThatYamlStringContainsYamlString( $output, $expected ) ) {
			throw new \Exception( $world->result );
		}
});

$steps->Then( '/^(STDOUT|STDERR) should be empty$/',
	function ( $world, $stream ) {

		$stream = strtolower( $stream );

		if ( !empty( $world->result->$stream ) ) {
			throw new \Exception( $world->result );
		}
	}
);

$steps->Then( '/^(STDOUT|STDERR) should not be empty$/',
	function ( $world, $stream ) {

		$stream = strtolower( $stream );

		if ( '' === rtrim( $world->result->$stream, "\n" ) ) {
			throw new Exception( $world->result );
		}
	}
);

$steps->Then( '/^the (.+) (file|directory) should (exist|not exist|be:|contain:|not contain:)$/',
	function ( $world, $path, $type, $action, $expected = null ) {
		$path = $world->replace_variables( $path );

		// If it's a relative path, make it relative to the current test dir
		if ( '/' !== $path[0] )
			$path = $world->variables['RUN_DIR'] . "/$path";

		if ( 'file' == $type ) {
			$test = 'file_exists';
		} else if ( 'directory' == $type ) {
			$test = 'is_dir';
		}

		switch ( $action ) {
		case 'exist':
			if ( ! $test( $path ) ) {
				throw new Exception( $world->result );
			}
			break;
		case 'not exist':
			if ( $test( $path ) ) {
				throw new Exception( $world->result );
			}
			break;
		default:
			if ( ! $test( $path ) ) {
				throw new Exception( "$path doesn't exist." );
			}
			$action = substr( $action, 0, -1 );
			$expected = $world->replace_variables( (string) $expected );
			if ( 'file' == $type ) {
				$contents = file_get_contents( $path );
			} else if ( 'directory' == $type ) {
				$files = glob( rtrim( $path, '/' ) . '/*' );
				foreach( $files as &$file ) {
					$file = str_replace( $path . '/', '', $file );
				}
				$contents = implode( PHP_EOL, $files );
			}
			checkString( $contents, $expected, $action );
		}
	}
);

