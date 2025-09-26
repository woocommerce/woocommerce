<?php
/**
 * WooCommerce Custom Sniff: No New Classes in Includes Directory
 *
 * This sniff prevents the addition of new classes in the includes directory.
 * No new functions or classes are allowed in the includes directory.
 *
 * @package WooCommerce\Sniffs\Classes
 */

namespace WooCommerce\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * NoNewClassesInIncludesSniff
 *
 * Detects class declarations in the includes directory and reports them as errors.
 * No new classes are allowed in the includes directory.
 */
class NoNewClassesInIncludesSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_CLASS, T_INTERFACE, T_TRAIT);
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
        $tokenType = $tokens[$stackPtr]['type'];
        
        // This is a class/interface/trait in the includes directory - report as error
        $phpcsFile->addError(
            'New classes, interfaces, and traits are not allowed in the includes directory. No new functions or classes are allowed in the includes directory.',
            $stackPtr,
            'NewClassInIncludes'
        );
    }
}