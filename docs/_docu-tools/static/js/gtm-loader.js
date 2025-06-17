// Custom GTM Loader
// This script ensures GTM loads in all environments

(function() {
  'use strict';

  // Initialize dataLayer
  window.dataLayer = window.dataLayer || [];

  // GTM Container ID
  const GTM_ID = 'GTM-WW2RLFD7';

  // Load GTM script
  function loadGTM() {
    // Check if GTM is already loaded
    if (document.querySelector('script[src*="googletagmanager"]')) {
      return;
    }

    // Create GTM script
    const gtmScript = document.createElement('script');
    gtmScript.async = true;
    gtmScript.src = 'https://www.googletagmanager.com/gtm.js?id=' + GTM_ID;
    document.head.appendChild(gtmScript);

    // Create noscript fallback
    const noscript = document.createElement('noscript');
    const iframe = document.createElement('iframe');
    iframe.src = 'https://www.googletagmanager.com/ns.html?id=' + GTM_ID;
    iframe.height = '0';
    iframe.width = '0';
    iframe.style.display = 'none';
    iframe.style.visibility = 'hidden';
    noscript.appendChild(iframe);
    document.head.appendChild(noscript);
  }

  // Load GTM immediately
  loadGTM();

  // Also try to load on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadGTM);
  }

  // And on window load
  window.addEventListener('load', loadGTM);

})(); 