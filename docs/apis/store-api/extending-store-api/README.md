---
sidebar_label: Extending the Store API
---
# Extending the Store API

Your application can change the way the Store API works by extending certain endpoints. It can add data to certain endpoints to make your server-side data available to the client-side.

You can also use the Store API trigger a server-side cart update from the client which will then update the client-side cart with the data returned by the API.

The documents listed below contain further details on how to achieve the above.

| Document | Description |
|----------|-------------|
| [Exposing your data](./extend-store-api-add-data/) | Explains how you can add additional data to Store API endpoints. |
| [Available extensible endpoints](./available-endpoints-to-extend/) | A list of all available endpoints to extend. |
| [Available Formatters](./extend-store-api-formatters/) | Available `Formatters` to format data for use in the Store API. |
| [Updating the cart on-demand](./extend-store-api-update-cart/) | Update the server-side cart following an action from the front-end. |
| [Adding fields and passing values](./extend-store-api-add-custom-fields/) | How to add custom fields to Store API endpoints. | 
