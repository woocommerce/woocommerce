# WooCommerce Settings REST API - v4

> This is an internal API only. Do not use it in production.

The WooCommerce Settings REST API provides a consistent interface for managing various WooCommerce settings. All settings endpoints share a common response schema that organizes settings into logical groups with standardized field types.

## Response Schema

Each settings endpoint returns data in the following structure:

```json
{
  "id": "string",          // Unique identifier for the settings group
  "title": "string",       // Settings title
  "description": "string", // Settings description
  "values": {             // Flat key-value mapping of all setting field values
    "field_id": "value"   // Values can be string, number, array, boolean...
  },
  "groups": {             // Collection of setting groups
    "group_id": {
      "title": "string",       // Group title
      "description": "string", // Group description
      "order": 0,             // Display order for the group
      "fields": [             // Array of setting fields
        {
          "id": "string",     // Setting field ID
          "label": "string",  // Setting field label
          "type": "string",   // Field type (text, number, select, multiselect, checkbox)
          "options": {},      // Available options for select/multiselect fields
          "desc": "string"    // Field description
        }
      ]
    }
  }
}
```
