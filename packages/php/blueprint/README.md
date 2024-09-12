<!-- markdownlint-disable MD029 -->
# Blueprint

This PHP Composer package facilitates exporting and importing WordPress Blueprint
compatible JSON formats. It offers a solid framework for seamless integration with
WordPress sites and supports extensibility, enabling plugins to customize export
and import functionalities. Manage site configurations, options, and settings
effortlessly with JSON files.

## Built-in Steps

| Step             |
|------------------|
| `installPlugin`  |
| `activatePlugin` |
| `deactivatePlugin` |
| `deletePlugin`   |
| `installTheme`   |
| `activateTheme`  |
| `setSiteOptions` |

## Hooks

| Hook                     | Description                     |
|--------------------------|---------------------------------|
| `wooblueprint_exporters` | A hook to add custom exporters. |
| `wooblueprint_importers` | A hook to add custom importers. |

## Example: Adding a Custom Exporter

1. Create a new class that extends `Automattic\WooCommerce\Blueprint\Exporters\StepExporter`.

```php
<?php

use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\Step;

class MyCustomExporter extends StepExporter {
    public function export( array $data ): Step {
       
    }
    
    public function get_step_name() {
        return 'setSiteOptions';
    }

}
```

2. The `export` method should return a `Step` object.
3. Let's use a built-in `SetSiteOptions` step for this example.
4. Create a new instance of `SetSiteOptions` and return it.

```php

use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\Step;

class MyCustomExporter extends StepExporter {
    public function export(): Step {
        $data = [
            'option1' => get_option( 'option1', 'value1' ),
            'option2' => get_option( 'option2', 'value2' ),
       ];
       return new SetSiteOptions( $data );
    }
    
    public function get_step_name() {
        return SetSiteOptions::get_step_name();
    }
}

```

5. Lastly, register the exporter with the Blueprint package via `wooblueprint_exporters`
filter.

```php
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\Step;

class MyCustomExporter extends StepExporter {
    public function export(): Step {
        $data = [
            'option1' => get_option( 'option1', 'value1' ),
            'option2' => get_option( 'option2', 'value2' ),
       ];
       return new SetSiteOptions( $data );
    }
    
    public function get_step_name() {
        return SetSiteOptions::get_step_name();
    }
}

add_filter( 'wooblueprint_exporters', function( array $exporters ) {
    $exporters[] = new MyCustomExporter();
    return $exporters;
} );

```

When exporting a Blueprint, the `MyCustomExporter` class will be called and the `SetSiteOptions`
step will be added to the Blueprint JSON.

Output:

  ```json
  {
      "steps": [
          {
              "name": "setSiteOptions",
              "options": {
                  "option1": "value1",
                  "option2": "value2"
              }
          }
      ]
  }
  ```

## Example: Adding a Custom Importer

## Example: Adding a Custom Step

## Example: Aliasing a Custom Exporter
