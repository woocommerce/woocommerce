/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Card, ToggleControl } from '@wordpress/components';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './settings-payments-woocommerce-payments.scss';

interface WooCommercePaymentsSettings {
    enabled: boolean;
    // Add other WooCommerce Payments specific settings here
}

export const SettingsPaymentsWooCommercePayments: React.FC = () => {
    const [settings, setSettings] = useState<WooCommercePaymentsSettings | null>(null);

    const { woocommercePaymentsSettings, isLoading } = useSelect((select) => {
        const { getSettings, hasFinishedResolution } = select(SETTINGS_STORE_NAME);
        const settingsGroup = getSettings('wc_woocommerce_payments');
        return {
            woocommercePaymentsSettings: settingsGroup,
            isLoading: !hasFinishedResolution('getSettings', ['wc_woocommerce_payments']),
        };
    });

    useEffect(() => {
        if (woocommercePaymentsSettings) {
            setSettings(woocommercePaymentsSettings);
        }
    }, [woocommercePaymentsSettings]);

    const handleToggle = (value: boolean) => {
        setSettings((prevSettings) => ({
            ...prevSettings!,
            enabled: value,
        }));
        // Here you would typically dispatch an action to update the settings in the store
        // and make an API call to save the changes
    };

    if (isLoading || !settings) {
        return <div>Loading WooCommerce Payments settings...</div>;
    }

    return (
        <div className="settings-payments-woocommerce-payments__container">
            <h1>WooCommerce Payments Settings</h1>
            <Card>
                <ToggleControl
                    label="Enable WooCommerce Payments"
                    checked={settings.enabled}
                    onChange={handleToggle}
                />
                {/* Add more WooCommerce Payments specific settings here */}
            </Card>
        </div>
    );
};

export default SettingsPaymentsWooCommercePayments;
