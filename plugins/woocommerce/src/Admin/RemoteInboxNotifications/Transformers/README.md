# Option Transformer

An option transformer is a class that transforms the given option value into a different value for the comparison operation.

Transformers run in the order they are defined. Each transformer passes down the value it transformed to the next transformer to consume.

Definition example:

```
  {
    "slug": "test-note",
     ...
    ],
    "rules": [
      {
        "type": "option",
        "transformers": [
            {
                "use": "dot_notation",
                "arguments": {
                    "path": "industry"
                }
            },
            {
                "use": "array_column",
                "arguments": {
                    "key": "slug"
                }
            }
        ],
        "option_name": "woocommerce_onboarding_profile",
        "operation": "!=",
        "value": "fashion-apparel-accessories",
        "default": []
      }
    ]
  }
```

### ArrayColumn (array_column)

This uses PHP's built-in `array_column` to select values by a given array key.

Arguments:

| name | description    |
| ---- | -------------- |
| key  | array key name |

Definition:

```php
"transformers": [
    {
        "use": "array_column",
        "arguments": {
            "key": "industry"
        }
    }
],
```

### ArrayFlatten (array_flatten)

This flattens a nested array.

Arguments: N/A

Definition:

```php
"transformers": [
    {
        "use": "array_flatten"
    }
],
```

### ArrayKeys (array_keys)

This uses PHP's built-in `array_keys` to return keys from an array.

Arguments: N/A

Definition:

```php
"transformers": [
    {
        "use": "array_keys"
    }
],
```

### ArraySearch (array_search)

This uses PHP's built-in `array_search` to search a value in an array.

Arguments:
|name|description|
|----|---------|
| value | a value to search in the given array |

Definition:

```php
"transformers": [
    {
        "use": "array_search",
        "arguments": {
            "value": "test"
        }
    }
],
```

### ArrayValues (array_values)

This uses PHP's built-in array_values to return values from an array.

Arguments: N/A

Definition:

```php
"transformers": [
    {
        "use": "array_values"
    }
],
```

### DotNotation (dot_notation)

This uses dot notation to select a value in an array. Dot notation lets you access an array as if it is an object.

Let's say we have the following array.

```php
$items = [
    'name' => 'name',
    'members' => ['member1', 'member2']
];
```

Example definition to select `$items['name']`:

```php
"transformers": [
    {
        "use": "dot_notation",
        "arguments": {
            "path": "name"
        }
    }
],
```

Example definition to select `$items['members'][0]`:

```php
"transformers": [
    {
        "use": "dot_notation",
        "arguments": {
            "path": "members.0"
        }
    }
],
```

### Count (count)

This uses PHP's built-in count to return the number of values from a countable, such as an array.

Arguments: N/A

Definition:

```php
"transformers": [
    {
        "use": "count"
    }
],
```
