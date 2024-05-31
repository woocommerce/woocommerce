<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

use Automattic\WooCommerce\Admin\SchemaBuilder\Exceptions\IncompatibleSchemaFormatException;
use Automattic\WooCommerce\Admin\SchemaBuilder\Exceptions\IncompatibleSchemaPatternException;

class SchemaString extends AbstractSchemaType {

    /**
     * Minimum length.
     *
     * @var int
     */
    private $min_length = null;

    /**
     * Maximum length.
     *
     * @var int
     */
    private $max_length = null;

    /**
     * Pattern.
     *
     * @var int
     */
    private $pattern = null;

    /**
     * Schema type.
     *
     * @return string
     */
    public function get_type() {
        return 'string';
    }

    /**
     * Set the minimum length.
     *
     * @param int
     * @return SchemaString
     */
    public function min_length( $min_length ) {
        $this->min_length = $min_length;
        return $this;
    }

    /**
     * Set the maximum length.
     *
     * @param int
     * @return SchemaString
     */
    public function max_length( $max_length ) {
        $this->max_length = $max_length;
        return $this;
    }

    /**
     * Set the pattern.
     *
     * @param string
     * @return SchemaString
     */
    public function pattern( $pattern ) {
        if ( $this->pattern !== null ) {
            throw new IncompatibleSchemaPatternException();
        }

        $this->pattern = $pattern;
        return $this;
    }

    /**
     * Get the JSON.
     *
     * @return array
     */
    public function get_json() {
        $json = parent::get_json();

        if ( $this->min_length !== null ) {
            $json['minLength'] = $this->min_length;
        }

        if ( $this->max_length !== null  ) {
            $json['maxLength'] = $this->max_length;
        }

        if ( $this->pattern !== null  ) {
            $json['pattern'] = $this->pattern;
        }

        return $json;
    }

    /**
     * Set the email format.
     *
     * @return SchemaString
     */
    public function email() {
        if ( $this->format !== null ) {
            throw new IncompatibleSchemaFormatException();
        }

        $this->format = 'email';
        return $this;
    }

    /**
     * Set the currency format.
     *
     * @return SchemaString
     */
    public function currency() {
        return $this->pattern( '^(0|([1-9]+[0-9]*))(\.[0-9]*)?$' );
    }

}