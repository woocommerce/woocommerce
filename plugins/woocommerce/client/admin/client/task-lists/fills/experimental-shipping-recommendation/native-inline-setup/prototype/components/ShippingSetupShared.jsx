import { Button, Icon } from '@wordpress/components';
import { check } from '@wordpress/icons';
import { summarizeMethods } from '../data/mockData.js';
import {
  COUNTRY_TREE,
  computeTags,
  findNodeById,
  getAllLeaves,
  tagsToZones,
} from '../data/countryTree.js';

export const STORE_ADDRESS = '456 Hub Avenue, Brooklyn, NY 11201';

export const setupSteps = [
  {
    id: 'setup-path',
    title: 'Choose how you want to setup',
  },
  {
    id: 'destinations',
    title: 'Select regions',
  },
  {
    id: 'review-rates',
    title: 'Review rates',
  },
  {
    id: 'connect',
    title: 'Connect to WordPress.com',
  },
];

function makePresetSelected() {
  const selected = new Set();
  const us = findNodeById(COUNTRY_TREE, 'us');
  const ca = findNodeById(COUNTRY_TREE, 'ca');

  if (us) getAllLeaves(us).forEach((leaf) => selected.add(leaf.id));
  if (ca) getAllLeaves(ca).forEach((leaf) => selected.add(leaf.id));

  return selected;
}

export function makeInitialTreeValue() {
  return {
    selected: makePresetSelected(),
    anywhereElseSelected: true,
    splitOut: new Set(['us-ak', 'us-hi']),
  };
}

export function makeManualTreeValue() {
  return {
    selected: new Set(),
    anywhereElseSelected: false,
    splitOut: new Set(),
  };
}

export function normalizeZone(zone) {
  return {
    ...zone,
    methods: {
      ...zone.methods,
      pickup: {
        hours: '',
        instructions: '',
        ...(zone.methods.pickup || {}),
        address: zone.methods.pickup?.address || STORE_ADDRESS,
      },
    },
  };
}

export function zonesFromTreeValue(treeValue) {
  const tags = computeTags(
    treeValue.selected,
    treeValue.anywhereElseSelected,
    treeValue.splitOut
  );

  return tagsToZones(tags).map(normalizeZone);
}

export function methodCount(zones) {
  return zones.reduce((count, zone) => {
    const customMethods = Array.isArray(zone.methods.custom)
      ? zone.methods.custom.filter((method) => method.on).length
      : 0;

    return count +
      ['flat', 'free', 'pickup', 'live'].filter((key) => zone.methods[key]?.on).length +
      customMethods;
  }, 0);
}

export function activeTags(treeValue) {
  return computeTags(treeValue.selected, treeValue.anywhereElseSelected, treeValue.splitOut);
}

export function ShippingSetupPathChoice({
  selectedPath = 'guided',
  onChangePath,
  onSkip,
}) {
  const setupOptions = [
    {
      id: 'guided',
      label: 'Start with Woo recommendations',
      badge: 'Recommended',
      description: 'Woo suggests destinations, rates, and delivery options. Edit anything before saving.',
    },
    {
      id: 'manual',
      label: 'Set up manually',
      description: 'Build from scratch for custom stores, client builds, or exact zones and rates.',
    },
  ];

  return (
    <section className="shipping-setup-path-choice" aria-labelledby="setup-path-title">
      <div className="shipping-setup-path-copy">
        <h2 id="setup-path-title">How should Woo set up shipping?</h2>
        <p>Pick guided setup for suggestions, or manual if you know your exact config.</p>
      </div>

      <fieldset className="shipping-setup-method-fieldset">
        <legend>Setup method</legend>
        <div className="shipping-setup-method-options">
          {setupOptions.map((option) => {
            const isSelected = selectedPath === option.id;

            return (
              <label
                className={`shipping-setup-method-card${isSelected ? ' is-selected' : ''}`}
                key={option.id}
              >
                <span className="shipping-setup-method-card-head">
                  <input
                    type="radio"
                    name="shipping-setup-method"
                    value={option.id}
                    checked={isSelected}
                    onChange={() => onChangePath?.(option.id)}
                  />
                  <span className="shipping-setup-method-title">{option.label}</span>
                  {option.badge && (
                    <span className="shipping-setup-recommended-badge">
                      {option.badge}
                    </span>
                  )}
                </span>
                <span className="shipping-setup-method-description">
                  {option.description}
                </span>
              </label>
            );
          })}
        </div>
      </fieldset>

      {onSkip && (
        <div className="shipping-setup-free-everywhere">
          <button type="button" className="empty-tertiary-link" onClick={onSkip}>
            Use free shipping everywhere
          </button>
          <span className="shipping-setup-free-everywhere-help">
            Offer free shipping on every order. Change this later.
          </span>
        </div>
      )}
    </section>
  );
}

export function ShippingSetupStepper({ currentStep, variant = 'modal' }) {
  return (
    <aside className={`settings-payments-onboarding-modal__sidebar shipping-setup-sidebar is-${variant}`} aria-label="Shipping setup steps">
      <div className="settings-payments-onboarding-modal__sidebar--header">
        <h2 className="settings-payments-onboarding-modal__sidebar--header-title">Set up Woo Shipping</h2>
      </div>
      <ol className="settings-payments-onboarding-modal__sidebar--list shipping-setup-step-list">
        {setupSteps.map((step, index) => {
          const isDone = index < currentStep;
          const isActive = index === currentStep;
          return (
            <li
              key={step.id}
              className={`settings-payments-onboarding-modal__sidebar--list-item shipping-setup-step${isDone ? ' is-completed' : ''}${isActive ? ' is-active' : ''}`}
              aria-current={isActive ? 'step' : undefined}
            >
              <span
                className="settings-payments-onboarding-modal__sidebar--list-item-icon shipping-setup-step-index"
                aria-hidden="true"
              />
              <span>
                <span className="settings-payments-onboarding-modal__sidebar--list-item-label shipping-setup-step-title">{step.title}</span>
              </span>
            </li>
          );
        })}
      </ol>
    </aside>
  );
}

export function ZoneSummaryCard({ zone, onEdit }) {
  const methods = summarizeMethods(zone.methods);

  return (
    <div className="setup-zone-card">
      <div className="setup-zone-card-header">
        <div>
          <h3>{zone.name}</h3>
          <p>{zone.regions}</p>
        </div>
        <Button variant="secondary" __next40pxDefaultSize onClick={() => onEdit(zone.id)}>
          Edit delivery options
        </Button>
      </div>
      <div className="setup-zone-methods">
        {methods.map((method, index) => (
          <div className="setup-zone-method" key={`${method.name}-${index}`}>
            <span>{method.name}</span>
            {method.detail && <span>{method.detail}</span>}
          </div>
        ))}
      </div>
    </div>
  );
}

export function WordPressConnectStep() {
  return (
    <section className="wp-connect-step" aria-labelledby="setup-connect-title">
      <div className="shipping-setup-panel-header">
        <h2 id="setup-connect-title">Connect to WordPress.com</h2>
        <p>
          Connect this store to WordPress.com to use Woo Shipping services,
          including discounted labels, live rates, and tracking.
        </p>
      </div>
    </section>
  );
}

export function ConnectingWordPressStep() {
  return (
    <section className="wp-connect-status" aria-live="polite" aria-labelledby="connecting-wordpress-title">
      <div className="wp-connect-spinner" aria-hidden="true" />
      <p className="shipping-setup-eyebrow">WordPress.com</p>
      <h2 id="connecting-wordpress-title">Connecting</h2>
      <p>
        We are connecting this store to WordPress.com.
      </p>
      <div className="wp-connect-progress" aria-hidden="true">
        <span />
      </div>
    </section>
  );
}

export function ShippingReadyStep({
  onViewZonesAndRates,
  onCloseWindow,
}) {
  return (
    <section className="wp-connect-status wp-connect-ready" aria-live="polite" aria-labelledby="shipping-ready-title">
      <div className="wp-connect-success" aria-hidden="true">
        <Icon icon={check} size={20} />
      </div>
      <h2 id="shipping-ready-title">You’re ready to use shipping!</h2>
      <p>
        Great news — your Woo Shipping has been connected and setup.
      </p>
      <div className="wp-connect-ready-actions">
        <Button
          variant="primary"
          __next40pxDefaultSize
          onClick={onViewZonesAndRates}
        >
          Go to view zones and rates
        </Button>
        <div className="wp-connect-ready-divider" aria-hidden="true">
          <span>OR</span>
        </div>
        <Button
          variant="secondary"
          __next40pxDefaultSize
          onClick={onCloseWindow}
        >
          Close this window
        </Button>
      </div>
    </section>
  );
}
