// This a minimalist cookie banner based on the https://github.com/Automattic/wp-calypso/tree/trunk/packages/privacy-toolset 
// It is used to manage the cookie consent and the loading of the Google Tag Manager script
import React, { useState, useEffect } from 'react';
import Cookies from 'js-cookie';
import './styles.css';

interface CookieBuckets {
  ad_storage: boolean;
  analytics_storage: boolean;
  functionality_storage: boolean;
  personalization_storage: boolean;
  security_storage: boolean;
}

interface CookiePreferences {
  ok: boolean;
  isDefault: boolean;
  buckets: CookieBuckets;
}

const DEFAULT_PREFERENCES: CookiePreferences = {
  ok: false,
  isDefault: true,
  buckets: {
    ad_storage: false,
    analytics_storage: false,
    functionality_storage: true,
    personalization_storage: true,
    security_storage: true,
  },
};

const COOKIE_NAME = 'gtm_options';

const COOKIE_OPTIONS = {
  expires: 60 * 60 * 24 * (365.25 / 2), /* six months; 365.25 -> avg days in year */
  sameSite: 'strict' as const,
  secure: process.env.NODE_ENV === 'production',
};


// Helper function to create consent options
const createConsentOptions = (buckets: CookieBuckets) => ({
  analytics_storage: buckets.analytics_storage ? 'granted' as const : 'denied' as const,
  ad_storage: buckets.ad_storage ? 'granted' as const : 'denied' as const,
});

export const CookieBanner: React.FC = () => {
  const [preferences, setPreferences] = useState<CookiePreferences>(DEFAULT_PREFERENCES);
  const [showDetails, setShowDetails] = useState(false);
  const [showBanner, setShowBanner] = useState(true);

  useEffect(() => {
    const savedPreferences = Cookies.get(COOKIE_NAME);
    if (savedPreferences) {
      try {
        const decodedPreferences = JSON.parse(decodeURIComponent(savedPreferences));
        setPreferences(decodedPreferences);
        if (decodedPreferences.ok) {
          setShowBanner(false);
          updateGtagConsent(decodedPreferences.buckets);
        } else {
          setShowBanner(true);
        }
      } catch (error) {
        setShowBanner(true);
      }
    } else {
      setShowBanner(true);
    }
  }, []);

  const updateGtagConsent = (buckets: CookieBuckets) => {
    if (typeof window.gtag === 'function') {
      window.gtag('consent', 'update', createConsentOptions(buckets));
    } else {
      // Retry after a short delay
      setTimeout(() => {
        if (typeof window.gtag === 'function') {
          window.gtag('consent', 'update', createConsentOptions(buckets));
        }
      }, 1000);
    }
  };

  const handleAcceptAll = () => {
    const newPreferences: CookiePreferences = {
      ok: true,
      isDefault: false,
      buckets: {
        ad_storage: true,
        analytics_storage: true,
        functionality_storage: true,
        personalization_storage: true,
        security_storage: true,
      },
    };
    setPreferences(newPreferences);
    Cookies.set(COOKIE_NAME, encodeURIComponent(JSON.stringify(newPreferences)), COOKIE_OPTIONS);
    updateGtagConsent(newPreferences.buckets);
    setShowBanner(false);
  };

  const handleSavePreferences = () => {
    const newPreferences = {
      ok: true,
      isDefault: false,
      buckets: {
        ...preferences.buckets,
        functionality_storage: true,
        security_storage: true,
      },
    };
    
    Cookies.set(COOKIE_NAME, encodeURIComponent(JSON.stringify(newPreferences)), COOKIE_OPTIONS);
    
    updateGtagConsent(newPreferences.buckets);
    setShowDetails(false);
    setShowBanner(false);
  };

  if (!showBanner) {
    return null;
  }

  return (
    <div className="cookie-banner">
      <div className="cookie-banner-content">
        {showDetails ? (
          <>
            <h3>Manage Your Cookie Settings</h3>
            <p>
              Select which cookies you want to accept. Essential cookies are required for the website to function properly.
            </p>
            <div className="cookie-preferences">
              <div className="cookie-option">
                <label>
                  <input
                    type="checkbox"
                    checked={preferences.buckets.functionality_storage}
                    disabled
                  />
                  Essential Cookies (Required)
                </label>
                <p>These cookies are essential for our websites and services to perform basic functions and are necessary for us to operate certain features.These include those required to allow registered users to authenticate and perform account-related functions, store preferences set by users such as account name, language, and location, and ensure our services are operating properly.</p>
              </div>
              
              <div className="cookie-option">
                <label>
                  <input
                    type="checkbox"
                    checked={preferences.buckets.analytics_storage}
                    onChange={(e) => setPreferences(prev => ({
                      ...prev,
                      buckets: {
                        ...prev.buckets,
                        analytics_storage: e.target.checked
                      }
                    }))}
                  />
                  Analytics
                </label>
                <p>These cookies allow us to optimize performance by collecting information on how users interact with our websites.</p>
              </div>
              
              <div className="cookie-option">
                <label>
                  <input
                    type="checkbox"
                    checked={preferences.buckets.ad_storage}
                    onChange={(e) => setPreferences(prev => ({
                      ...prev,
                      buckets: {
                        ...prev.buckets,
                        ad_storage: e.target.checked
                      }
                    }))}
                  />
                  Advertising
                </label>
                <p>These cookies are set by us and our advertising partners to provide you with relevant content and to understand that content's effectiveness.</p>
              </div>

              <div className="cookie-actions">
                <button
                  className="cookie-save-preferences"
                  onClick={handleSavePreferences}
                >
                  Save Preferences
                </button>
              </div>
            </div>
          </>
        ) : (
          <>
            <h3>Use of your personal data</h3>
            <p>
              We and our partners process your personal data (such as browsing data, IP Addresses, cookie information, and other unique identifiers) based on your consent and/or our legitimate interest to optimize our website, marketing activities, and your user experience.
            </p>
            <div className="cookie-actions">
              <button
                className="cookie-accept-all"
                onClick={handleAcceptAll}
              >
                Accept All
              </button>
              <button
                className="cookie-details-button"
                onClick={() => setShowDetails(true)}
              >
                Customize Preferences
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
}; 