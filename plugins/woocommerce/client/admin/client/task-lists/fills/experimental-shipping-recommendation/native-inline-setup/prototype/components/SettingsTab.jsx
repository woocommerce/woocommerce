import { useState } from 'react';
import {
  Button,
  CheckboxControl,
  Modal,
  Notice,
  SelectControl,
  TextControl,
} from '@wordpress/components';

const initialShipFromAddresses = [
  {
    id: 'primary',
    name: 'Store address',
    address1: '123 Market St',
    address2: '',
    city: 'San Francisco',
    region: 'CA',
    postcode: '94103',
    country: 'United States',
    isDefault: true,
    useRatesAndLabels: true,
    usePickup: false,
  },
  {
    id: 'warehouse',
    name: 'East coast warehouse',
    address1: '456 Hub Avenue',
    address2: '',
    city: 'Brooklyn',
    region: 'NY',
    postcode: '11201',
    country: 'United States',
    isDefault: false,
    useRatesAndLabels: false,
    usePickup: true,
  },
];

function emptyAddress() {
  return {
    id: `address-${Date.now()}`,
    name: '',
    address1: '',
    address2: '',
    city: '',
    region: '',
    postcode: '',
    country: 'United States',
    isDefault: false,
    useRatesAndLabels: true,
    usePickup: false,
  };
}

function formatAddress(location) {
  const regionPostcode = [location.region, location.postcode]
    .filter(Boolean)
    .join(' ');
  const locality = [location.city, regionPostcode]
    .filter(Boolean)
    .join(', ');

  return [
    location.address1,
    location.address2,
    locality,
    location.country,
  ]
    .filter(Boolean)
    .join(' · ');
}

function formatAddressUse(location) {
  const uses = [];

  if (location.useRatesAndLabels) {
    uses.push('live rates and labels');
  }

  if (location.usePickup) {
    uses.push('local pickup');
  }

  if (uses.length === 0) {
    return 'Not used by shipping yet';
  }

  return `Used for ${uses.join(' and ')}`;
}

function validateAddress(draft) {
  const requiredFields = [
    ['name', 'Address name'],
    ['address1', 'Address line 1'],
    ['city', 'City'],
    ['region', 'State or region'],
    ['postcode', 'Postal code'],
    ['country', 'Country'],
  ];
  const missing = requiredFields
    .filter(([key]) => !draft[key]?.trim())
    .map(([, label]) => label);

  if (missing.length === 0) {
    return '';
  }

  return `Add ${missing.join(', ').toLowerCase()} before saving.`;
}

export function LabelsPackagesTab() {
  const [addresses, setAddresses] = useState(initialShipFromAddresses);
  const [labelSize, setLabelSize] = useState('letter');
  const [addressModal, setAddressModal] = useState(null);

  function openAddAddress() {
    setAddressModal({
      mode: 'add',
      draft: emptyAddress(),
      error: '',
    });
  }

  function openEditAddress(location) {
    setAddressModal({
      mode: 'edit',
      draft: { ...location },
      error: '',
    });
  }

  function closeAddressModal() {
    setAddressModal(null);
  }

  function updateAddressDraft(key, value) {
    setAddressModal((modal) => ({
      ...modal,
      error: '',
      draft: {
        ...modal.draft,
        [key]: value,
      },
    }));
  }

  function saveAddress() {
    const error = validateAddress(addressModal.draft);

    if (error) {
      setAddressModal((modal) => ({ ...modal, error }));
      return;
    }

    setAddresses((current) => {
      const exists = current.some((location) => location.id === addressModal.draft.id);
      let next = exists
        ? current.map((location) =>
            location.id === addressModal.draft.id ? addressModal.draft : location
          )
        : [...current, addressModal.draft];

      if (addressModal.draft.isDefault) {
        next = next.map((location) => ({
          ...location,
          isDefault: location.id === addressModal.draft.id,
        }));
      }

      return next;
    });
    setAddressModal(null);
  }

  function setDefaultAddress(id) {
    setAddresses((current) =>
      current.map((location) => ({
        ...location,
        isDefault: location.id === id,
      }))
    );
  }

  return (
    <>
      {/* Shipping origin */}
      <div className="settings-section">
        <div className="settings-section-head">
          <h3 className="settings-section-title">Ship from</h3>
          <p className="settings-section-desc">
            Addresses used for live rates, labels, pickup, and customs.
          </p>
        </div>
        <div className="settings-section-body">
          <div className="ship-from-list">
            {addresses.map((location) => (
              <div className="ship-from-row" key={location.id}>
                <div className="ship-from-main">
                  <div className="ship-from-title-row">
                    <span className="ship-from-name">{location.name}</span>
                    {location.isDefault && (
                      <span className="ship-from-default">Default</span>
                    )}
                  </div>
                  <div className="ship-from-address">
                    {formatAddress(location)}
                  </div>
                  <div className="ship-from-help">{formatAddressUse(location)}</div>
                </div>
                <div className="ship-from-actions">
                  {!location.isDefault && (
                    <Button
                      variant="tertiary"
                      onClick={() => setDefaultAddress(location.id)}
                      __next40pxDefaultSize
                    >
                      Set default
                    </Button>
                  )}
                  <Button
                    variant="tertiary"
                    onClick={() => openEditAddress(location)}
                    __next40pxDefaultSize
                  >
                    Edit
                  </Button>
                </div>
              </div>
            ))}
          </div>
          <div className="settings-section-actions">
            <Button variant="secondary" onClick={openAddAddress} __next40pxDefaultSize>
              Add address
            </Button>
          </div>
        </div>
      </div>

      {/* Package templates */}
      <div className="settings-section">
        <div className="settings-section-head">
          <h3 className="settings-section-title">Package templates</h3>
          <p className="settings-section-desc">
            Packages used when buying labels and calculating live carrier rates.
          </p>
        </div>
        <div className="settings-section-body">
          <div className="settings-row">
            <div className="settings-row-main">
              <div className="settings-row-label">Small box</div>
              <div className="settings-row-help">8 x 6 x 4 in · 0.4 lb</div>
            </div>
            <div className="settings-row-value">Default</div>
          </div>
          <div className="settings-row">
            <div className="settings-row-main">
              <div className="settings-row-label">Medium box</div>
              <div className="settings-row-help">12 x 10 x 8 in · 0.8 lb</div>
            </div>
            <Button variant="tertiary" __next40pxDefaultSize>Edit</Button>
          </div>
          <div className="settings-section-actions">
            <Button variant="secondary" __next40pxDefaultSize>
              Add package
            </Button>
          </div>
        </div>
      </div>

      {/* Shipping labels */}
      <div className="settings-section">
        <div className="settings-section-head">
          <h3 className="settings-section-title">Shipping labels</h3>
          <p className="settings-section-desc">
            Defaults used when buying labels. Package, service, insurance, and customs can still be chosen during purchase.
          </p>
        </div>
        <div className="settings-section-body">
          <div className="settings-row">
            <div className="settings-row-main">
              <div className="settings-row-label">Label size</div>
              <div className="settings-row-help">
                You can override this when printing a label.
              </div>
            </div>
            <SelectControl
              label="Label size"
              hideLabelFromVision
              value={labelSize}
              onChange={setLabelSize}
              options={[
                { value: 'thermal', label: '4 × 6 in (thermal printer)' },
                { value: 'letter', label: 'Letter (8.5 × 11 in)' },
                { value: 'a4', label: 'A4' },
              ]}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          </div>
        </div>
      </div>

      {addressModal && (
        <Modal
          title={addressModal.mode === 'add' ? 'Add address' : 'Edit ship-from address'}
          onRequestClose={closeAddressModal}
          className="address-modal"
          size="medium"
        >
          <div className="address-modal-content">
            {addressModal.error && (
              <Notice status="error" isDismissible={false}>
                {addressModal.error}
              </Notice>
            )}

            <TextControl
              label="Address name"
              value={addressModal.draft.name}
              onChange={(value) => updateAddressDraft('name', value)}
              placeholder="e.g. West coast warehouse"
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />

            <TextControl
              label="Address line 1"
              value={addressModal.draft.address1}
              onChange={(value) => updateAddressDraft('address1', value)}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />

            <TextControl
              label="Address line 2"
              value={addressModal.draft.address2}
              onChange={(value) => updateAddressDraft('address2', value)}
              placeholder="Apartment, suite, unit, building, floor, etc."
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />

            <div className="address-modal-grid">
              <TextControl
                label="City"
                value={addressModal.draft.city}
                onChange={(value) => updateAddressDraft('city', value)}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
              <TextControl
                label="State or region"
                value={addressModal.draft.region}
                onChange={(value) => updateAddressDraft('region', value)}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
              <TextControl
                label="Postal code"
                value={addressModal.draft.postcode}
                onChange={(value) => updateAddressDraft('postcode', value)}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
              <SelectControl
                label="Country"
                value={addressModal.draft.country}
                onChange={(value) => updateAddressDraft('country', value)}
                options={[
                  { value: 'United States', label: 'United States' },
                  { value: 'Canada', label: 'Canada' },
                  { value: 'United Kingdom', label: 'United Kingdom' },
                  { value: 'Australia', label: 'Australia' },
                  { value: 'Germany', label: 'Germany' },
                ]}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
            </div>

            <div className="address-modal-options">
              <CheckboxControl
                label="Use as the default ship-from address"
                checked={addressModal.draft.isDefault}
                onChange={(value) => updateAddressDraft('isDefault', value)}
                __nextHasNoMarginBottom
              />
              <CheckboxControl
                label="Use for live rates and labels"
                checked={addressModal.draft.useRatesAndLabels}
                onChange={(value) => updateAddressDraft('useRatesAndLabels', value)}
                __nextHasNoMarginBottom
              />
              <CheckboxControl
                label="Available for local pickup"
                checked={addressModal.draft.usePickup}
                onChange={(value) => updateAddressDraft('usePickup', value)}
                __nextHasNoMarginBottom
              />
            </div>

            <div className="address-modal-actions">
              <Button variant="tertiary" onClick={closeAddressModal} __next40pxDefaultSize>
                Cancel
              </Button>
              <Button variant="primary" onClick={saveAddress} __next40pxDefaultSize>
                Save address
              </Button>
            </div>
          </div>
        </Modal>
      )}
    </>
  );
}

// Store-level shipping preferences that sit outside a single zone.
export default function SettingsTab({ productGroups }) {
  const [freeRateBehavior, setFreeRateBehavior] = useState('smart');
  const [ratesBeforeAddress, setRatesBeforeAddress] = useState('hide');

  return (
    <>
      {/* Checkout defaults */}
      <div className="settings-section">
        <div className="settings-section-head">
          <h3 className="settings-section-title">Checkout defaults</h3>
          <p className="settings-section-desc">
            Automatic choices that keep checkout clear while zones and delivery options decide the rates.
          </p>
        </div>
        <div className="settings-section-body">
          <div className="settings-row">
            <div className="settings-row-main">
              <div className="settings-row-label">When free shipping is available</div>
              <div className="settings-row-help">
                Hide standard paid rates, but keep faster premium options available.
              </div>
            </div>
            <SelectControl
              label="When free shipping is available"
              hideLabelFromVision
              value={freeRateBehavior}
              onChange={setFreeRateBehavior}
              options={[
                { value: 'smart', label: 'Hide standard paid rates, keep premium options' },
                { value: 'free-only', label: 'Show only free shipping' },
                { value: 'show-all', label: 'Show every available rate' },
              ]}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          </div>
          <div className="settings-row">
            <div className="settings-row-main">
              <div className="settings-row-label">Rates before address</div>
              <div className="settings-row-help">
                Rates stay hidden until checkout knows where the order is going.
              </div>
            </div>
            <SelectControl
              label="Rates before address"
              hideLabelFromVision
              value={ratesBeforeAddress}
              onChange={setRatesBeforeAddress}
              options={[
                { value: 'hide', label: 'Hide until address is entered' },
                { value: 'estimate', label: 'Show estimated rates' },
              ]}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          </div>
        </div>
      </div>

      {/* Product groups for shipping */}
      <div className="settings-section">
        <div className="settings-section-head">
          <h3 className="settings-section-title">Product groups for shipping</h3>
          <p className="settings-section-desc">
            Define groups (e.g. "Heavy", "Fragile", "Digital") and assign them to products.
            Shipping rates can charge differently per group.
          </p>
          <p className="settings-section-note">
            Note: groups are applied <strong>per product</strong>, not per zone. Manage assignments from the product editor.
          </p>
        </div>
        <div className="settings-section-body">
          {productGroups.map((g) => (
            <div className="settings-row" key={g.id}>
              <div className="settings-row-main">
                <div className="settings-row-label">{g.name}</div>
                <div className="settings-row-help">{g.meta}</div>
              </div>
              <Button variant="tertiary" __next40pxDefaultSize>Edit</Button>
            </div>
          ))}
          <div className="product-group-actions">
            <Button variant="secondary" __next40pxDefaultSize>+ Add a group</Button>
          </div>
        </div>
      </div>
    </>
  );
}
