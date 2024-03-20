<!-- markdownlint-disable MD024 -->

# Option Transformer

An option transformer is a class that transforms the given option value into a different value for the comparison operation.

Transformers run in the order in which they are defined, and each transformer passes down the value it transformed to the next transformer for consumption.

**Definition example**: transformers are always used with `option` rule.

```js
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

## array_column

PHP's built-in `array_column` to select values from a single column. For more information about how array_column works, please see PHP's [official documentation](https://www.php.net/manual/en/function.array-column.php).

### Arguments

| name | description    |
| ---- | -------------- |
| key  | array key name |

### Definition

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

### Example

Given the following data

```php
array(
	array("industry" => "media" ),
	array("industry" => "software" )
);
```

Use `array_column` to extract `array("media", "software")` then choose the first element with `dot_notation`.

```php
"transformers": [
    {
        "use": "array_column",
        "arguments": {
            "key": "industry"
        }
    },
    {
    	"use": "dot_notation",
    	"arguments": {
    		"key": "0"
    	}
    }
],
```

**Output**: "media"





## array_flatten

Flattens a nested array.

### Arguments: N/A

### Definition

```php
"transformers": [
    {
        "use": "array_flatten"
    }
],
```

### Example

Given the following data

```php
array(
	array(
		'member1',
	),
	array(
		'member2',
	),
	array(
		'member3',
	),
);
```

Use `array_flatten` to extract `array("member1", "member2", "member3")` then use `array_search` to make sure it has `member2`


```php
"transformers": [
    {
        "use": "array_flatten",
    },
    {
        "use": "array_search",
        "arguments": {
            "key": "member2"
        }
    }
],
```

**Output**: true

## array_keys

PHP's built-in `array_keys` to return keys from an array. For more information about how `array_keys` works, please see PHP’s [official documentation](https://www.php.net/manual/en/function.array-column.php).

### Arguments: N/A

### Definition

```php
"transformers": [
    {
        "use": "array_keys"
    }
],
```

### Example

Given the following data

```php
array(
    "name" => "tester",
    "address" => "test",
    "supports_version_2" => true
)
```

Use `array_keys` to extract `array("name", "address", "supports_version_2")` and then use `array_search` to make sure it has `supports_version_2`

```php
"transformers": [
    {
        "use": "array_keys",
    },
    {
        "use": "array_search",
        "arguments": {
            "key": "member2"
        }
    }
],
```

**Output**: true

## array_search

PHP's built-in `array_search` to search a value in an array. For more information about how `array_search` works, please see PHP’s [official documentation](https://www.php.net/manual/en/function.array-search.php).

### Arguments

|name|description|
|----|---------|
| value | a value to search in the given array |

### Definition

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

### Examples

See examples from [array_flatten](#array_flatten) and [array_keys](#array_keys)

## array_values

PHP's built-in array_values to return values from an array. For more information about how `array_values` works, please see PHP’s [official documentation](https://www.php.net/manual/en/function.array-values).


### Arguments: N/A

### Definition

```php
"transformers": [
    {
        "use": "array_values"
    }
],
```

### Example

Given the following data

```php
array (
	"size" => "x-large"
)
```

Use `array_values` to extract `array("x-large")`

```php
"transformers": [
    {
        "use": "array_values",
    }
],
```

**Output:** "x-large"


## dot_notation

Uses dot notation to select a value in an array. Dot notation lets you access an array as if it is an object.

### Arguments: N/A

### Definition


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

### Example



Given the following data

```php
array(
    'name' => 'john',
    'members' => ['member1', 'member2']
);
```

Select `name` field.

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

**Output:** "john"

Select `member2`. You can access array items with an index.

```php
"transformers": [
    {
        "use": "dot_notation",
        "arguments": {
            "path": "members.1"
        }
    }
],
```

**Output:**: "member2"

## count

PHP's built-in count to return the number of values from a countable, such as an array.

### Arguments: N/A

### Definition

```php
"transformers": [
    {
        "use": "count"
    }
],
```

### Example

Given the following list of usernames

```php
array(
	"username1",
	"username2",
	"username3"
)
```

Let's count # of users with `count`

```php
"transformers": [
    {
        "use": "count",
    }
],
```

**Output:** 3

## prepare_url

This prepares the site URL by removing the protocol and the last slash.

### Arguments: N/A

### Definition

```php
"transformers": [
    {
        "use": "prepare_url"
    }
],
```

### Example

Given the following data

```php
$siteurl = "https://mysite.com/"
```

Removes the protocol and the last slash.

```php
"transformers": [
    {
        "use": "prepare_url",
    }
],
```

**Output:** "mysite.com"
