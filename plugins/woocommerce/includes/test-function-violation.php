<?php
/**
 * Test file to demonstrate the NoNewFunctionsInIncludesSniff rule
 * 
 * This file contains examples that should trigger the linting error.
 * Remove this file after testing.
 */

// This function should trigger the linting error
function should_trigger_error() {
    return 'This standalone function should be flagged as an error';
}

// This class with methods should NOT trigger the error
class TestClass {
    public function allowed_method() {
        return 'This method is allowed';
    }
    
    private function another_allowed_method() {
        return 'This private method is also allowed';
    }
}