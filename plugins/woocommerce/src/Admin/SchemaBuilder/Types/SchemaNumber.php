<?php

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Types;

use Automattic\WooCommerce\Admin\SchemaBuilder\Exceptions\IncompatibleSchemaFormatException;

class SchemaNumber extends AbstractSchemaType {

    /**
     * Minimum.
     *
     * @var int
     */
    private $minimum = null;

    /**
     * Maximum.
     *
     * @var int
     */
    private $maximum = null;

    /**
     * Exclusive minimum.
     *
     * @var int
     */
    private $exclusive_minimum = null;

    /**
     * Exclusive maximum.
     *
     * @var int
     */
    private $exclusive_maximum = null;

    /**
     * Multiple of.
     *
     * @var int
     */
    private $multiple_of = null;

    /**
     * Schema type.
     *
     * @return string
     */
    public function get_type() {
        return 'number';
    }

    /**
     * Set the minimum.
     *
     * @param int
     * @return SchemaString
     */
    public function minimum( $minimum ) {
        $this->minimum = $this->parse_value( $minimum );
        return $this;
    }

    /**
     * Set the maximum.
     *
     * @param int
     * @return SchemaString
     */
    public function maximum( $maximum ) {
        $this->maximum = $this->parse_value( $maximum );
        return $this;
    }

    /**
     * Set the minimum.
     *
     * @param int
     * @return SchemaString
     */
    public function exclusiveMinimum( $exclusive_minimum ) {
        $this->exclusive_minimum = $this->parse_value( $exclusive_minimum );
        return $this;
    }

    /**
     * Set the maximum.
     *
     * @param int
     * @return SchemaString
     */
    public function exclusiveMaximum( $exclusive_maximum ) {
        $this->exclusive_maximum = $this->parse_value( $exclusive_maximum );
        return $this;
    }

    /**
     * Set the multiple of.
     *
     * @param int
     * @return SchemaString
     */
    public function multipleOf( $multiple_of ) {
        $this->multiple_of = $this->parse_value( $multiple_of );
        return $this;
    }

    /**
     * Get the JSON.
     *
     * @return array
     */
    public function get_json() {
        $json = parent::get_json();

        if ( $this->minimum !== null ) {
            $json['minimum'] = $this->minimum;
        }

        if ( $this->maximum !== null  ) {
            $json['maximum'] = $this->maximum;
        }

        if ( $this->exclusive_minimum !== null ) {
            $json['exclusive_minimum'] = $this->exclusive_minimum;
        }

        if ( $this->exclusive_maximum !== null  ) {
            $json['exclusive_maximum'] = $this->exclusive_maximum;
        }

        if ( $this->multiple_of !== null  ) {
            $json['multiple_of'] = $this->multiple_of;
        }

        return $json;
    }

}