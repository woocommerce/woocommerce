<?php
/**
 * Class WC_Abstract_Order file.
 *
 * @package WooCommerce\Tests\Libraries
 */

include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';


/**
 * Class WC_Eval_Math.
 */
class WC_Eval_Math_Test extends WC_Unit_Test_Case {
  /**
   * Simple equations
   */
  public function test_simple() {
    $this->assertSame( 2, WC_Eval_Math::evaluate( "2" ) );
    $this->assertSame( -2, WC_Eval_Math::evaluate( "-2" ) ); // unary
    $this->assertSame( -2, WC_Eval_Math::evaluate( "  -  2  " ) ); // whitespace
    $this->assertSame( 2, WC_Eval_Math::evaluate( "--2" ) );
    $this->assertSame( 7, WC_Eval_Math::evaluate( "5+2" ) );
    $this->assertSame( 3, WC_Eval_Math::evaluate( "5-2" ) );
    $this->assertSame( 10, WC_Eval_Math::evaluate( "5*2" ) );
    $this->assertSame( 10, WC_Eval_Math::evaluate( "5 2" ) ); // implicit mult
    $this->assertSame( 2.5, WC_Eval_Math::evaluate( "5/2" ) );
    $this->assertSame( 1, WC_Eval_Math::evaluate( "5%2" ) );
    $this->assertSame( 2, WC_Eval_Math::evaluate( "5\\2" ) );
    $this->assertSame( 25, WC_Eval_Math::evaluate( "5^2" ) );
    $this->assertSame( 25, WC_Eval_Math::evaluate( "5^2;  " ) ); // allow semi-colon
  }

  /**
   * Brackets ()
   */
  public function test_brackets() {
    $this->assertSame( 15, WC_Eval_Math::evaluate( "5*(2+1)" ) );
    $this->assertSame( 125, WC_Eval_Math::evaluate( "(5^(2+1))" ) );
    $this->assertSame( 123, WC_Eval_Math::evaluate( "(1+2)*((3+4)*5+6)" ) );
  }

  /**
   * Operator precedence and left/right association
   */
  public function test_precedence() {
    $this->assertSame( 11, WC_Eval_Math::evaluate( "5+2*3" ) );
    $this->assertSame( 162, WC_Eval_Math::evaluate( "2*3^4" ) );
    $this->assertSame( 512, WC_Eval_Math::evaluate( "2^3^2" ) ); // right-associative
  }

  /**
   * Setting and getting variables
   */
  public function test_variable() {
    // built-ins
    $this->assertSame( 3.14, WC_Eval_Math::evaluate( "pi" ) );
    $this->assertSame( 2.71, WC_Eval_Math::evaluate( "e" ) );
    // assigning to constant
    WC_Eval_Math::$suppress = true;
    $this->assertSame( false, WC_Eval_Math::evaluate( "e=2" ) );
    WC_Eval_Math::$suppress = false;
    // assignment
    $this->assertSame( 11, WC_Eval_Math::evaluate( "test=5+2*3" ) );
    $this->assertSame( 12, WC_Eval_Math::evaluate( "test1=6+2*3" ) );
    $this->assertSame( 13, WC_Eval_Math::evaluate( "test_=7+2*3" ) );
    $this->assertSame( 1, WC_Eval_Math::evaluate( "test=1+0" ) ); // redefine
    // using new variables
    $this->assertSame( 12, WC_Eval_Math::evaluate( "test+5+2*3" ) );
    // invalid assignment
    WC_Eval_Math::$suppress = true;
    $this->assertSame( false, WC_Eval_Math::evaluate( "var=2(" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "var" ) ); // shouldn't exist
    WC_Eval_Math::$suppress = false;
  }

  /**
   * Setting and using functions
   */
  public function test_function() {
    // built-ins
    WC_Eval_Math::$fb[] = 'sin';
    $this->assertSame( sin(1), WC_Eval_Math::evaluate( "sin(1)" ) );
    // defining
    $this->assertSame( 1, WC_Eval_Math::evaluate( "sin=1" ) );
    $this->assertSame( sin(2), WC_Eval_Math::evaluate( "sin(2)" ) ); // function takes prio
    $this->assertSame( 2, WC_Eval_Math::evaluate( "sin+1" ) ); // but variable still exists
    $this->assertSame( 7, WC_Eval_Math::evaluate( "test=5+2" ) );
    $this->assertSame( true, WC_Eval_Math::evaluate( "f(x)  = test 2 x" ) ); // at least 1 arg
    $this->assertSame( true, WC_Eval_Math::evaluate( "g(x,y)=test 2 x+y" ) );
    // calling
    $this->assertSame( 14, WC_Eval_Math::evaluate( "f(1)" ) );
    $this->assertSame( 32, WC_Eval_Math::evaluate( "g(2  ,  1+3 ) " ) );
    // freeze vars
    $this->assertSame( 3, WC_Eval_Math::evaluate( "test=1+2" ) );
    $this->assertSame( 14, WC_Eval_Math::evaluate( "f(1)" ) );
    // wrong arg count
    WC_Eval_Math::$suppress = true;
    $this->assertSame( false, WC_Eval_Math::evaluate( "sin(1,2)" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "f(1, 2)" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "g(1)" ) );
    // misc
    $this->assertSame( 3, WC_Eval_Math::evaluate( "test(1)" ) ); // implicit multiplication with variable (no func test)
    $this->assertSame( false, WC_Eval_Math::evaluate( "undefined(1)" ) ); // shouldn't exist
    WC_Eval_Math::$suppress = false;
  }

  /**
   * Invalid inputs
   */
  public function test_invalid() {
    WC_Eval_Math::$suppress = true;
    $this->assertSame( false, WC_Eval_Math::evaluate( "5+(2" ) ); // missing bracket
    $this->assertSame( false, WC_Eval_Math::evaluate( "5+2)" ) ); // too much bracket
    $this->assertSame( false, WC_Eval_Math::evaluate( "+2" ) ); // non-unary used as unary
    $this->assertSame( false, WC_Eval_Math::evaluate( "5+" ) ); // missing operand
    $this->assertSame( false, WC_Eval_Math::evaluate( "5++2" ) ); // double operator
    $this->assertSame( false, WC_Eval_Math::evaluate( "5+2,1" ) ); // argument delimiter without function call
    $this->assertSame( false, WC_Eval_Math::evaluate( "(5+2,1)" ) ); // argument delimiter without function call
    $this->assertSame( false, WC_Eval_Math::evaluate( "5^2;1" ) ); // two expressions
    $this->assertSame( false, WC_Eval_Math::evaluate( "=" ) ); // assignment without variables
    $this->assertSame( false, WC_Eval_Math::evaluate( "a()=2" ) ); // no-arg function
    $this->assertSame( false, WC_Eval_Math::evaluate( "a(x)=y" ) ); // unknown variable
    $this->assertSame( true, WC_Eval_Math::evaluate( "a(x,y)=x y" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "a(,)" ) ); // no arg
    $this->invalid_chars();
    WC_Eval_Math::$suppress = false;
  }

  /**
   * Test all invalid characters in Extended Latin range
   */
  private function invalid_chars() {
    // https://www.utf8-chartable.de
    for ( $i = 0; $i <= 31; $i++ ) { 
      $this->assertSame( false, WC_Eval_Math::evaluate( "\x$i" ) );
    }
    $this->assertSame( false, WC_Eval_Math::evaluate( "!" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "\"" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "#" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "$" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "&" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "'" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( ":" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "<" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( ">" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "?" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "@" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "[" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "]" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "_" ) );
    $this->assertSame( false, WC_Eval_Math::evaluate( "`" ) );
    for ( $i = 123; $i <= 591; $i++ ) { 
      $this->assertSame( false, WC_Eval_Math::evaluate( "\x$i" ) );
    }
  }
}