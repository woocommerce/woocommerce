<?php

/**
 * Texy! - human-readable text to HTML converter.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE version 2 or 3
 * @link       http://texy.info
 * @package    Texy
 */



/**#@+
 * Regular expression patterns
 */

// Unicode character classes
define('TEXY_CHAR',        'A-Za-z\x{C0}-\x{2FF}\x{370}-\x{1EFF}');

// marking meta-characters
// any mark:               \x14-\x1F
// CONTENT_MARKUP mark:    \x17-\x1F
// CONTENT_REPLACED mark:  \x16-\x1F
// CONTENT_TEXTUAL mark:   \x15-\x1F
// CONTENT_BLOCK mark:     \x14-\x1F
define('TEXY_MARK',        "\x14-\x1F");


// modifier .(title)[class]{style}
define('TEXY_MODIFIER',    '(?: *(?<= |^)\\.((?:\\([^)\\n]+\\)|\\[[^\\]\\n]+\\]|\\{[^}\\n]+\\}){1,3}?))');

// modifier .(title)[class]{style}<>
define('TEXY_MODIFIER_H',  '(?: *(?<= |^)\\.((?:\\([^)\\n]+\\)|\\[[^\\]\\n]+\\]|\\{[^}\\n]+\\}|<>|>|=|<){1,4}?))');

// modifier .(title)[class]{style}<>^
define('TEXY_MODIFIER_HV', '(?: *(?<= |^)\\.((?:\\([^)\\n]+\\)|\\[[^\\]\\n]+\\]|\\{[^}\\n]+\\}|<>|>|=|<|\\^|\\-|\\_){1,5}?))');



// images   [* urls .(title)[class]{style} >]
define('TEXY_IMAGE',       '\[\* *+([^\n'.TEXY_MARK.']+)'.TEXY_MODIFIER.'? *(\*|(?<!<)>|<)\]');


// links
define('TEXY_LINK_URL',    '(?:\[[^\]\n]+\]|(?!\[)[^\s'.TEXY_MARK.']*?[^:);,.!?\s'.TEXY_MARK.'])'); // any url - doesn't end by :).,!?
define('TEXY_LINK',        '(?::('.TEXY_LINK_URL.'))');       // any link
define('TEXY_LINK_N',      '(?::('.TEXY_LINK_URL.'|:))');     // any link (also unstated)
define('TEXY_EMAIL',       '['.TEXY_CHAR.'][0-9.+_'.TEXY_CHAR.'-]{0,63}@[0-9.+_'.TEXY_CHAR.'\x{ad}-]{1,252}\.['.TEXY_CHAR.'\x{ad}]{2,19}'); // name@exaple.com
define('TEXY_URLSCHEME',   '[a-z][a-z0-9+.-]*:');    // http:  |  mailto:
/**#@-*/
