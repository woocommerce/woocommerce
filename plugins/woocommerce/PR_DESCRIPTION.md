# Add REST API V4 Payment Gateways Endpoint

## Description

This PR adds a new REST API V4 endpoint for managing payment gateways, following the V4 architecture patterns with dependency injection and proper type hinting.

## Changes

- **Schema**: `PaymentGatewaySchema.php` - Defines the schema for payment gateway objects with proper documentation
- **Controller**: `Controller.php` - Handles GET collection, GET single, and UPDATE operations for payment gateways
- **Registration**: Updated `Server.php` to register the new payment gateways controller
- **Tests**: Comprehensive test coverage for all endpoints and edge cases
- **Documentation**: Updated README.md with route registration instructions

### API Endpoints

- `GET /wc/v4/payment-gateways` - List all payment gateways
- `GET /wc/v4/payment-gateways/{id}` - Get a single payment gateway
- `PUT /wc/v4/payment-gateways/{id}` - Update a payment gateway

## Testing Instructions

### 1. Generate API Credentials

1. Go to **WooCommerce > Settings > Advanced > REST API**
2. Click **Add key**
3. Set the following:
   - Description: `V4 Payment Gateways Test`
   - User: Select your admin user
   - Permissions: `Read/Write`
4. Click **Generate API key**
5. Copy the **Consumer key** and **Consumer secret** (you won't see them again)

### 2. Test GET All Payment Gateways

```bash
curl -u "CONSUMER_KEY:CONSUMER_SECRET" \
  http://localhost:8082/wp-json/wc/v4/payment-gateways
```

**Expected Result:**
- Status: `200 OK`
- Array of payment gateway objects
- Each gateway should include: `id`, `title`, `description`, `enabled`, `method_title`, `method_description`, `method_supports`, `settings`, `order`

### 3. Test GET Single Payment Gateway

```bash
curl -u "CONSUMER_KEY:CONSUMER_SECRET" \
  http://localhost:8082/wp-json/wc/v4/payment-gateways/cheque
```

**Expected Result:**
- Status: `200 OK`
- Single payment gateway object for the "Check payments" gateway
- `settings` object should contain configuration fields like `title`, `instructions`, etc.

### 4. Test GET COD Gateway (with multiselect fields)

```bash
curl -u "CONSUMER_KEY:CONSUMER_SECRET" \
  http://localhost:8082/wp-json/wc/v4/payment-gateways/cod
```

**Expected Result:**
- Status: `200 OK`
- COD gateway object
- `settings.enable_for_methods` should be present (type: `multiselect`)
- Note: `options` array may be empty due to performance optimization (only populated in admin settings context)

### 5. Test UPDATE Payment Gateway - Enable Gateway

```bash
curl -X PUT \
  -u "CONSUMER_KEY:CONSUMER_SECRET" \
  -H "Content-Type: application/json" \
  -d '{"enabled": true}' \
  http://localhost:8082/wp-json/wc/v4/payment-gateways/cheque
```

**Expected Result:**
- Status: `200 OK`
- Gateway object returned with `enabled: true`

### 6. Test UPDATE Payment Gateway - Update Settings

```bash
curl -X PUT \
  -u "CONSUMER_KEY:CONSUMER_SECRET" \
  -H "Content-Type: application/json" \
  -d '{
    "enabled": true,
    "title": "Pay by Check",
    "description": "Send us a check",
    "settings": {
      "title": "Check Payment",
      "instructions": "Please send your check to our office"
    }
  }' \
  http://localhost:8082/wp-json/wc/v4/payment-gateways/cheque
```

**Expected Result:**
- Status: `200 OK`
- Gateway returned with updated values:
  - `title: "Pay by Check"`
  - `description: "Send us a check"`
  - `settings.title.value: "Check Payment"`
  - `settings.instructions.value: "Please send your check to our office"`

### 7. Test UPDATE Payment Gateway - Update Order

```bash
curl -X PUT \
  -u "CONSUMER_KEY:CONSUMER_SECRET" \
  -H "Content-Type: application/json" \
  -d '{"order": 5}' \
  http://localhost:8082/wp-json/wc/v4/payment-gateways/cheque
```

**Expected Result:**
- Status: `200 OK`
- Gateway returned with `order: 5`

### 8. Test Error Cases

**Invalid Gateway ID:**
```bash
curl -u "CONSUMER_KEY:CONSUMER_SECRET" \
  http://localhost:8082/wp-json/wc/v4/payment-gateways/invalid_gateway
```
**Expected Result:** Status `404 Not Found`

**No Authentication:**
```bash
curl http://localhost:8082/wp-json/wc/v4/payment-gateways
```
**Expected Result:** Status `401 Unauthorized`

### 9. Run Automated Tests

```bash
# Run all payment gateways tests
pnpm run test:php:env -- --filter WC_REST_Payment_Gateways_V4_Controller_Tests

# Run specific test
pnpm run test:php:env -- --filter WC_REST_Payment_Gateways_V4_Controller_Tests::test_get_payment_gateways
```

**Expected Result:** All tests should pass

## Changelog

- Add - REST API V4 endpoint for payment gateways management
- Add - PaymentGatewaySchema class following V4 architecture
- Add - Payment gateways controller with GET and UPDATE operations
- Add - Comprehensive test coverage for payment gateways endpoint

## Notes

- The V4 endpoint follows the same data structure as V3 but uses the new V4 architecture with dependency injection
- Payment gateway settings are returned as dynamic objects where keys are setting IDs
- Some gateways (like COD) conditionally populate multiselect `options` arrays only in admin settings context for performance reasons
