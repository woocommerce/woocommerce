<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Util;

/**
 * Simple markdown to HTML converter.
 *
 * Supports: headings, paragraphs, code blocks, inline code, links, emphasis, lists, blockquotes, tables.
 */
final class MarkdownParser
{
    /**
     * Convert markdown to HTML.
     *
     * @param string $markdown The markdown text
     * @return string The HTML output
     */
    public static function toHtml(string $markdown): string
    {
        // Normalize line endings
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);

        // Process code blocks first (to protect their content)
        $codeBlocks = [];
        $markdown = preg_replace_callback(
            '/```(\w+)?\n(.*?)```/s',
            function ($matches) use (&$codeBlocks) {
                $index = count($codeBlocks);
                $lang = $matches[1] ?? '';
                $code = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
                $langClass = $lang ? ' class="language-' . htmlspecialchars($lang) . '"' : '';
                $codeBlocks[$index] = "<pre><code{$langClass}>" . rtrim($code) . "</code></pre>";
                return "%%CODEBLOCK{$index}%%";
            },
            $markdown
        );

        // Split into lines for block-level processing
        $lines = explode("\n", $markdown);
        $html = [];
        $inList = false;
        $listType = '';
        $inBlockquote = false;
        $blockquoteContent = [];
        $inParagraph = false;
        $paragraphContent = [];
        $inTable = false;
        $tableRows = [];

        foreach ($lines as $line) {
            // Check for code block placeholder
            if (preg_match('/^%%CODEBLOCK(\d+)%%$/', $line, $matches)) {
                // Close any open blocks
                if ($inParagraph) {
                    $html[] = '<p>' . self::processInline(implode(' ', $paragraphContent)) . '</p>';
                    $paragraphContent = [];
                    $inParagraph = false;
                }
                if ($inList) {
                    $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    $inList = false;
                }
                if ($inBlockquote) {
                    $html[] = '<blockquote>' . self::toHtml(implode("\n", $blockquoteContent)) . '</blockquote>';
                    $blockquoteContent = [];
                    $inBlockquote = false;
                }
                if ($inTable) {
                    $html[] = self::renderTable($tableRows);
                    $tableRows = [];
                    $inTable = false;
                }
                $html[] = $codeBlocks[(int) $matches[1]];
                continue;
            }

            // Table row (starts with |)
            if (preg_match('/^\|(.+)\|$/', $line)) {
                // Close other open blocks
                if ($inParagraph) {
                    $html[] = '<p>' . self::processInline(implode(' ', $paragraphContent)) . '</p>';
                    $paragraphContent = [];
                    $inParagraph = false;
                }
                if ($inList) {
                    $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    $inList = false;
                }
                if ($inBlockquote) {
                    $html[] = '<blockquote>' . self::toHtml(implode("\n", $blockquoteContent)) . '</blockquote>';
                    $blockquoteContent = [];
                    $inBlockquote = false;
                }
                $inTable = true;
                $tableRows[] = $line;
                continue;
            }

            // Close table if line doesn't continue it
            if ($inTable) {
                $html[] = self::renderTable($tableRows);
                $tableRows = [];
                $inTable = false;
            }

            // Heading
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches)) {
                if ($inParagraph) {
                    $html[] = '<p>' . self::processInline(implode(' ', $paragraphContent)) . '</p>';
                    $paragraphContent = [];
                    $inParagraph = false;
                }
                if ($inList) {
                    $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    $inList = false;
                }
                $level = strlen($matches[1]);
                $html[] = "<h{$level}>" . self::processInline($matches[2]) . "</h{$level}>";
                continue;
            }

            // Unordered list item
            if (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
                if ($inParagraph) {
                    $html[] = '<p>' . self::processInline(implode(' ', $paragraphContent)) . '</p>';
                    $paragraphContent = [];
                    $inParagraph = false;
                }
                if (!$inList || $listType !== 'ul') {
                    if ($inList) {
                        $html[] = '</ol>';
                    }
                    $html[] = '<ul>';
                    $inList = true;
                    $listType = 'ul';
                }
                $html[] = '<li>' . self::processInline($matches[1]) . '</li>';
                continue;
            }

            // Ordered list item
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                if ($inParagraph) {
                    $html[] = '<p>' . self::processInline(implode(' ', $paragraphContent)) . '</p>';
                    $paragraphContent = [];
                    $inParagraph = false;
                }
                if (!$inList || $listType !== 'ol') {
                    if ($inList) {
                        $html[] = '</ul>';
                    }
                    $html[] = '<ol>';
                    $inList = true;
                    $listType = 'ol';
                }
                $html[] = '<li>' . self::processInline($matches[1]) . '</li>';
                continue;
            }

            // Close list if line doesn't continue it
            if ($inList && !preg_match('/^\s*$/', $line)) {
                $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                $inList = false;
            }

            // Blockquote
            if (preg_match('/^>\s*(.*)$/', $line, $matches)) {
                if ($inParagraph) {
                    $html[] = '<p>' . self::processInline(implode(' ', $paragraphContent)) . '</p>';
                    $paragraphContent = [];
                    $inParagraph = false;
                }
                $inBlockquote = true;
                $blockquoteContent[] = $matches[1];
                continue;
            }

            // Close blockquote
            if ($inBlockquote && !preg_match('/^>/', $line)) {
                $html[] = '<blockquote>' . self::toHtml(implode("\n", $blockquoteContent)) . '</blockquote>';
                $blockquoteContent = [];
                $inBlockquote = false;
            }

            // Empty line
            if (preg_match('/^\s*$/', $line)) {
                if ($inParagraph) {
                    $html[] = '<p>' . self::processInline(implode(' ', $paragraphContent)) . '</p>';
                    $paragraphContent = [];
                    $inParagraph = false;
                }
                if ($inList) {
                    $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    $inList = false;
                }
                continue;
            }

            // Regular text - add to paragraph
            $inParagraph = true;
            $paragraphContent[] = $line;
        }

        // Close any remaining open elements
        if ($inParagraph) {
            $html[] = '<p>' . self::processInline(implode(' ', $paragraphContent)) . '</p>';
        }
        if ($inList) {
            $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
        }
        if ($inBlockquote) {
            $html[] = '<blockquote>' . self::toHtml(implode("\n", $blockquoteContent)) . '</blockquote>';
        }
        if ($inTable) {
            $html[] = self::renderTable($tableRows);
        }

        return implode("\n", $html);
    }

    /**
     * Process inline markdown elements.
     *
     * @param string $text The text to process
     * @return string HTML with inline elements
     */
    private static function processInline(string $text): string
    {
        // Escape HTML first
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Inline code (must be before other patterns)
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

        // Bold (both ** and __)
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);

        // Italic (both * and _) - careful not to match inside words for underscore
        $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/(?<![a-zA-Z])_([^_]+)_(?![a-zA-Z])/', '<em>$1</em>', $text);

        // Images ![alt](url) - must be before links
        $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $text);

        // Links [text](url)
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);

        // Unescape > for HTML entities that might have been escaped
        $text = str_replace('&gt;', '>', $text);

        return $text;
    }

    /**
     * Render a markdown table as HTML.
     *
     * @param array<string> $rows The table rows (including header and separator)
     * @return string The HTML table
     */
    private static function renderTable(array $rows): string
    {
        if (count($rows) < 2) {
            // Not enough rows for a valid table
            return '<p>' . implode(' ', array_map([self::class, 'processInline'], $rows)) . '</p>';
        }

        $html = ['<table>'];

        foreach ($rows as $index => $row) {
            // Skip separator row (contains only |, -, :, and spaces)
            if (preg_match('/^\|[\s\-:|]+\|$/', $row)) {
                continue;
            }

            // Parse cells
            $cells = self::parseTableRow($row);

            if ($index === 0) {
                // Header row
                $html[] = '<thead>';
                $html[] = '<tr>';
                foreach ($cells as $cell) {
                    $html[] = '<th>' . self::processInline(trim($cell)) . '</th>';
                }
                $html[] = '</tr>';
                $html[] = '</thead>';
                $html[] = '<tbody>';
            } else {
                // Body row
                $html[] = '<tr>';
                foreach ($cells as $cell) {
                    $html[] = '<td>' . self::processInline(trim($cell)) . '</td>';
                }
                $html[] = '</tr>';
            }
        }

        $html[] = '</tbody>';
        $html[] = '</table>';

        return implode("\n", $html);
    }

    /**
     * Parse a table row into cells.
     *
     * @param string $row The table row
     * @return array<string> The cells
     */
    private static function parseTableRow(string $row): array
    {
        // Remove leading and trailing pipes
        $row = trim($row, '|');

        // Split by pipe (but not escaped pipes)
        $cells = explode('|', $row);

        return $cells;
    }
}
