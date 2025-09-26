<?php
/**
 * Test file to demonstrate the new linting rules for includes directory
 * 
 * This file contains examples that should trigger linting errors.
 * Remove this file after testing.
 */

// This function should trigger the NoNewFunctionsInIncludesSniff error
function should_trigger_function_error() {
    return 'This standalone function should be flagged as an error';
}

// This class should trigger the NoNewClassesInIncludesSniff error
class TestClassInIncludes {
    public function method() {
        return 'This class should also be flagged as an error';
    }
}

// This interface should also trigger the NoNewClassesInIncludesSniff error
interface TestInterfaceInIncludes {
    public function method();
}

// This trait should also trigger the NoNewClassesInIncludesSniff error
trait TestTraitInIncludes {
    public function method() {
        return 'This trait should also be flagged as an error';
    }
}