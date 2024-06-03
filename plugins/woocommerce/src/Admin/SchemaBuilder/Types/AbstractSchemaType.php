<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

use Automattic\WooCommerce\Admin\SchemaBuilder\Types\SchemaReference;

abstract class AbstractSchemaType {

    /**
     * Format.
     *
     * @var string|null
     */
    protected $format = null;

    /**
     * Required.
     *
     * @var bool
     */
    protected $required = false;

    /**
     * Description.
     *
     * @var string|null
     */
    protected $description = null;

    /**
     * Read only.
     *
     * @var bool
     */
    protected $readonly = false;

    /**
     * Validation callback.
     *
     * @var string
     */
    protected $callback = null;

    /**
     * Validation callback args.
     *
     * @var array
     */
    protected $callback_args = array();

    /**
     * Context.
     *
     * @var array
     */
    protected $context = array( 'view', 'edit' );

    /**
     * Schema type.
     *
     * @return string
     */
    abstract function get_type();

    /**
     * Set a description.
     *
     * @param string $description Description.
     * @return SchemaTypeInterface
     */
    public function description( $description ) {
        $this->description = $description;
        return $this;
    }

    /**
     * Set the property as required.
     *
     * @return SchemaTypeInterface
     */
    public function required() {
        $this->required = true;
        return $this;
    }

    /**
     * Set the property as required.
     *
     * @return SchemaTypeInterface
     */
    public function readonly() {
        $this->readonly = true;
        return $this;
    }

    /**
     * Context
     */
    public function context( $context ) {
        $this->context = $context;
        return $this;
    }

    /**
     * Get the JSON.
     */
    public function get_json() {
        $json = array(
            'type'        => $this->get_type(),
        );

        if ( $this->description ) {
            $json['description'] = $this->description;
        }

        if ( $this->format ) {
            $json['format'] = $this->format;
        }

        if ( $this->context ) {
            $json['context'] = $this->context;
        }

        if ( $this->readonly ) {
            $json['readonly'] = true;
        }

        if ( $this->callback ) {
            $json['$filters'] = array(
                '$func' => $this->callback,
                '$vars' => $this->callback_args,
            );
        }

        return $json;
    }

    /**
     * Check if the property is required.
     */
    public function is_required() {
        return $this->required;
    }

    /**
     * Add a validation callback.
     *
     * @param string $callback Callback.
     * @param array  $args Callback arguments.
     */
    public function validate( $callback, $args = array() ) {
        $this->callback      = $callback;
        $this->callback_args = $args;
        return $this;
    }

    /**
     * Parse an input value.
     */
    public function parse_value( $input ) {
        if ( is_a( $input, SchemaReference::class ) ) {
            return $input->get_json();
        }

        return $input;
    }

}