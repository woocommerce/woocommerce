<?php

namespace Automattic\WooCommerce\TransientFiles;

use \Closure;

/**
 * Context class for the template rendering process.
 *
 * When a template is rendered with TransientFilesEngine::create_file_by_rendering_template, the template code will see
 * an instance of this class as "$this". If the template executes "$this->render", the secondary template
 * will see a different instance of the class.
 */
class TemplateRenderingContext {

	/**
	 * The full path of the template file being rendered.
	 *
	 * @var string
	 */
	private string $template_file_path;

	/**
	 * The variables to be made available to the template code via "$this->variable" and "$this->get_variable".
	 *
	 * @var array
	 */
	private array $variables;

	/**
	 * The callback to be used to render a secondary template.
	 *
	 * @var Closure
	 */
	private Closure $render_subtemplate_callback;

	/**
	 * The current display name of the template being rendered.
	 *
	 * @var string
	 */
	private string $template_display_name;

	/**
	 * The variables defined locally with __set or set_variable.
	 *
	 * @var array
	 */
	private array $local_variables = array();

	/**
	 * Creates a new instance of the class.
	 *
	 * @param Closure $render_subtemplate_callback The callback to be used to render a secondary template.
	 * @param string  $template_file_path The full path of the template file being rendered.
	 * @param array   $variables The variables to be made available to the template code via "$this->variable" and "$this->get_variable".
	 */
	public function __construct( Closure $render_subtemplate_callback, string $template_file_path, array $variables ) {
		$this->render_subtemplate_callback = $render_subtemplate_callback;
		$this->template_file_path          = $template_file_path;
		$this->variables                   = $variables;

		$this->template_display_name = pathinfo( $template_file_path, PATHINFO_FILENAME );
	}

	/**
	 * Get the display name of the current template.
	 * By default it's the file name of the template without extension, can be changed with set_template_display_name.
	 *
	 * @return string Current template display name.
	 */
	public function get_template_display_name(): string {
		return $this->template_display_name;
	}

	/**
	 * Set the display name of the current template, that can be retrieved with get_template_display_name.
	 * By default it's the file name of the template without extension.
	 * The new name is valid until the current rendering process finishes
	 * (if the template is rendered again as a secondary template, it will have the default name again).
	 *
	 * @param string $name The display name of the template to be set.
	 */
	public function set_template_display_name( string $name ): void {
		$this->template_display_name = $name;
	}

	/**
	 * Check if a variable with a given name is available.
	 *
	 * @param string $name The variable name.
	 * @return bool True if a variable with a given name is available, false otherwise.
	 */
	public function has_variable( string $name ): bool {
		return array_key_exists( $name, $this->variables ) || array_key_exists( $name, $this->local_variables );
	}

	/**
	 * Get a variable by name. This getter allows to retrieve variables with "$this->name".
	 *
	 * @param string $name The name of the variable.
	 * @return mixed|null The value of the variable, or null if no variable is available with that name.
	 */
	public function __get( $name ) {
		return $this->get_variable( $name );
	}

	/**
	 * Sets a local value for a variable. This setter allows to set the value with "$this->name = value".
	 *
	 * The value is considered local because it won't be passed to secondary templates
	 * rendered with "render".
	 *
	 * @param string $name The name of the variable.
	 * @param mixed  $value The value of the variable.
	 */
	public function __set( $name, $value ) {
		$this->set_variable($name, $value);
	}

	/**
	 * Sets a local value for a variable.
	 *
	 * The value is considered local because it won't be passed to secondary templates
	 * rendered with "render".
	 *
	 * @param string $name The name of the variable.
	 * @param mixed  $value The value of the variable.
	 */
	public function set_variable( string $name, $value) {
		$this->local_variables[$name] = $value;
	}

	/**
	 * Get a variable by name. This getter allows to retrieve variables with "$this->name".
	 *
	 * @param string $name The name of the variable.
	 * @return mixed|null The value of the variable, or null if no variable is available with that name.
	 */
	public function get_variable( string $name ) {
		return $this->local_variables[$name] ?? $this->variables[ $name ] ?? null;
	}

	/**
	 * Get the names of all the available variables.
	 *
	 * @return array The names of all the available variables.
	 */
	public function get_variable_names(): array {
		return array_keys( array_merge( $this->variables, $this->local_variables ) );
	}

	/**
	 * Render a secondary template.
	 *
	 * Setting $relative to true is an indication to consider the location of the secondary template file
	 * as relative to the template being currently rendered. The current template path, as well as the "relative" flag,
	 * will be supplied to the core rendering method in TransientFilesEngine and to the woocommerce_transient_file_creation_template_file_path filter.
	 *
	 * The variables available in the current template will be merged with the array passed in $variables
	 * (the latter will overwrite duplicate keys from the former) and the result will be passed to the secondary template.
	 *
	 * @param string $template_name The name of the secondary template.
	 * @param array  $variables The variables to be passed to the secondary template (after being merged with the variables of the current template).
	 * @param bool   $relative True if the location of the secondary template file is to be considered as relative to the location of the current template file.
	 */
	public function render( string $template_name, array $variables = array(), bool $relative = true ): void {
		$variables = array_merge( $this->variables, $variables );
		( $this->render_subtemplate_callback )( $template_name, $variables, $relative );
	}
}
