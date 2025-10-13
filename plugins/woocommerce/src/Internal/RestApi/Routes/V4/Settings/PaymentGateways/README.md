# Payment Gateways Settings API

REST API endpoint for managing payment gateway settings in WooCommerce.

## Endpoint

```http
GET  /wc/v4/settings/payment-gateways/{gateway_id}
PUT  /wc/v4/settings/payment-gateways/{gateway_id}
```

## Response Structure

### GET Response

When retrieving a payment gateway's settings, the response includes:

```json
{
  "id": "bacs",
  "title": "Direct bank transfer",
  "description": "Make your payment directly into our bank account.",
  "order": 1,
  "enabled": false,
  "method_title": "BACS",
  "method_description": "Allows payments by BACS...",
  "method_supports": ["products"],
  "values": {
    "enabled": "no",
    "title": "Direct bank transfer",
    "description": "Make your payment directly into our bank account.",
    "instructions": "Make your payment directly...",
    "account_details": [
      {
        "account_name": "Company Name",
        "account_number": "12345678",
        "bank_name": "Bank Name",
        "sort_code": "12-34-56",
        "iban": "GB00BANK12345678",
        "bic": "BANKGB00"
      }
    ]
  },
  "groups": {
    "settings": {
      "title": "Settings",
      "description": "",
      "order": 1,
      "fields": [
        {
          "id": "enabled",
          "label": "Enable/Disable",
          "type": "checkbox",
          "desc": "Enable this payment gateway"
        },
        {
          "id": "title",
          "label": "Title",
          "type": "text",
          "desc": "This controls the title which the user sees during checkout."
        },
        {
          "id": "description",
          "label": "Description",
          "type": "text",
          "desc": "This controls the description which the user sees during checkout."
        },
        {
          "id": "order",
          "label": "Order",
          "type": "number",
          "desc": "Determines the display order of payment gateways during checkout."
        },
        {
          "id": "instructions",
          "label": "Instructions",
          "type": "textarea",
          "desc": "Instructions that will be added to the thank you page and emails."
        }
      ]
    }
  }
}
```

### Field Descriptions

- **id**: Gateway identifier (readonly)
- **title**: Gateway title shown to customers during checkout
- **description**: Gateway description shown during checkout
- **order**: Display order in checkout (lower = higher priority)
- **enabled**: Whether the gateway is enabled
- **method_title**: Internal gateway title (readonly)
- **method_description**: Internal gateway description (readonly)
- **method_supports**: Features supported by the gateway (readonly)
- **values**: Flat key-value object containing all current field values
- **groups**: Organized field definitions with metadata for building UI

## PUT Request

### Request Format

```json
{
  "values": {
    "enabled": true,
    "title": "Bank Transfer",
    "description": "Pay via direct bank transfer",
    "instructions": "Please use the following bank details...",
    "account_details": [
      {
        "account_name": "My Company Ltd",
        "account_number": "12345678",
        "bank_name": "Test Bank",
        "sort_code": "12-34-56",
        "iban": "GB00TEST12345678",
        "bic": "TESTBIC"
      }
    ]
  }
}
```

### Response

Returns the updated gateway object with the same structure as the GET response.

## Standard vs Special Fields

### Standard Fields

Standard fields are those defined in the gateway's `form_fields` property:

- Automatically validated and sanitized based on field type
- Stored in the gateway's settings option
- Examples: `instructions`, `enable_for_virtual`, `enable_for_methods`

### Top-Level Fields

These fields are handled at the gateway object level:

- `enabled`: Gateway enabled status
- `title`: Gateway title
- `description`: Gateway description
- `order`: Gateway display order

### Special Fields

Special fields require custom handling and are gateway-specific:

- Not defined in `form_fields`
- Require custom validation, sanitization, and storage logic
- Example: BACS `account_details` (stored in separate option)

## Creating Gateway-Specific Schemas

To add custom handling for a payment gateway, create a schema class that extends `AbstractPaymentGatewaySettingsSchema`.

### Example: BACS Gateway Schema

```php
<?php
namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema;

use WC_Payment_Gateway;
use WP_Error;

class BacsGatewaySettingsSchema extends AbstractPaymentGatewaySettingsSchema {

    /**
     * Provide values for special fields.
     */
    protected function get_special_field_values( WC_Payment_Gateway $gateway ): array {
        return array(
            'account_details' => get_option( 'woocommerce_bacs_accounts', array() ),
        );
    }

    /**
     * Define special field schemas for UI.
     */
    protected function get_special_field_schemas( WC_Payment_Gateway $gateway ): array {
        return array(
            array(
                'id'    => 'account_details',
                'label' => __( 'Account details', 'woocommerce' ),
                'type'  => 'array',
                'desc'  => __( 'Bank account details for direct bank transfer.', 'woocommerce' ),
            ),
        );
    }

    /**
     * Identify special fields.
     */
    public function is_special_field( string $field_id ): bool {
        return 'account_details' === $field_id;
    }

    /**
     * Validate and sanitize special fields.
     */
    public function validate_and_sanitize_special_fields(
        WC_Payment_Gateway $gateway,
        array $values
    ) {
        $validated = array();

        foreach ( $values as $field_id => $value ) {
            if ( 'account_details' === $field_id ) {
                // Custom validation logic
                if ( ! is_array( $value ) ) {
                    return new WP_Error(
                        'rest_invalid_param',
                        __( 'Account details must be an array.', 'woocommerce' ),
                        array( 'status' => 400 )
                    );
                }

                $validated[ $field_id ] = $this->sanitize_accounts( $value );
            }
        }

        return $validated;
    }

    /**
     * Update special fields in database.
     */
    public function update_special_fields(
        WC_Payment_Gateway $gateway,
        array $values
    ): void {
        foreach ( $values as $field_id => $value ) {
            if ( 'account_details' === $field_id ) {
                update_option( 'woocommerce_bacs_accounts', $value );
            }
        }
    }

    private function sanitize_accounts( array $accounts ): array {
        // Custom sanitization logic
        return array_map( function( $account ) {
            return array_map( 'sanitize_text_field', $account );
        }, $accounts );
    }
}
```

### Registering Custom Schema

Update the `get_schema_for_gateway()` method in `Controller.php`:

```php
private function get_schema_for_gateway( string $gateway_id ): AbstractPaymentGatewaySettingsSchema {
    switch ( $gateway_id ) {
        case 'bacs':
            return new BacsGatewaySettingsSchema();
        case 'cod':
            return new CodGatewaySettingsSchema();
        case 'my_custom_gateway':
            return new MyCustomGatewaySettingsSchema();
        default:
            return new PaymentGatewaySettingsSchema();
    }
}
```

## Field Types

The API supports standard WooCommerce gateway field types:

- **text**: Single-line text input
- **textarea**: Multi-line text input
- **checkbox**: Boolean checkbox
- **select**: Dropdown select
- **multiselect**: Multiple selection dropdown
- **number**: Numeric input
- **email**: Email input
- **password**: Password input
- **color**: Color picker

All field types are automatically sanitized and validated according to their type.

## Examples

### Enable a Gateway

```bash
curl -X PUT "https://example.com/wp-json/wc/v4/settings/payment-gateways/bacs" \
  -H "Content-Type: application/json" \
  -u consumer_key:consumer_secret \
  -d '{
    "values": {
      "enabled": true
    }
  }'
```

### Update Gateway Settings

```bash
curl -X PUT "https://example.com/wp-json/wc/v4/settings/payment-gateways/bacs" \
  -H "Content-Type: application/json" \
  -u consumer_key:consumer_secret \
  -d '{
    "values": {
      "enabled": true,
      "title": "Direct Bank Transfer",
      "instructions": "Please transfer funds to our bank account.",
      "order": 1
    }
  }'
```

### Update BACS Account Details

```bash
curl -X PUT "https://example.com/wp-json/wc/v4/settings/payment-gateways/bacs" \
  -H "Content-Type: application/json" \
  -u consumer_key:consumer_secret \
  -d '{
    "values": {
      "account_details": [
        {
          "account_name": "My Company Ltd",
          "account_number": "12345678",
          "bank_name": "Test Bank",
          "sort_code": "12-34-56",
          "iban": "GB00TEST12345678",
          "bic": "TESTBIC"
        }
      ]
    }
  }'
```

## Error Responses

### Invalid Gateway ID

```json
{
  "code": "woocommerce_rest_payment_gateway_invalid_id",
  "message": "Invalid payment gateway ID.",
  "data": {
    "status": 404
  }
}
```

### Missing Required Parameter

```json
{
  "code": "rest_missing_callback_param",
  "message": "Missing parameter(s): values",
  "data": {
    "status": 400
  }
}
```

### Invalid Field Value

```json
{
  "code": "rest_invalid_param",
  "message": "Invalid value for enable_for_methods. Valid options: flat_rate, local_pickup",
  "data": {
    "status": 400
  }
}
```

## Architecture

```text
Controller.php
├── get_item()           - GET endpoint handler
├── update_item()        - PUT endpoint handler
└── get_schema_for_gateway() - Routes to gateway-specific schema

Schema/
├── AbstractPaymentGatewaySettingsSchema.php
│   ├── get_item_response()                    - Formats GET response
│   ├── get_values()                            - Gets all field values
│   ├── get_groups()                            - Gets field definitions
│   ├── validate_and_sanitize_settings()       - Validates standard fields
│   ├── get_special_field_values()             - Override for special fields
│   ├── get_special_field_schemas()            - Override for special field defs
│   ├── is_special_field()                      - Override to identify special fields
│   ├── validate_and_sanitize_special_fields() - Override for special validation
│   └── update_special_fields()                 - Override for special updates
│
├── PaymentGatewaySettingsSchema.php  - Generic schema (no special fields)
├── BacsGatewaySettingsSchema.php     - BACS-specific (account_details)
└── CodGatewaySettingsSchema.php      - COD-specific (future extensions)
```

## Testing

Tests are located in:

```text
tests/php/src/Internal/RestApi/Routes/V4/Settings/PaymentGateways/
└── PaymentGatewaysSettingsControllerTest.php
```

Run tests with:

```bash
pnpm --filter=@woocommerce/plugin-woocommerce test:unit:env -- --filter=PaymentGatewaysSettingsControllerTest
```
