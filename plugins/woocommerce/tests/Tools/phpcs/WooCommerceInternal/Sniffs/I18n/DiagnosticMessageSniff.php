<?php
/**
 * DiagnosticMessageSniff.
 *
 * @package WooCommerce\Tests\Tools\PHPCS
 */

declare( strict_types=1 );

namespace WooCommerceInternal\Sniffs\I18n;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\PassedParameters;
use WordPressCS\WordPress\AbstractFunctionParameterSniff;

/**
 * Flags translation calls inside the message passed to a developer diagnostic function.
 *
 * Messages for `wc_doing_it_wrong()`, `trigger_error()`, `error_log()` and the deprecation
 * notices are read in logs and searched by developers and support. Translating them makes the
 * same problem appear under a different text on every site language, so they stay in English.
 * Calls made through `LegacyProxy::call_function( 'wc_doing_it_wrong', ... )` are checked too.
 * The sniff only sees translation calls written inside the call, not values built elsewhere.
 */
class DiagnosticMessageSniff extends AbstractFunctionParameterSniff {

	/**
	 * Group name for this sniff's functions.
	 *
	 * @var string
	 */
	protected $group_name = 'diagnostic_message';

	/**
	 * Diagnostic functions, mapped to the position and name of each parameter that holds message text.
	 *
	 * @var array<string, array<int, string>>
	 */
	protected $target_functions = array(
		'wc_doing_it_wrong'        => array( 2 => 'message' ),
		'_doing_it_wrong'          => array( 2 => 'message' ),
		'trigger_error'            => array( 1 => 'message' ),
		'wp_trigger_error'         => array( 2 => 'message' ),
		'user_error'               => array( 1 => 'message' ),
		'error_log'                => array( 1 => 'message' ),
		'wc_deprecated_function'   => array( 3 => 'replacement' ),
		'_deprecated_function'     => array( 3 => 'replacement' ),
		'_deprecated_class'        => array( 3 => 'replacement' ),
		'wc_deprecated_argument'   => array( 3 => 'message' ),
		'_deprecated_argument'     => array( 3 => 'message' ),
		'wc_deprecated_hook'       => array(
			3 => 'replacement',
			4 => 'message',
		),
		'_deprecated_hook'         => array(
			3 => 'replacement',
			4 => 'message',
		),
		'_deprecated_file'         => array(
			3 => 'replacement',
			4 => 'message',
		),
		'apply_filters_deprecated' => array(
			4 => 'replacement',
			5 => 'message',
		),
		'do_action_deprecated'     => array(
			4 => 'replacement',
			5 => 'message',
		),
		'wc_do_deprecated_action'  => array(
			4 => 'replacement',
			5 => 'message',
		),
		// LegacyProxy::call_function(); the proxied function name decides what to check.
		'call_function'            => array(),
	);

	/**
	 * WordPress functions that return or print translated text.
	 *
	 * @var array<string, true>
	 */
	private const TRANSLATION_FUNCTIONS = array(
		'__'                             => true,
		'_e'                             => true,
		'_x'                             => true,
		'_ex'                            => true,
		'_n'                             => true,
		'_nx'                            => true,
		'esc_html__'                     => true,
		'esc_html_e'                     => true,
		'esc_html_x'                     => true,
		'esc_attr__'                     => true,
		'esc_attr_e'                     => true,
		'esc_attr_x'                     => true,
		'translate'                      => true,
		'translate_with_gettext_context' => true,
		'translate_nooped_plural'        => true,
	);

	/**
	 * Also matches `->call_function()` and `::call_function()`, the LegacyProxy call style.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 * @return bool
	 */
	public function is_targetted_token( $stackPtr ) {
		if ( 'call_function' !== strtolower( $this->tokens[ $stackPtr ]['content'] ) ) {
			return parent::is_targetted_token( $stackPtr );
		}

		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $stackPtr - 1, null, true );
		$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $stackPtr + 1, null, true );

		return false !== $prev && false !== $next
			&& in_array( $this->tokens[ $prev ]['code'], array( \T_OBJECT_OPERATOR, \T_NULLSAFE_OBJECT_OPERATOR, \T_DOUBLE_COLON ), true )
			&& \T_OPEN_PARENTHESIS === $this->tokens[ $next ]['code']
			&& isset( $this->tokens[ $next ]['parenthesis_closer'] );
	}

	/**
	 * Flags translation calls in the message parameters of a matched diagnostic call.
	 *
	 * @param int    $stackPtr        The position of the function name token.
	 * @param string $group_name      The matched group name.
	 * @param string $matched_content The matched function name, in lowercase.
	 * @param array  $parameters      The call's parameters, from PassedParameters::getParameters().
	 * @return void
	 */
	public function process_parameters( $stackPtr, $group_name, $matched_content, $parameters ) {
		if ( 'call_function' === $matched_content ) {
			$this->process_proxied_call( $parameters );
			return;
		}

		foreach ( $this->target_functions[ $matched_content ] as $position => $name ) {
			$parameter = PassedParameters::getParameterFromStack( $parameters, $position, $name );
			if ( false !== $parameter ) {
				$this->flag_translations( $parameter, $matched_content );
			}
		}
	}

	/**
	 * Checks `call_function( 'wc_doing_it_wrong', ... )`, where every argument is shifted by one.
	 *
	 * @param array $parameters The call's parameters, from PassedParameters::getParameters().
	 * @return void
	 */
	private function process_proxied_call( array $parameters ): void {
		if ( ! isset( $parameters[1] ) ) {
			return;
		}

		$first = $this->phpcsFile->findNext( Tokens::$emptyTokens, $parameters[1]['start'], $parameters[1]['end'] + 1, true );
		if ( false === $first || \T_CONSTANT_ENCAPSED_STRING !== $this->tokens[ $first ]['code'] ) {
			return;
		}

		$function = strtolower( trim( $this->tokens[ $first ]['content'], '\'"' ) );
		if ( 'call_function' === $function || ! isset( $this->target_functions[ $function ] ) ) {
			return;
		}

		foreach ( array_keys( $this->target_functions[ $function ] ) as $position ) {
			if ( isset( $parameters[ $position + 1 ] ) ) {
				$this->flag_translations( $parameters[ $position + 1 ], $function );
			}
		}
	}

	/**
	 * Reports each translation call inside one parameter.
	 *
	 * @param array  $parameter  One parameter, from PassedParameters.
	 * @param string $diagnostic The diagnostic function receiving it.
	 * @return void
	 */
	private function flag_translations( array $parameter, string $diagnostic ): void {
		for ( $i = $parameter['start']; $i <= $parameter['end']; $i++ ) {
			if ( \T_STRING !== $this->tokens[ $i ]['code'] ) {
				continue;
			}

			$function = strtolower( $this->tokens[ $i ]['content'] );
			if ( ! isset( self::TRANSLATION_FUNCTIONS[ $function ] ) || ! $this->is_function_call( $i ) ) {
				continue;
			}

			$this->phpcsFile->addError(
				'Developer diagnostic messages must not be translated. Pass an English string to %s() instead of calling %s(), so the message is the same in every site language.',
				$i,
				'Translated',
				array( $diagnostic, $function )
			);
		}
	}

	/**
	 * Determines whether a T_STRING token is a call to a global function rather than a method call.
	 *
	 * @param int $ptr Position of the T_STRING token.
	 * @return bool
	 */
	private function is_function_call( int $ptr ): bool {
		$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, $ptr + 1, null, true );
		if ( false === $next || \T_OPEN_PARENTHESIS !== $this->tokens[ $next ]['code'] ) {
			return false;
		}

		$prev = $this->phpcsFile->findPrevious( Tokens::$emptyTokens, $ptr - 1, null, true );

		return false === $prev || ! in_array( $this->tokens[ $prev ]['code'], array( \T_OBJECT_OPERATOR, \T_NULLSAFE_OBJECT_OPERATOR, \T_DOUBLE_COLON, \T_FUNCTION ), true );
	}
}
