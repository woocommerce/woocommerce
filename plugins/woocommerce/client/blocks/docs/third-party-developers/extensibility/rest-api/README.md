# Extending the Store API

Your application can change the way the Store API works by extending certain endpoints. It can add data to certain endpoints to make your server-side data available to the client-side.

You can also use the Store API trigger a server-side cart update from the client which will then update the client-side cart with the data returned by the API.

The documents listed below contain further details on how to achieve the above.

| Document                                                                              | Description                                                                        |
|---------------------------------------------------------------------------------------|------------------------------------------------------------------------------------|
| [Exposing your data in the Store API](./extend-rest-api-add-data.md)                  | Explains how you can add additional data to Store API endpoints.                   |
| [Available endpoints to extend with ExtendSchema](./available-endpoints-to-extend.md) | A list of all available endpoints to extend.                                       |
| [Available Formatters](./extend-rest-api-formatters.md)                               | Available `Formatters` to format data for use in the Store API.                    |
| [Updating the cart with the Store API](./extend-rest-api-update-cart.md)              | Update the server-side cart following an action from the front-end.                |

