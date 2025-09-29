import React, { useState, useRef } from 'react';
import TurndownService from 'turndown';
import './styles.css';

const MarkdownCopy: React.FC = () => {
  const [isCopying, setIsCopying] = useState(false);
  const [copyStatus, setCopyStatus] = useState<'idle' | 'success' | 'error'>('idle');
  const buttonRef = useRef<HTMLButtonElement>(null);

  const copyMarkdownToClipboard = async () => {
    if (isCopying) return; // Prevent multiple simultaneous operations
    
    setIsCopying(true);
    setCopyStatus('idle');
    
    try {
      // Get the main content element
      const mainContent = document.querySelector('article > .theme-doc-markdown');
      if (!mainContent) {
        throw new Error('Content not found');
      }

      // Clone the content to avoid modifying the original
      const contentClone = mainContent.cloneNode(true) as HTMLElement;

      // Remove unwanted elements
      const elementsToRemove = contentClone.querySelectorAll('.theme-edit-this-page, .theme-last-updated, .theme-prev-next-button, .markdown-copy-button, .hash-link');
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
          
          const language = pre.className.split(' ').find(className => className.startsWith('language-'))?.replace('language-', '') || '';
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
      
      setCopyStatus('success');
      
      // Announce success to screen readers
      announceToScreenReader('Page content copied to clipboard');
      
    } catch (err) {
      console.error('Failed to copy markdown:', err);
      setCopyStatus('error');
      
      // Announce error to screen readers
      announceToScreenReader('Failed to copy page content');
    } finally {
      setIsCopying(false);
      
      // Restore focus to the button after operation completes
      if (buttonRef.current) {
        buttonRef.current.focus();
      }
      
      // Reset status after a delay
      setTimeout(() => {
        setCopyStatus('idle');
      }, 3000);
    }
  };

  // Function to announce messages to screen readers
  const announceToScreenReader = (message: string) => {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = message;
    
    document.body.appendChild(announcement);
    
    // Remove after announcement
    setTimeout(() => {
      document.body.removeChild(announcement);
    }, 1000);
  };

  // Handle keyboard events
  const handleKeyDown = (event: React.KeyboardEvent) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      copyMarkdownToClipboard();
    }
  };

  // Get appropriate ARIA label based on state
  const getAriaLabel = () => {
    if (isCopying) return 'Copying page content...';
    if (copyStatus === 'success') return 'Page content copied successfully';
    if (copyStatus === 'error') return 'Failed to copy page content. Click to try again';
    return 'Copy page content as markdown';
  };

  // Get appropriate button text for screen readers
  const getButtonText = () => {
    if (isCopying) return 'Copying...';
    if (copyStatus === 'success') return 'Copied!';
    if (copyStatus === 'error') return 'Copy failed';
    return 'Copy markdown';
  };

  return (
    <button
      ref={buttonRef}
      className={`markdown-copy-button ${copyStatus !== 'idle' ? `markdown-copy-button--${copyStatus}` : ''}`}
      onClick={copyMarkdownToClipboard}
      onKeyDown={handleKeyDown}
      aria-label={getAriaLabel()}
      aria-describedby="markdown-copy-description"
      disabled={isCopying}
      title="Copy page content as markdown"
    >
      {copyStatus === 'success' ? (
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
          aria-hidden="true"
          focusable="false"
        >
          <polyline points="20,6 9,17 4,12"></polyline>
        </svg>
      ) : (
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
          aria-hidden="true"
          focusable="false"
        >
          <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
          <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
        </svg>
      )}
      
      {/* Screen reader only text */}
      <span className="sr-only">{getButtonText()}</span>
      
      {/* Hidden description for additional context */}
      <div id="markdown-copy-description" className="sr-only">
        Copies the current page content as markdown format to your clipboard
      </div>
    </button>
  );
};

export default MarkdownCopy;

