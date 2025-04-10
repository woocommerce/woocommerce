<?php

namespace Automattic\WooCommerce\Blueprint;

/**
 * Class ClassExtractor
 *
 * Provides functionality to manipulate PHP class files by replacing variables,
 * adding prefixes, and removing strict types declarations.
 *
 * This class is used to generate 'code' part for runPHP step from a template file.
 */
class ClassExtractor {
	/**
	 * @var string Path to the PHP file being processed.
	 */
	private string $filePath;

	/**
	 * @var bool Whether the file contains a strict types declaration.
	 */
	private bool $hasStrictTypesDeclaration = false;

	/**
	 * @var string PHP code to prefix to the final output.
	 */
	private string $prefix = '';

	/**
	 * @var array Replacements for class variables.
	 */
	private array $classVariableReplacements = array();

	/**
	 * @var array Replacements for method variables.
	 */
	private array $methodVariableReplacements = array();

	/**
	 * Constructor.
	 *
	 * @param string $filePath Path to the PHP file to process.
	 *
	 * @throws \InvalidArgumentException If the file does not exist.
	 */
	public function __construct( string $filePath ) {
		if ( ! file_exists( $filePath ) ) {
			throw new \InvalidArgumentException( "File not found: $filePath" );
		}
		$this->filePath = $filePath;
	}

	/**
	 * Adds a prefix to include the WordPress wp-load.php file.
	 *
	 * @return $this
	 */
	public function with_wp_load() {
		$this->prefix .= "<?php require_once 'wordpress/wp-load.php'; ";
		return $this;
	}

	/**
	 * Replaces a class variable with a new value.
	 *
	 * @param string $variableName Name of the class variable.
	 * @param mixed  $newValue The new value to assign to the variable.
	 *
	 * @return $this
	 */
	public function replace_class_variable( $variableName, $newValue ) {
		$this->classVariableReplacements[ $variableName ] = $newValue;
		return $this;
	}

	/**
	 * Replaces a variable inside a method with a new value.
	 *
	 * @param string $methodName Name of the method.
	 * @param string $variableName Name of the variable to replace.
	 * @param mixed  $newValue The new value to assign to the variable.
	 *
	 * @return $this
	 */
	public function replace_method_variable( $methodName, $variableName, $newValue ) {
		$this->methodVariableReplacements[] = array(
			'method'   => $methodName,
			'variable' => $variableName,
			'value'    => $newValue,
		);
		return $this;
	}

	/**
	 * Generates the processed PHP code with applied replacements and prefixes.
	 *
	 * @return string The modified PHP code.
	 */
	public function get_code() {
		$fileContent = file_get_contents( $this->filePath );

		$fileContent = preg_replace( '/<\?php\s*/', '', $fileContent );

		if ( preg_match( '/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $fileContent ) ) {
			$this->hasStrictTypesDeclaration = true;
			$fileContent                     = preg_replace( '/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', '', $fileContent );
		}

		$fileContent = preg_replace( '/\/\*.*?\*\/|\/\/.*?(?=\r?\n)/s', '', $fileContent );

		foreach ( $this->classVariableReplacements as $variable => $value ) {
			$fileContent = $this->apply_class_variable_replacement( $fileContent, $variable, $value );
		}

		foreach ( $this->methodVariableReplacements as $replacement ) {
			$fileContent = $this->apply_variable_replacement(
				$fileContent,
				$replacement['method'],
				$replacement['variable'],
				$replacement['value']
			);
		}

		return $this->prefix . trim( $fileContent );
	}

	/**
	 * Applies a replacement to a class variable in the file content.
	 *
	 * @param string $fileContent The content of the PHP file.
	 * @param string $variableName The name of the variable to replace.
	 * @param mixed  $newValue The new value for the variable.
	 *
	 * @return string The updated file content.
	 */
	private function apply_class_variable_replacement( $fileContent, $variableName, $newValue ) {
		$replacementValue = var_export( $newValue, true );

		$pattern = '/(protected|private|public)\s+\$' . preg_quote( $variableName, '/' ) . '\s*=\s*.*?;|'
			. '(protected|private|public)\s+\$' . preg_quote( $variableName, '/' ) . '\s*;?/';

		$replacement = "$1 \$$variableName = $replacementValue;";
		return preg_replace( $pattern, $replacement, $fileContent, 1 );
	}

	/**
	 * Applies a replacement to a variable in a specific method.
	 *
	 * @param string $fileContent The content of the PHP file.
	 * @param string $methodName The name of the method containing the variable.
	 * @param string $variableName The name of the variable to replace.
	 * @param mixed  $newValue The new value for the variable.
	 *
	 * @return string The updated file content.
	 */
	private function apply_variable_replacement( $fileContent, $methodName, $variableName, $newValue ) {
		$pattern = '/function\s+' . preg_quote( $methodName, '/' ) . '\s*\([^)]*\)\s*\{\s*(.*?)\s*\}/s';
		if ( preg_match( $pattern, $fileContent, $matches ) ) {
			$methodBody = $matches[1];

			$newValueExported = var_export( $newValue, true );
			$variablePattern  = '/\$' . preg_quote( $variableName, '/' ) . '\s*=\s*[^;]+;/';
			$replacement      = '$' . $variableName . ' = ' . $newValueExported . ';';

			$updatedMethodBody = preg_replace( $variablePattern, $replacement, $methodBody, 1 );

			if ( $updatedMethodBody !== null ) {
				$fileContent = str_replace( $methodBody, $updatedMethodBody, $fileContent );
			}
		}

		return $fileContent;
	}

	/**
	 * Checks if the file has a strict types declaration.
	 *
	 * @return bool True if the file has a strict types declaration, false otherwise.
	 */
	public function has_strict_type_declaration() {
		return $this->hasStrictTypesDeclaration;
	}
}
