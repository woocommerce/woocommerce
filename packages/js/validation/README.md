# Validation

A package for validation and parsing of data using JSON Schema.

## Installation

Install the module.

```bash
pnpm install @woocommerce/validation --save
```

## Usage

The parser accepts a JSON schema as its first argument and the data being to validate against as its second argument.

The validator will always return parsed values, even when errors occur on the original input data.  This ensures that other consumers downstream of the data receive an expected type and don't need to be concerned with invalid data.

```js
const myValidator = validator();
const { parsed, errors } = myValidator.parse( schema, data );
```

### Error handling

The parser will always return an `errors` property as an array.  This will be an empty array when no errors exist on the input data.

```js
errors = [
    {
        code: 'missing_required';
        keyword: 'required';
        message: '/my-property is a required property';
        path: '/my-property';
    }
];
```

Each error contains information to help identify why the error was triggered and the exact property which contains the error.

* `code` - The error specific code to help identify the issue.
* `keyword` - The JSON Schema keyword which triggered the error.
* `message` - A readable message about the problem.  Note that this is intended for developers and not meant for direct usage within consumer facing areas of an application.
* `path` - The absolute path to the property within the tree as a JSON pointer.

### Custom filters

Filters allow code to be validated against a callback function.  This can be used to add custom keywords for all properties, trigger errors on specific properties, or provide errors around other specific context.

To add a filter, call `addFilter` on your validator.  Each time `parse` is called on your validator, the filter callback will run on each property.

```js
const myValidator = validator();
myValidator.addFilter( ( context ) => {
    const { path, schema } = context;
    if ( schema.type === 'string' && path !== '/my-property' || parsed !== 'bad-value' ) {
        return [
            {
                code: 'MY_ERROR_CODE',
                keyword: '',
                message: 'My property cannot be "bad-value"',
                path,
            }
        ];
    }
    return [];
} );
```

Each filter callback receives the `context` (parsed) as an argument and should return an array of errors or an empty array when no errors exist.

```js
context = {
    schema: {
        'type' => 'text',
        'minLength' => 5,
    },
    value: 2,
    parsed: '2',
    path: '/my-string-property',
    data: { ... }, // The root input data.
    filters: [],
}
```

## Supported Features

### Object

Objects types are accepted and will recursively evaluate any items under `properties`.  Any missing properties will trigger an error with the `missing_required` error code.

```json
{
    "type": "object",
    "properties": {
        "my-sub-property": {
            "type": "string"
        }
    },
    "required": [ "my-sub-property" ]
}
```

#### Keywords

* `required` - Accepts an array of required property keys

### String

String types will validate that the input is a string or will attempt to coerce numbers to strings.

```json
{
    "type": "string",
    "minLength": 5,
    "maxLength": 100,
    "format": "email",
}
```

#### Keywords

* `format` - The format the string should provide.  Currently accepts `email` and `uri`.
* `maxLength` - The maximum length for the string
* `minLength` - The minimum length for the string
* `pattern` - The regex pattern the string should match


### Comparing properties

The validator accepts the use of the `$data` property which is still [under proposal](https://github.com/json-schema-org/json-schema-spec/issues/51).

This property allows us to compare two properties by providing a JSON pointer to reference another property.

```json
{
    "type": "object",
    "properties": {
        "my-string": {
            "type": "string",
            "minLength": { "$data": "1/my-number" },
        },
        "my-number": {
            "type": "number",
        },
    }
}
```

The pointer itself is relative and comprised of a number which represents how many levels to move up from the current, followed by a path to the property.
