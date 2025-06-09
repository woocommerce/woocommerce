import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import TurndownService from 'turndown';
import './styles.css';

const MarkdownCopy: React.FC = () => {
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    // Show the button after a short delay to ensure the page is loaded
    const timer = setTimeout(() => {
      const breadcrumbs = document.querySelector('nav.theme-doc-breadcrumbs');
      if (breadcrumbs) {
        // Create a container for the button
        const buttonContainer = document.createElement('div');
        buttonContainer.style.display = 'inline-flex';
        buttonContainer.style.alignItems = 'center';
        buttonContainer.style.marginLeft = '8px';
        
        // Append the button container after the breadcrumbs
        breadcrumbs.appendChild(buttonContainer);
        
        // Create root and render the button
        const root = createRoot(buttonContainer);
        root.render(<MarkdownCopyButton />);
        
        setIsVisible(true);
      }
    }, 1000);

    return () => clearTimeout(timer);
  }, []);

  // Don't render anything in the original component
  return null;
};

// Separate button component to avoid re-rendering the entire component
const MarkdownCopyButton: React.FC = () => {
  const copyMarkdownToClipboard = async () => {
    try {
      // Get the main content element
      const mainContent = document.querySelector('article > .theme-doc-markdown');
      if (!mainContent) return;

      // Clone the content to avoid modifying the original
      const contentClone = mainContent.cloneNode(true) as HTMLElement;

      // Remove unwanted elements
      const elementsToRemove = contentClone.querySelectorAll('.theme-code-block, .theme-edit-this-page, .theme-last-updated, .theme-prev-next-button');
      elementsToRemove.forEach(el => el.remove());

      // Configure turndown service
      const turndownService = new TurndownService({
        headingStyle: 'atx',
        codeBlockStyle: 'fenced',
        emDelimiter: '*',
        bulletListMarker: '-',
      });

      // Add custom rules for MDX-specific elements
      turndownService.addRule('mdxCodeBlock', {
        filter: ['pre'],
        replacement: (content, node) => {
          const pre = node as HTMLElement;
          const code = pre.querySelector('code');
          if (!code) return content;
          
          const language = code.className.replace('language-', '') || '';
          return `\n\`\`\`${language}\n${code.textContent || ''}\n\`\`\`\n\n`;
        }
      });

      turndownService.addRule('mdxHeading', {
        filter: ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        replacement: (content, node) => {
          const heading = node as HTMLElement;
          const level = heading.tagName.charAt(1);
          return `\n${'#'.repeat(parseInt(level))} ${content}\n\n`;
        }
      });

      // Convert to markdown
      const markdown = turndownService.turndown(contentClone.innerHTML)
        .replace(/\n{3,}/g, '\n\n') // Remove excessive newlines
        .replace(/\\n/g, '\n') // Fix escaped newlines
        .trim();

      // Copy to clipboard
      await navigator.clipboard.writeText(markdown);
      
      // Show feedback
      const button = document.querySelector('.markdown-copy-button');
      if (button) {
        const originalHTML = button.innerHTML;
        button.innerHTML = '✓';
        setTimeout(() => {
          button.innerHTML = originalHTML;
        }, 2000);
      }
    } catch (err) {
      console.error('Failed to copy markdown:', err);
    }
  };

  return (
    <button
      className="markdown-copy-button"
      onClick={copyMarkdownToClipboard}
      title="Copy page content as markdown"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="16"
        height="16"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
      >
        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
      </svg>
    </button>
  );
};

export default MarkdownCopy;

