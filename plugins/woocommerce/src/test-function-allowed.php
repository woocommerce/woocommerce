<?php
/**
 * Test file to demonstrate the new linting rules for src directory
 * 
 * This file contains examples that should trigger linting errors.
 * Remove this file after testing.
 */

// This function should trigger the NoNewFunctionsInSrcSniff error
function should_trigger_function_error_in_src() {
    return 'This standalone function should be flagged as an error in src/';
}

// This class should NOT trigger any error (classes are allowed in src/)
class AllowedClassInSrc {
    public function allowed_method() {
        return 'This method is allowed';
    }
    
    private function another_allowed_method() {
        return 'This private method is also allowed';
    }
}

// This interface should also be allowed in src/
interface AllowedInterfaceInSrc {
    public function method();
}

// This trait should also be allowed in src/
trait AllowedTraitInSrc {
    public function method() {
        return 'This trait is allowed in src/';
    }
}