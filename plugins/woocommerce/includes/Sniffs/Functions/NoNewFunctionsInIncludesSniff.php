<?php
/**
 * WooCommerce Custom Sniff: No New Functions in Includes Directory
 *
 * This sniff prevents the addition of new functions in the includes directory.
 * All new code should be added as classes in the src directory.
 *
 * @package WooCommerce\Sniffs\Functions
 */

namespace WooCommerce\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * NoNewFunctionsInIncludesSniff
 *
 * Detects function declarations in the includes directory and reports them as errors.
 */
class NoNewFunctionsInIncludesSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_FUNCTION);
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $fileName = $phpcsFile->getFilename();
        
        // Check if the file is in the includes directory
        if (strpos($fileName, DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR) === false) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        
        // Skip if this is a method inside a class (not a standalone function)
        $prevToken = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if ($prevToken !== false && $tokens[$prevToken]['code'] === T_CLOSE_CURLY_BRACKET) {
            // This might be a method, check if we're inside a class
            $classToken = $phpcsFile->findPrevious(T_CLASS, $stackPtr - 1);
            if ($classToken !== false) {
                return; // This is a method, not a standalone function
            }
        }

        // Check if this is a method by looking for visibility keywords before the function
        $visibilityToken = $phpcsFile->findPrevious(
            array(T_PUBLIC, T_PRIVATE, T_PROTECTED, T_STATIC),
            $stackPtr - 1,
            null,
            false,
            null,
            true
        );
        
        if ($visibilityToken !== false) {
            // Check if there's a class between the visibility token and the function
            $classToken = $phpcsFile->findPrevious(T_CLASS, $stackPtr - 1, $visibilityToken);
            if ($classToken !== false) {
                return; // This is a method, not a standalone function
            }
        }

        // Check if this is a method by looking for the class keyword in the same line or previous lines
        $functionLine = $tokens[$stackPtr]['line'];
        $classToken = $phpcsFile->findPrevious(T_CLASS, $stackPtr - 1);
        if ($classToken !== false && $tokens[$classToken]['line'] < $functionLine) {
            // Check if the class is still open (not closed before this function)
            $classEnd = $tokens[$classToken]['scope_closer'];
            if ($classEnd > $stackPtr) {
                return; // This is a method inside a class
            }
        }

        // This is a standalone function in the includes directory - report as error
        $phpcsFile->addError(
            'New functions are not allowed in the includes directory. Please add new code as classes in the src directory instead.',
            $stackPtr,
            'NewFunctionInIncludes'
        );
    }
}