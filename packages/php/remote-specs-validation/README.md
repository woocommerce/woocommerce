# Remote Specification Validation

## Available Schemas

| Filename  | Endpoint |
| ------------- | ------------- |
| remote-inbox-notification.json  | https://woocommerce.com/wp-json/wccom/inbox-notifications/1.0/notifications.json  |
| Content Cell  | Content Cell  |

# Working with Schema

If it's your first time working with JSON Schema, we highly recommend reading https://json-schema.org/learn/getting-started-step-by-step first. 

1. Open a schema file from `schemas` directory.
2. Make changes.
3. Run `./bin/build schemas/:name-of-schema-file`
4. Bundled schema file is saved in `bundles` directory.
