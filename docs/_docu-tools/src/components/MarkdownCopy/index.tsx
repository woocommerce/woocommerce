import React, { useEffect, useState, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import TurndownService from 'turndown';
import './styles.css';

const MarkdownCopy: React.FC = () => {
  const [isVisible, setIsVisible] = useState(false);
  const rootRef = useRef<ReturnType<typeof createRoot> | null>(null);
  const buttonContainerRef = useRef<HTMLDivElement | null>(null);

  const createButton = () => {
    // Clean up existing button if it exists
    if (rootRef.current) {
      try {
        rootRef.current.unmount();
      } catch (error) {
        // Ignore unmount errors
      }
      rootRef.current = null;
    }

    if (buttonContainerRef.current) {
      buttonContainerRef.current.remove();
      buttonContainerRef.current = null;
    }

    const breadcrumbs = document.querySelector('nav.theme-doc-breadcrumbs');
    if (breadcrumbs) {
      // Create a container for the button
      const buttonContainer = document.createElement('div');
      buttonContainer.style.display = 'inline-flex';
      buttonContainer.style.alignItems = 'center';
      buttonContainer.style.marginLeft = '8px';
      
      // Append the button container after the breadcrumbs
      breadcrumbs.appendChild(buttonContainer);
      
      // Store references
      buttonContainerRef.current = buttonContainer;
      
      // Create root and render the button
      const root = createRoot(buttonContainer);
      root.render(<MarkdownCopyButton />);
      rootRef.current = root;
      
      setIsVisible(true);
    }
  };

  const cleanup = () => {
    if (rootRef.current) {
      try {
        rootRef.current.unmount();
      } catch (error) {
        // Ignore unmount errors
      }
      rootRef.current = null;
    }

    if (buttonContainerRef.current) {
      buttonContainerRef.current.remove();
      buttonContainerRef.current = null;
    }
  };

  useEffect(() => {
    // Initial setup
    const timer = setTimeout(() => {
      createButton();
    }, 1000);

    // Listen for navigation changes
    const handleNavigation = () => {
      // Small delay to ensure DOM is updated
      setTimeout(() => {
        createButton();
      }, 100);
    };

    // Listen for popstate (browser back/forward)
    window.addEventListener('popstate', handleNavigation);

    // Listen for pushstate/replacestate (programmatic navigation)
    const originalPushState = history.pushState;
    const originalReplaceState = history.replaceState;

    history.pushState = function(...args) {
      originalPushState.apply(history, args);
      handleNavigation();
    };

    history.replaceState = function(...args) {
      originalReplaceState.apply(history, args);
      handleNavigation();
    };

    // Use MutationObserver to detect DOM changes that might indicate navigation
    const observer = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        if (mutation.type === 'childList') {
          // Check if breadcrumbs were added/removed
          const hasBreadcrumbsChange = Array.from(mutation.addedNodes).some(node => 
            node.nodeType === Node.ELEMENT_NODE && 
            (node as Element).querySelector?.('nav.theme-doc-breadcrumbs')
          ) || Array.from(mutation.removedNodes).some(node => 
            node.nodeType === Node.ELEMENT_NODE && 
            (node as Element).querySelector?.('nav.theme-doc-breadcrumbs')
          );

          if (hasBreadcrumbsChange) {
            handleNavigation();
            break;
          }
        }
      }
    });

    // Observe the entire document for changes
    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

    return () => {
      clearTimeout(timer);
      window.removeEventListener('popstate', handleNavigation);
      
      // Restore original history methods
      history.pushState = originalPushState;
      history.replaceState = originalReplaceState;
      
      observer.disconnect();
      cleanup();
    };
  }, []);

  // Don't render anything in the original component
  return null;
};

// Separate button component to avoid re-rendering the entire component
const MarkdownCopyButton: React.FC = () => {
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

