// GTM Consent Management Script
// This script runs before GTM loads to set up proper consent management

(function() {
  'use strict';

  // Initialize dataLayer immediately
  window.dataLayer = window.dataLayer || [];

  // Function to get cookie value
  function getCookie(name) {
    const value = '; ' + document.cookie;
    const parts = value.split('; ' + name + '=');
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
  }

  // Function to decode cookie value
  function decodeCookie(cookieValue) {
    try {
      return JSON.parse(decodeURIComponent(cookieValue));
    } catch (e) {
      return null;
    }
  }

  // Function to create consent options
  function createConsentOptions(buckets) {
    return {
      analytics_storage: buckets.analytics_storage ? 'granted' : 'denied',
      ad_storage: buckets.ad_storage ? 'granted' : 'denied',
      functionality_storage: buckets.functionality_storage ? 'granted' : 'denied',
      personalization_storage: buckets.personalization_storage ? 'granted' : 'denied',
      security_storage: buckets.security_storage ? 'granted' : 'denied',
    };
  }

  // Check for existing cookie preferences
  const cookieValue = getCookie('gtm_options');
  let preferences = null;

  if (cookieValue) {
    preferences = decodeCookie(cookieValue);
  }

  // Set default consent state - deny analytics by default
  const defaultConsent = {
    analytics_storage: 'denied', // Changed back to denied
    ad_storage: 'denied',
    functionality_storage: 'granted',
    personalization_storage: 'granted',
    security_storage: 'granted',
  };

  // Use saved preferences if available, otherwise use defaults
  const consentState = preferences && preferences.ok ? 
    createConsentOptions(preferences.buckets) : 
    defaultConsent;

  // Push consent state to dataLayer immediately
  window.dataLayer.push({
    'event': 'consent',
    'action': 'default',
    ...consentState
  });

  // Create a global function to update consent
  window.updateGTMConsent = function(buckets) {
    const consentOptions = createConsentOptions(buckets);
    
    // Update dataLayer
    window.dataLayer.push({
      'event': 'consent',
      'action': 'update',
      ...consentOptions
    });

    // Also try to use gtag if available
    if (typeof window.gtag === 'function') {
      window.gtag('consent', 'update', consentOptions);
    }
  };

  // Also set up a listener for when GTM loads
  window.addEventListener('load', function() {
    // Re-push consent state after page load to ensure GTM picks it up
    if (window.dataLayer) {
      window.dataLayer.push({
        'event': 'consent',
        'action': 'default',
        ...consentState
      });
    }
  });
})(); 