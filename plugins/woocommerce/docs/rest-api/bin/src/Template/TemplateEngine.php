<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Template;

use RuntimeException;

/**
 * Simple PHP template engine for generating HTML pages.
 */
final class TemplateEngine
{
    /**
     * @param string $templatesDir The directory containing template files
     */
    public function __construct(
        private readonly string $templatesDir,
    ) {
    }

    /**
     * Render a template with the given variables.
     *
     * @param string $template The template name (without .php extension)
     * @param array<string, mixed> $variables Variables to pass to the template
     * @return string The rendered HTML
     * @throws RuntimeException If the template is not found
     */
    public function render(string $template, array $variables = []): string
    {
        $templatePath = $this->templatesDir . '/' . $template . '.php';

        if (!file_exists($templatePath)) {
            throw new RuntimeException("Template not found: {$template}");
        }

        // Extract variables into the local scope
        extract($variables, EXTR_SKIP);

        // Make the engine available to templates for nested rendering
        $engine = $this;

        // Capture output
        ob_start();
        try {
            include $templatePath;
            return ob_get_clean() ?: '';
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new RuntimeException("Error rendering template {$template}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Escape HTML entities for safe output.
     *
     * @param string $text The text to escape
     * @return string The escaped text
     */
    public function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape and output text.
     *
     * @param string $text The text to escape and output
     */
    public function e(string $text): void
    {
        echo $this->escape($text);
    }

    /**
     * Convert Markdown to HTML (simple implementation).
     *
     * @param string $markdown The markdown text
     * @return string The HTML output
     */
    public function markdown(string $markdown): string
    {
        // Process block elements first, then inline elements
        $lines = explode("\n", $markdown);
        $html = '';
        $inCodeBlock = false;
        $codeBlockLang = '';
        $codeBlockContent = '';
        $inList = false;
        $listType = '';
        $inTable = false;
        $tableRows = [];
        $tableHasHeader = false;

        foreach ($lines as $line) {
            // Code blocks
            if (preg_match('/^```(\w*)$/', $line, $matches)) {
                if (!$inCodeBlock) {
                    $inCodeBlock = true;
                    $codeBlockLang = $matches[1] ?: 'text';
                    $codeBlockContent = '';
                } else {
                    $html .= '<pre><code class="language-' . $codeBlockLang . '">' .
                        $this->escape($codeBlockContent) . '</code></pre>';
                    $inCodeBlock = false;
                }
                continue;
            }

            if ($inCodeBlock) {
                $codeBlockContent .= ($codeBlockContent ? "\n" : '') . $line;
                continue;
            }

            // Tables (trailing | is optional)
            if (preg_match('/^\|(.+)/', $line)) {
                // Check if it's a separator row
                if (preg_match('/^\|[\s\-:|]+\|?\s*$/', $line)) {
                    // Mark that we've seen a separator (header row was before this)
                    if ($inTable && !$tableHasHeader) {
                        $tableHasHeader = true;
                    }
                    continue;
                }

                if (!$inTable) {
                    $inTable = true;
                    $tableRows = [];
                    $tableHasHeader = false;
                }

                // Remove leading | and optional trailing |, then split
                $lineContent = preg_replace('/^\||\|$/','', $line);
                $cells = array_map('trim', explode('|', $lineContent));

                // Skip rows where all cells are empty (empty header rows)
                $nonEmptyCells = array_filter($cells, fn($c) => $c !== '');
                if (count($nonEmptyCells) === 0) {
                    continue;
                }

                $tableRows[] = $cells;
                continue;
            } elseif ($inTable) {
                $html .= $this->renderTable($tableRows, $tableHasHeader);
                $inTable = false;
                $tableRows = [];
            }

            // Unordered lists
            if (preg_match('/^[\-\*]\s+(.+)$/', $line, $matches)) {
                if (!$inList || $listType !== 'ul') {
                    if ($inList) {
                        $html .= '</' . $listType . '>';
                    }
                    $html .= '<ul>';
                    $inList = true;
                    $listType = 'ul';
                }
                $html .= '<li>' . $this->inlineMarkdown($matches[1]) . '</li>';
                continue;
            }

            // Ordered lists
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                if (!$inList || $listType !== 'ol') {
                    if ($inList) {
                        $html .= '</' . $listType . '>';
                    }
                    $html .= '<ol>';
                    $inList = true;
                    $listType = 'ol';
                }
                $html .= '<li>' . $this->inlineMarkdown($matches[1]) . '</li>';
                continue;
            }

            // Close list if we're no longer in one
            if ($inList && !preg_match('/^\s*$/', $line)) {
                $html .= '</' . $listType . '>';
                $inList = false;
            }

            // Headers
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches)) {
                $level = strlen($matches[1]);
                $html .= '<h' . $level . '>' . $this->inlineMarkdown($matches[2]) . '</h' . $level . '>';
                continue;
            }

            // Empty line
            if (preg_match('/^\s*$/', $line)) {
                continue;
            }

            // Regular paragraph
            $html .= '<p>' . $this->inlineMarkdown($line) . '</p>';
        }

        // Close any remaining open elements
        if ($inList) {
            $html .= '</' . $listType . '>';
        }
        if ($inTable) {
            $html .= $this->renderTable($tableRows, $tableHasHeader);
        }

        return $html;
    }

    /**
     * Render a markdown table as HTML.
     *
     * @param array<array<string>> $rows Table rows
     * @param bool $hasHeader Whether the first row should be treated as a header
     * @return string HTML table
     */
    private function renderTable(array $rows, bool $hasHeader = true): string
    {
        if (count($rows) === 0) {
            return '';
        }

        $html = '<table class="md-table">';

        $startIndex = 0;

        // First row is header if $hasHeader is true and we have rows before the separator
        if ($hasHeader && count($rows) > 0) {
            $html .= '<thead><tr>';
            foreach ($rows[0] as $cell) {
                $html .= '<th>' . $this->inlineMarkdown($cell) . '</th>';
            }
            $html .= '</tr></thead>';
            $startIndex = 1;
        }

        // Remaining rows are body
        if (count($rows) > $startIndex) {
            $html .= '<tbody>';
            for ($i = $startIndex; $i < count($rows); $i++) {
                $html .= '<tr>';
                foreach ($rows[$i] as $cell) {
                    $html .= '<td>' . $this->inlineMarkdown($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody>';
        }

        $html .= '</table>';
        return $html;
    }

    /**
     * Process inline markdown elements.
     *
     * @param string $text The text to process
     * @return string HTML with inline elements converted
     */
    private function inlineMarkdown(string $text): string
    {
        $html = $this->escape($text);

        // Inline code (`)
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html) ?? $html;

        // Bold
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html) ?? $html;

        // Italic
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html) ?? $html;

        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html) ?? $html;

        return $html;
    }
}
