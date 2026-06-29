import { Fragment, useEffect, useMemo, useState } from 'react';
import { Button } from '@wordpress/components';
import { summarizeMethods } from '../data/mockData.js';
import TreeCombo from './TreeCombo.jsx';
import {
  activeTags,
  ConnectingWordPressStep,
  makeInitialTreeValue,
  makeManualTreeValue,
  normalizeZone,
  setupSteps,
  ShippingReadyStep,
  ShippingSetupPathChoice,
  WordPressConnectStep,
  zonesFromTreeValue,
} from './ShippingSetupShared.jsx';
import SuggestedRatesNotice from './SuggestedRatesNotice.jsx';
import ZoneEditPanel from './ZoneEditPanel.jsx';

const STEP_SETUP_PATH = 0;
const STEP_DESTINATIONS = 1;
const STEP_REVIEW_RATES = 2;
const STEP_CONNECT = 3;

const fullPageProgressSteps = [
  {
    id: 'destinations',
    title: 'Where you ship',
  },
  {
    id: 'review-rates',
    title: 'Review rates',
  },
  {
    id: 'connect',
    title: 'Connect to WordPress',
  },
];

function HorizontalStepper({ currentStep }) {
  const progressStep = Math.max(0, currentStep - 1);

  return (
    <div className="shipping-fullpage-stepper" aria-label="Shipping setup progress">
      {fullPageProgressSteps.map((step, index) => {
        const isDone = index < progressStep;
        const isActive = index === progressStep;
        return (
          <Fragment key={step.id}>
            <div
              className={`shipping-fullpage-step${isDone ? ' is-completed' : ''}${isActive ? ' is-active' : ''}`}
              aria-current={isActive ? 'step' : undefined}
            >
              <span className="shipping-fullpage-step-index">{isDone ? '✓' : index + 1}</span>
              <span className="shipping-fullpage-step-title">{step.title}</span>
            </div>
            {index < fullPageProgressSteps.length - 1 && (
              <span className="shipping-fullpage-step-connector" aria-hidden="true" />
            )}
          </Fragment>
        );
      })}
    </div>
  );
}

function ReviewRatesStep({ zones, onEditZone }) {
  return (
    <section aria-labelledby="shipping-fullpage-review-title">
      <h2 className="screen-reader-text" id="shipping-fullpage-review-title">
        Review rates
      </h2>

      <SuggestedRatesNotice />

      <div className="shipping-setup-rates-review">
        {zones.map((zone) => (
          <div className="shipping-setup-rates-zone" key={zone.id}>
            <div className="shipping-setup-rates-zone-header">
              <div>
                <h3>{zone.name}</h3>
                <p>{zone.regions}</p>
              </div>
              <Button
                variant="secondary"
                __next40pxDefaultSize
                onClick={() => onEditZone(zone.id)}
              >
                Edit delivery options
              </Button>
            </div>
            <div className="shipping-setup-rates-zone-methods">
              {summarizeMethods(zone.methods).map((method, index) => (
                <div className="shipping-setup-rates-method" key={`${zone.id}-${method.name}-${index}`}>
                  <span>{method.name}</span>
                  {method.detail && <span>{method.detail}</span>}
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}

export default function ShippingSetupFullPage({
  productGroups = [],
  onBack,
  onFinish,
  onSkip,
}) {
  const [currentStep, setCurrentStep] = useState(0);
  const [setupPath, setSetupPath] = useState('guided');
  const [treeValue, setTreeValue] = useState(makeInitialTreeValue);
  const [zones, setZones] = useState(() => zonesFromTreeValue(makeInitialTreeValue()));
  const [connectState, setConnectState] = useState('ready');
  const [editingZoneId, setEditingZoneId] = useState(null);

  const treeTags = useMemo(() => activeTags(treeValue), [treeValue]);
  const hasDestinations = treeTags.length > 0;
  const isManualPath = setupPath === 'manual';
  const isConnectSuccess = currentStep === STEP_CONNECT && connectState === 'success';
  const editingZone = currentStep === STEP_REVIEW_RATES
    ? zones.find((zone) => zone.id === editingZoneId)
    : null;

  useEffect(() => {
    if (connectState !== 'connecting') {
      return undefined;
    }

    const timer = window.setTimeout(() => {
      setConnectState('success');
    }, 900);

    return () => window.clearTimeout(timer);
  }, [connectState]);

  useEffect(() => {
    if (currentStep !== STEP_REVIEW_RATES) {
      setEditingZoneId(null);
    }
  }, [currentStep]);

  function handleSetupPathChange(nextPath) {
    const nextTreeValue = nextPath === 'manual'
      ? makeManualTreeValue()
      : makeInitialTreeValue();

    setSetupPath(nextPath);
    setTreeValue(nextTreeValue);
    setZones(zonesFromTreeValue(nextTreeValue));
    setConnectState('ready');
  }

  function goNext() {
    if (currentStep === STEP_DESTINATIONS) {
      setZones(zonesFromTreeValue(treeValue));
    }

    if (currentStep === setupSteps.length - 1) {
      if (connectState === 'success') {
        onFinish(zones, 'zones');
        return;
      }

      setConnectState('connecting');
      return;
    }

    setCurrentStep((step) => step + 1);
  }

  function viewZonesAndRates() {
    onFinish(zones, 'zones');
  }

  function saveEditedZone(updates) {
    setZones((currentZones) => currentZones.map((zone) => (
      zone.id === editingZoneId
        ? normalizeZone({ ...zone, ...updates })
        : zone
    )));
    setEditingZoneId(null);
  }

  function closeWindow() {
    onFinish(zones, 'providers');
  }

  function goBack() {
    if (currentStep === STEP_SETUP_PATH) {
      onBack();
      return;
    }

    setCurrentStep((step) => Math.max(STEP_SETUP_PATH, step - 1));
  }

  const primaryLabel = currentStep === setupSteps.length - 1
    ? connectState === 'success'
      ? 'Go to the shipping zones & rates'
      : connectState === 'connecting'
        ? 'Connecting...'
        : 'Connect to WordPress.com'
    : 'Continue';

  return (
    <div className={`shipping-fullpage-setup${currentStep === STEP_SETUP_PATH ? ' is-setup-path' : ''}${currentStep === STEP_DESTINATIONS ? ' is-destinations' : ''}${isConnectSuccess ? ' is-connect-success' : ''}`}>
      {currentStep !== STEP_SETUP_PATH && !isConnectSuccess && (
        <HorizontalStepper currentStep={currentStep} />
      )}

      <div className={`shipping-fullpage-panel${currentStep === STEP_REVIEW_RATES ? ' is-review-rates' : ''}${currentStep === STEP_SETUP_PATH ? ' is-setup-path' : ''}${currentStep === STEP_DESTINATIONS ? ' is-destinations' : ''}${isConnectSuccess ? ' is-connect-success' : ''}`}>
        {currentStep === STEP_SETUP_PATH && (
          <ShippingSetupPathChoice
            selectedPath={setupPath}
            onChangePath={handleSetupPathChange}
            onSkip={onSkip}
          />
        )}

        {currentStep === STEP_DESTINATIONS && (
          <section aria-labelledby="shipping-fullpage-destinations-title">
            <h2 className="screen-reader-text" id="shipping-fullpage-destinations-title">
              Where you ship
            </h2>
            <p className="shipping-fullpage-destination-copy">
              {isManualPath
                ? 'Choose where this store ships. Add only the destinations you want, then set delivery options for each shipping zone.'
                : 'We preselect common regions for a US store. Split out destinations that need their own rates before reviewing checkout choices.'}
            </p>
            <TreeCombo
              label="Countries and regions"
              value={treeValue}
              onChange={setTreeValue}
            />
          </section>
        )}

        {currentStep === STEP_REVIEW_RATES && (
          <ReviewRatesStep
            zones={zones}
            onEditZone={setEditingZoneId}
          />
        )}

        {currentStep === STEP_CONNECT && connectState === 'ready' && (
          <WordPressConnectStep zones={zones} />
        )}

        {currentStep === STEP_CONNECT && connectState === 'connecting' && (
          <ConnectingWordPressStep />
        )}

        {currentStep === STEP_CONNECT && connectState === 'success' && (
          <ShippingReadyStep
            onViewZonesAndRates={viewZonesAndRates}
            onCloseWindow={closeWindow}
          />
        )}
      </div>

      {!isConnectSuccess && (
      <div className="shipping-fullpage-footer">
        <div className="shipping-fullpage-footer-exit">
          {currentStep !== STEP_SETUP_PATH && (
            <Button
              variant="tertiary"
              __next40pxDefaultSize
              disabled={connectState === 'connecting'}
              onClick={goBack}
            >
              Back
            </Button>
          )}
        </div>
        <div className="shipping-fullpage-footer-actions">
          {currentStep === setupSteps.length - 1 && connectState === 'ready' && (
            <Button variant="tertiary" __next40pxDefaultSize onClick={() => onFinish(zones)}>
              Skip for now
            </Button>
          )}
          <Button
            variant="primary"
            __next40pxDefaultSize
            disabled={(currentStep === STEP_DESTINATIONS && !hasDestinations) || connectState === 'connecting'}
            isBusy={connectState === 'connecting'}
            onClick={goNext}
          >
            {primaryLabel}
          </Button>
        </div>
      </div>
      )}

      {editingZone && (
        <ZoneEditPanel
          mode="methods"
          zone={editingZone}
          productGroups={productGroups}
          onSave={saveEditedZone}
          onCancel={() => setEditingZoneId(null)}
        />
      )}

    </div>
  );
}
