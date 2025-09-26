<?php
/**
 * Test file to demonstrate that functions are allowed in src directory
 * 
 * This file contains examples that should NOT trigger the linting error.
 * Remove this file after testing.
 */

// This function should NOT trigger the linting error (it's in src/)
function allowed_function_in_src() {
    return 'This function is allowed in the src directory';
}

// This class with methods should also be fine
class TestClassInSrc {
    public function allowed_method() {
        return 'This method is allowed';
    }
}