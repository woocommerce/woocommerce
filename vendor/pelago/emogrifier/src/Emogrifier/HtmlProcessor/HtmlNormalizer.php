<?php

namespace Pelago\Emogrifier\HtmlProcessor;

/**
 * Normalizes HTML:
 * - add a document type (HTML5) if missing
 * - disentangle incorrectly nested tags
 * - add HEAD and BODY elements (if they are missing)
 * - reformat the HTML
 *
 * @author Oliver Klee <github@oliverklee.de>
 */
class HtmlNormalizer extends AbstractHtmlProcessor
{
}
