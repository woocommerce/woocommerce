<?php
/**
 * Test file to demonstrate what is allowed in src directory
 * 
 * This file contains examples that should NOT trigger any linting errors.
 * Remove this file after testing.
 */

// This class is allowed in src/
class AllowedClass {
    public function public_method() {
        return 'This public method is allowed';
    }
    
    private function private_method() {
        return 'This private method is allowed';
    }
    
    protected function protected_method() {
        return 'This protected method is allowed';
    }
    
    public static function static_method() {
        return 'This static method is allowed';
    }
}

// This interface is allowed in src/
interface AllowedInterface {
    public function interface_method();
}

// This trait is allowed in src/
trait AllowedTrait {
    public function trait_method() {
        return 'This trait method is allowed';
    }
}

// This abstract class is allowed in src/
abstract class AllowedAbstractClass {
    abstract public function abstract_method();
    
    public function concrete_method() {
        return 'This concrete method is allowed';
    }
}