<?php declare(strict_types=1);

namespace League\Container\Argument;

interface ClassNameInterface
{
    /**
     * Return the class name.
     *
     * @return string
     */
    public function getValue() : string;
}
