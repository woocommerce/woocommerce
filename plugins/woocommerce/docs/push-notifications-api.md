# WooCommerce Push Notifications REST API

The Push Notifications REST API allows authenticated users to register and manage push notification tokens for receiving notifications from WooCommerce stores.

## Base URL

```
https://example.com/wp-json/wc-push-notifications/
```

## Authentication

All endpoints require authentication. Users must be logged in and have a valid WordPress session or use WordPress authentication methods (Application Passwords, OAuth, etc.).

**Requirements:**
- Valid WordPress user session
- Jetpack connection must be active on the store

## Endpoints

### 1. Register Push Token

Register a new push notification token or update an existing one.

**Endpoint:** `POST /wp-json/wc-push-notifications/push-tokens`

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `token` | string | Yes | The push notification token from the device |
| `platform` | string | Yes | Platform type: `ios`, `android`, or `browser` |
| `origin` | string | Yes | Application origin identifier |
| `device_uuid` | string | Conditional | Device UUID (required for iOS/Android, optional for browser) |

**Platform-specific Token Formats:**

- **iOS**: 64-character hexadecimal string (e.g., `a1b2c3d4...`)
- **Android**: Base64-encoded string, max 4096 characters
- **Browser**: JSON string containing:
  ```json
  {
    "endpoint": "https://...",
    "keys": {
      "auth": "base64-string",
      "p256dh": "base64-string"
    }
  }
  ```

**Valid Origin Values:**

- `com.woocommerce.android` - WooCommerce Android app (production)
- `com.woocommerce.android:dev` - WooCommerce Android app (development)
- `com.automattic.woocommerce` - WooCommerce iOS app (production)
- `com.automattic.woocommerce:dev` - WooCommerce iOS app (development)

#### iOS Example Request

```bash
curl -X POST https://example.com/wp-json/wc-push-notifications/push-tokens \
  -H "Content-Type: application/json" \
  -u username:password \
  -d '{
    "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2",
    "platform": "ios",
    "device_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "origin": "com.automattic.woocommerce"
  }'
```

#### Android Example Request

```bash
curl -X POST https://example.com/wp-json/wc-push-notifications/push-tokens \
  -H "Content-Type: application/json" \
  -u username:password \
  -d '{
    "token": "fGcI8xMc-Rk:APA91bH...",
    "platform": "android",
    "device_uuid": "550e8400-e29b-41d4-a716-446655440001",
    "origin": "com.woocommerce.android"
  }'
```

#### Browser Example Request

```bash
curl -X POST https://example.com/wp-json/wc-push-notifications/push-tokens \
  -H "Content-Type: application/json" \
  -u username:password \
  -d '{
    "token": "{\"endpoint\":\"https://fcm.googleapis.com/fcm/send/...\",\"keys\":{\"auth\":\"dGVzdA==\",\"p256dh\":\"dGVzdA==\"}}",
    "platform": "browser",
    "origin": "com.automattic.woocommerce"
  }'
```

#### Success Response

**HTTP Status:** `201 Created`

```json
{
  "id": 123
}
```

**Response Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | The unique ID of the push token record |

#### Error Responses

**401 Unauthorized** - User not authenticated or Jetpack not connected

```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": {
    "status": 401
  }
}
```

**400 Bad Request** - Invalid token format

```json
{
  "code": "rest_invalid_param",
  "message": "Invalid push token format.",
  "data": {
    "status": 400
  }
}
```

**400 Bad Request** - Missing device_uuid for iOS/Android

```json
{
  "code": "rest_invalid_param",
  "message": "Missing parameter(s): device_uuid.",
  "data": {
    "status": 400,
    "params": {
      "device_uuid": "Missing parameter(s): device_uuid."
    }
  }
}
```

**400 Bad Request** - Invalid platform

```json
{
  "code": "rest_invalid_param",
  "message": "Invalid parameter(s): platform",
  "data": {
    "status": 400,
    "params": {
      "platform": "platform is not one of ios, android, browser."
    }
  }
}
```

---

### 2. Delete Push Token

Remove a registered push notification token.

**Endpoint:** `DELETE /wp-json/wc-push-notifications/push-tokens/{id}`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The push token ID to delete |

#### Example Request

```bash
curl -X DELETE https://example.com/wp-json/wc-push-notifications/push-tokens/123 \
  -u username:password
```

#### Success Response

**HTTP Status:** `204 No Content`

```
(empty response body)
```

#### Error Responses

**401 Unauthorized** - User not authenticated

```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": {
    "status": 401
  }
}
```

**404 Not Found** - Token doesn't exist or doesn't belong to authenticated user

```json
{
  "code": "rest_invalid_push_token",
  "message": "Push token could not be found.",
  "data": {
    "status": 404
  }
}
```

**400 Bad Request** - Invalid token data

```json
{
  "code": "rest_invalid_argument",
  "message": "Can't delete push token because the push token data provided is invalid.",
  "data": {
    "status": 400
  }
}
```

---

## Token Behavior

### Upsert Logic

The registration endpoint uses "upsert" logic:
- If a token with the same `token` value OR same `device_uuid` already exists for the same user/platform/origin combination, it will be **updated**
- Otherwise, a new token record will be **created**

This ensures that:
- Each device only has one active token per platform/origin
- Token renewals automatically update the existing record
- Duplicate tokens are prevented

### Authorization Rules

1. **User Authentication**: All endpoints require a valid WordPress user session
2. **Jetpack Connection**: Push notifications are only available when Jetpack is connected
3. **Ownership Validation**: Users can only delete their own push tokens
4. **Privacy Protection**: Token existence is not revealed - users always receive 404 for tokens they don't own (prevents enumeration attacks)

---

## Error Codes Reference

| Error Code | HTTP Status | Description |
|------------|-------------|-------------|
| `rest_forbidden` | 401/403 | User not authenticated or not authorized |
| `rest_invalid_param` | 400 | Invalid parameter value or format |
| `rest_missing_callback_param` | 400 | Required parameter is missing |
| `rest_invalid_push_token` | 404 | Push token not found or access denied |
| `rest_invalid_argument` | 400 | Invalid data provided |
| `rest_internal_error` | 500 | Internal server error |

---

## Token Validation Rules

### iOS Token Validation
- Must be exactly 64 characters
- Must contain only hexadecimal characters (0-9, A-F, a-f)
- Example: `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2`

### Android Token Validation
- Must contain only alphanumeric characters and: `=`, `:`, `_`, `-`, `+`, `/`
- Maximum length: 4096 characters
- Example: `fGcI8xMc-Rk:APA91bHsK...`

### Browser Token Validation
- Must be valid JSON
- Must contain `endpoint` (HTTPS URL)
- Must contain `keys.auth` (base64 string)
- Must contain `keys.p256dh` (base64 string)
- Endpoint must use HTTPS protocol
- Example:
  ```json
  {
    "endpoint": "https://fcm.googleapis.com/fcm/send/abc123",
    "keys": {
      "auth": "dGVzdEF1dGg=",
      "p256dh": "dGVzdFAyNTY="
    }
  }
  ```

---

## Best Practices

1. **Token Registration Timing**
   - Register tokens after user login
   - Re-register when tokens are renewed by the OS
   - Delete tokens on user logout

2. **Error Handling**
   - Handle 401 errors by prompting user to re-authenticate
   - Handle 400 errors by validating token format before sending
   - Retry 500 errors with exponential backoff

3. **Security**
   - Always use HTTPS
   - Store tokens securely on the device
   - Never log or expose push tokens in client-side code
   - Clear tokens from device storage on logout

4. **Device UUID**
   - Use a consistent, device-specific identifier
   - iOS: Use `UIDevice.identifierForVendor`
   - Android: Use `Settings.Secure.ANDROID_ID` or Firebase Instance ID
   - Browser: Not required (can be omitted)

---

## Rate Limiting

Standard WordPress REST API rate limiting applies. Excessive requests may result in temporary blocks.

---

## Support

For issues or questions about the Push Notifications API:
- Check WooCommerce logs in WP Admin → WooCommerce → Status → Logs
- Ensure Jetpack is properly connected
- Verify user authentication is working correctly
