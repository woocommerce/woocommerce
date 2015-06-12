<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Eval_Math
 *
 * Based on EvalMath by Miles Kaufman Copyright (C) 2005 Miles Kaufmann http://www.twmagic.com/
 */
class WC_Eval_Math {
	/** @var bool */
	public $suppress_errors = false;

	/** @var string */
	public $last_error = null;

	/** @var array */
	public $v = array( 'e'=>2.71, 'pi'=>3.14 ); // variables (and constants)

	/** @var array */
	public $f = array(); // user-defined functions

	/** @var array */
	public $vb = array( 'e', 'pi' ); // constants

	/** @var array */
	public $fb = array(  // built-in functions
		'sin', 'sinh', 'arcsin', 'asin', 'arcsinh', 'asinh',
		'cos', 'cosh', 'arccos', 'acos', 'arccosh', 'acosh',
		'tan', 'tanh', 'arctan', 'atan', 'arctanh', 'atanh',
		'sqrt', 'abs', 'ln', 'log'
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->v['pi'] = pi();
		$this->v['e']  = exp( 1 );
	}

	/**
	 * Evaluate maths string
	 *
	 * @param string  $expr
	 * @return mixed
	 */
	public function evaluate( $expr ) {
		$this->last_error = null;
		$expr = trim( $expr );
		if ( substr( $expr, -1, 1 ) == ';' ) $expr = substr( $expr, 0, strlen( $expr )-1 ); // strip semicolons at the end
		//===============
		// is it a variable assignment?
		if ( preg_match( '/^\s*([a-z]\w*)\s*=\s*(.+)$/', $expr, $matches ) ) {
			if ( in_array( $matches[1], $this->vb ) ) { // make sure we're not assigning to a constant
				return $this->trigger( "cannot assign to constant '$matches[1]'" );
			}
			if ( ( $tmp = $this->pfx( $this->nfx( $matches[2] ) ) ) === false ) return false; // get the result and make sure it's good
			$this->v[$matches[1]] = $tmp; // if so, stick it in the variable array
			return $this->v[$matches[1]]; // and return the resulting value
			//===============
			// is it a function assignment?
		} elseif ( preg_match( '/^\s*([a-z]\w*)\s*\(\s*([a-z]\w*(?:\s*,\s*[a-z]\w*)*)\s*\)\s*=\s*(.+)$/', $expr, $matches ) ) {
			$fnn = $matches[1]; // get the function name
			if ( in_array( $matches[1], $this->fb ) ) { // make sure it isn't built in
				return $this->trigger( "cannot redefine built-in function '$matches[1]()'" );
			}
			$args = explode( ",", preg_replace( "/\s+/", "", $matches[2] ) ); // get the arguments
			if ( ( $stack = $this->nfx( $matches[3] ) ) === false ) return false; // see if it can be converted to postfix
			for ( $i = 0; $i<count( $stack ); $i++ ) { // freeze the state of the non-argument variables
				$token = $stack[$i];
				if ( preg_match( '/^[a-z]\w*$/', $token ) and !in_array( $token, $args ) ) {
					if ( array_key_exists( $token, $this->v ) ) {
						$stack[$i] = $this->v[$token];
					} else {
						return $this->trigger( "undefined variable '$token' in function definition" );
					}
				}
			}
			$this->f[$fnn] = array( 'args'=>$args, 'func'=>$stack );
			return true;
			//===============
		} else {
			return $this->pfx( $this->nfx( $expr ) ); // straight up evaluation, woo
		}
	}

	/**
	 * Vars
	 *
	 * @return array
	 */
	public function vars() {
		$output = $this->v;
		unset( $output['pi'] );
		unset( $output['e'] );
		return $output;
	}

	/**
	 * Funcs
	 *
	 * @return array
	 */
	public function funcs() {
		$output = array();
		foreach ( $this->f as $fnn=>$dat ) {
			$output[] = $fnn . '(' . implode( ',', $dat['args'] ) . ')';
		}
		return $output;
	}

	// Convert infix to postfix notation
	private function nfx( $expr ) {

		$index = 0;
		$stack = new WC_Eval_Math_Stack;
		$output = array(); // postfix form of expression, to be passed to pfx()
		// $expr = trim(strtolower($expr));
		$expr = trim( $expr );

		$ops   = array( '+', '-', '*', '/', '^', '_' );
		$ops_r = array( '+'=>0, '-'=>0, '*'=>0, '/'=>0, '^'=>1 ); // right-associative operator?
		$ops_p = array( '+'=>0, '-'=>0, '*'=>1, '/'=>1, '_'=>1, '^'=>2 ); // operator precedence

		$expecting_op = false; // we use this in syntax-checking the expression
		// and determining when a - is a negation

		if ( preg_match( "/[^\w\s+*^\/()\.,-]/", $expr, $matches ) ) { // make sure the characters are all good
			return $this->trigger( "illegal character '{$matches[0]}'" );
		}

		while ( 1 ) { // 1 Infinite Loop ;)
			$op = substr( $expr, $index, 1 ); // get the first character at the current index
			// find out if we're currently at the beginning of a number/variable/function/parenthesis/operand
			$ex = preg_match( '/^([A-Za-z]\w*\(?|\d+(?:\.\d*)?|\.\d+|\()/', substr( $expr, $index ), $match );
			//===============
			if ( $op == '-' and !$expecting_op ) { // is it a negation instead of a minus?
				$stack->push( '_' ); // put a negation on the stack
				$index++;
			} elseif ( $op == '_' ) { // we have to explicitly deny this, because it's legal on the stack
				return $this->trigger( "illegal character '_'" ); // but not in the input expression
				//===============
			} elseif ( ( in_array( $op, $ops ) or $ex ) and $expecting_op ) { // are we putting an operator on the stack?
				if ( $ex ) { // are we expecting an operator but have a number/variable/function/opening parethesis?
					$op = '*'; $index--; // it's an implicit multiplication
				}
				// heart of the algorithm:
				while ( $stack->count > 0 and ( $o2 = $stack->last() ) and in_array( $o2, $ops ) and ( $ops_r[$op] ? $ops_p[$op] < $ops_p[$o2] : $ops_p[$op] <= $ops_p[$o2] ) ) {
					$output[] = $stack->pop(); // pop stuff off the stack into the output
				}
				// many thanks: http://en.wikipedia.org/wiki/Reverse_Polish_notation#The_algorithm_in_detail
				$stack->push( $op ); // finally put OUR operator onto the stack
				$index++;
				$expecting_op = false;
				//===============
			} elseif ( $op == ')' and $expecting_op ) { // ready to close a parenthesis?
				while ( ( $o2 = $stack->pop() ) != '(' ) { // pop off the stack back to the last (
					if ( is_null( $o2 ) ) return $this->trigger( "unexpected ')'" );
					else $output[] = $o2;
				}
				if ( preg_match( "/^([A-Za-z]\w*)\($/", $stack->last( 2 ), $matches ) ) { // did we just close a function?
					$fnn = $matches[1]; // get the function name
					$arg_count = $stack->pop(); // see how many arguments there were (cleverly stored on the stack, thank you)
					$output[] = $stack->pop(); // pop the function and push onto the output
					if ( in_array( $fnn, $this->fb ) ) { // check the argument count
						if ( $arg_count > 1 )
							return $this->trigger( "too many arguments ($arg_count given, 1 expected)" );
					} elseif ( array_key_exists( $fnn, $this->f ) ) {
						if ( $arg_count != count( $this->f[$fnn]['args'] ) )
							return $this->trigger( "wrong number of arguments ($arg_count given, " . count( $this->f[$fnn]['args'] ) . " expected)" );
					} else { // did we somehow push a non-function on the stack? this should never happen
						return $this->trigger( "internal error" );
					}
				}
				$index++;
				//===============
			} elseif ( $op == ',' and $expecting_op ) { // did we just finish a function argument?
				while ( ( $o2 = $stack->pop() ) != '(' ) {
					if ( is_null( $o2 ) ) return $this->trigger( "unexpected ','" ); // oops, never had a (
					else $output[] = $o2; // pop the argument expression stuff and push onto the output
				}
				// make sure there was a function
				if ( !preg_match( "/^([A-Za-z]\w*)\($/", $stack->last( 2 ), $matches ) )
					return $this->trigger( "unexpected ','" );
				$stack->push( $stack->pop()+1 ); // increment the argument count
				$stack->push( '(' ); // put the ( back on, we'll need to pop back to it again
				$index++;
				$expecting_op = false;
				//===============
			} elseif ( $op == '(' and !$expecting_op ) {
				$stack->push( '(' ); // that was easy
				$index++;
				$allow_neg = true;
				//===============
			} elseif ( $ex and !$expecting_op ) { // do we now have a function/variable/number?
				$expecting_op = true;
				$val = $match[1];
				if ( preg_match( "/^([A-Za-z]\w*)\($/", $val, $matches ) ) { // may be func, or variable w/ implicit multiplication against parentheses...
					if ( in_array( $matches[1], $this->fb ) or array_key_exists( $matches[1], $this->f ) ) { // it's a func
						$stack->push( $val );
						$stack->push( 1 );
						$stack->push( '(' );
						$expecting_op = false;
					} else { // it's a var w/ implicit multiplication
						$val = $matches[1];
						$output[] = $val;
					}
				} else { // it's a plain old var or num
					$output[] = $val;
				}
				$index += strlen( $val );
				//===============
			} elseif ( $op == ')' ) { // miscellaneous error checking
				return $this->trigger( "unexpected ')'" );
			} elseif ( in_array( $op, $ops ) and !$expecting_op ) {
				return $this->trigger( "unexpected operator '$op'" );
			} else { // I don't even want to know what you did to get here
				return $this->trigger( "an unexpected error occured" );
			}
			if ( $index == strlen( $expr ) ) {
				if ( in_array( $op, $ops ) ) { // did we end with an operator? bad.
					return $this->trigger( "operator '$op' lacks operand" );
				} else {
					break;
				}
			}
			while ( substr( $expr, $index, 1 ) == ' ' ) { // step the index past whitespace (pretty much turns whitespace
				$index++;                             // into implicit multiplication if no operator is there)
			}

		}
		while ( !is_null( $op = $stack->pop() ) ) { // pop everything off the stack and push onto output
			if ( $op == '(' ) return $this->trigger( "expecting ')'" ); // if there are (s on the stack, ()s were unbalanced
			$output[] = $op;
		}
		return $output;
	}

	// evaluate postfix notation
	private function pfx( $tokens, $vars = array() ) {
		if ( $tokens == false ) return false;

		$stack = new WC_Eval_Math_Stack;

		foreach ( $tokens as $token ) { // nice and easy
			// if the token is a binary operator, pop two values off the stack, do the operation, and push the result back on
			if ( in_array( $token, array( '+', '-', '*', '/', '^' ) ) ) {
				if ( is_null( $op2 = $stack->pop() ) ) return $this->trigger( "internal error" );
				if ( is_null( $op1 = $stack->pop() ) ) return $this->trigger( "internal error" );
				switch ( $token ) {
				case '+':
					$stack->push( $op1+$op2 ); break;
				case '-':
					$stack->push( $op1-$op2 ); break;
				case '*':
					$stack->push( $op1*$op2 ); break;
				case '/':
					if ( $op2 == 0 ) return $this->trigger( "division by zero" );
					$stack->push( $op1/$op2 ); break;
				case '^':
					$stack->push( pow( $op1, $op2 ) ); break;
				}
				// if the token is a unary operator, pop one value off the stack, do the operation, and push it back on
			} elseif ( $token == "_" ) {
				$stack->push( -1*$stack->pop() );
				// if the token is a function, pop arguments off the stack, hand them to the function, and push the result back on
			} elseif ( preg_match( "/^([a-z]\w*)\($/", $token, $matches ) ) { // it's a function!
				$fnn = $matches[1];
				if ( in_array( $fnn, $this->fb ) ) { // built-in function:
					if ( is_null( $op1 = $stack->pop() ) ) return $this->trigger( "internal error" );
					$fnn = preg_replace( "/^arc/", "a", $fnn ); // for the 'arc' trig synonyms
					if ( $fnn == 'ln' ) $fnn = 'log';
					eval( '$stack->push(' . $fnn . '($op1));' ); // perfectly safe eval()
				} elseif ( array_key_exists( $fnn, $this->f ) ) { // user function
					// get args
					$args = array();
					for ( $i = count( $this->f[$fnn]['args'] )-1; $i >= 0; $i-- ) {
						if ( is_null( $args[$this->f[$fnn]['args'][$i]] = $stack->pop() ) ) return $this->trigger( "internal error" );
					}
					$stack->push( $this->pfx( $this->f[$fnn]['func'], $args ) ); // yay... recursion!!!!
				}
				// if the token is a number or variable, push it on the stack
			} else {
				if ( is_numeric( $token ) ) {
					$stack->push( $token );
				} elseif ( array_key_exists( $token, $this->v ) ) {
					$stack->push( $this->v[$token] );
				} elseif ( array_key_exists( $token, $vars ) ) {
					$stack->push( $vars[$token] );
				} else {
					return $this->trigger( "undefined variable '$token'" );
				}
			}
		}
		// when we're out of tokens, the stack should have a single element, the final result
		if ( $stack->count != 1 ) return $this->trigger( "internal error" );
		return $stack->pop();
	}

	// trigger an error, but nicely, if need be
	private function trigger( $msg ) {
		$this->last_error = $msg;
		if ( !$this->suppress_errors ) {
			echo "\nError found in:";
			$this->debugPrintCallingFunction();

			trigger_error( $msg, E_USER_WARNING );
		}
		return false;
	}

	// Prints the file name, function name, and
	// line number which called your function
	// (not this function, then one that  called
	// it to begin with)
	private function debugPrintCallingFunction() {
		$file = 'n/a';
		$func = 'n/a';
		$line = 'n/a';
		$debugTrace = debug_backtrace();
		if ( isset( $debugTrace[1] ) ) {
			$file = $debugTrace[1]['file'] ? $debugTrace[1]['file'] : 'n/a';
			$line = $debugTrace[1]['line'] ? $debugTrace[1]['line'] : 'n/a';
		}
		if ( isset( $debugTrace[2] ) ) $func = $debugTrace[2]['function'] ? $debugTrace[2]['function'] : 'n/a';
		echo "\n$file, $func, $line\n";
	}
}

/**
 * Class WC_Eval_Math_Stack
 */
class WC_Eval_Math_Stack {

	/** @var array */
	public $stack = array();

	/** @var integer */
	public $count = 0;

	public function push( $val ) {
		$this->stack[ $this->count ] = $val;
		$this->count++;
	}

	public function pop() {
		if ( $this->count > 0 ) {
			$this->count--;
			return $this->stack[ $this->count ];
		}
		return null;
	}

	public function last( $n=1 ) {
		$key = $this->count - $n;
		return array_key_exists( $key, $this->stack ) ? $this->stack[ $key ] : null;
	}
}
