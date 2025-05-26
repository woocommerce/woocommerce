<!-- markdownlint-disable MD029 -->
# Blueprint

This PHP Composer package facilitates exporting and importing WordPress Blueprint compatible JSON formats. It offers a solid framework for seamless integration with WordPress sites and supports extensibility, enabling plugins to customize export and import functionalities. Manage site configurations, options, and settings effortlessly with JSON files.

## Usage

Blueprint lets you export your WordPress site configuration to a JSON file and import it into another site. This can be done via WP-CLI or directly in PHP for advanced automation or integration.

### Exporting a Blueprint

You can export a site configuration using the `ExportSchema` class:

```php
use Automattic\WooCommerce\Blueprint\ExportSchema;

// Optionally pass custom exporters, or leave empty for built-in exporters.
$export_schema = new ExportSchema();

// Export all steps:
$schema = $export_schema->export();

// Export only specific steps:
// $schema = $export_schema->export(['installPlugin', 'activateTheme']);

// Save to file:
file_put_contents('blueprint.json', json_encode($schema, JSON_PRETTY_PRINT));
```

### Importing a Blueprint

You can import a previously exported JSON file using the `ImportSchema` class:

```php
use Automattic\WooCommerce\Blueprint\ImportSchema;

// Load the JSON file:
$import_schema = ImportSchema::create_from_file('blueprint.json');

// Run the import:
$results = $import_schema->import();

// $results is an array of StepProcessorResult objects for each step.
```

### Importing a Single Step (Advanced)

To import a single step from a JSON definition, use the `ImportStep` class:

```php
use Automattic\WooCommerce\Blueprint\ImportStep;

$step_definition = json_decode(
  '{"step":"setSiteOptions","options":{"option1":"value1"}}'
);
$import_step = new ImportStep($step_definition);
$result = $import_step->import();
```

## Data Format

A Blueprint JSON file contains all the information needed to configure a WordPress or WooCommerce site. The format is fully compatible with [WordPress Blueprint data format](https://wordpress.github.io/wordpress-playground/blueprints/data-format/).

The following is an example of a Blueprint JSON file:

```json
{
  "landingPage": "/wp-admin/admin.php?page=wc-admin",
  "steps": [
    {
      "step": "setSiteOptions",
      "options": {
        "woocommerce_store_address": "123 Main St",
        "woocommerce_store_address_2": "Suite 100",
        "woocommerce_store_city": "Sample City",
        "woocommerce_default_country": "US:CA",
        "woocommerce_store_postcode": "90001",
        "woocommerce_all_except_countries": [],
        "woocommerce_specific_allowed_countries": [],
        "woocommerce_specific_ship_to_countries": [],
        "woocommerce_calc_taxes": "yes"
      }
    }
  ]
}
```

You can include as many steps as needed, each representing a different part of your WooCommerce or WordPress configuration. This is the format you get when exporting, and what you provide when importing a Blueprint.


## Built-in Steps

A **step** is a single, self-contained action or operation that can be exported to or imported from a Blueprint JSON file. Steps are the building blocks of the Blueprint export/import process.

Blueprint comes with several built-in steps for common site operations:

| Step             | Description                        |
|------------------|------------------------------------|
| `installPlugin`  | Install a WordPress plugin         |
| `activatePlugin` | Activate a WordPress plugin        |
| `installTheme`   | Install a WordPress theme          |
| `activateTheme`  | Activate a WordPress theme         |
| `setSiteOptions` | Set WordPress site options         |
| `runSql`         | Run custom SQL queries             |


### Example: SetSiteOptions Step

*PHP (creating a step):*

```php
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;

$step = new SetSiteOptions([
    'option1' => 'value1',
    'option2' => 'value2',
]);
```

*JSON (as exported):*

```json
{
  "step": "setSiteOptions",
  "options": {
    "option1": "value1",
    "option2": "value2"
  }
}
```

## Extending Blueprint

You can extend Blueprint by adding custom exporters, importers, or steps. This allows you to support new data types or custom site logic. For example, you can add a custom exporter to export your plugin configuration.

### Hooks

| Hook                     | Description                                 |
|--------------------------|---------------------------------------------|
| `wooblueprint_exporters` | Add custom exporters to the export process  |
| `wooblueprint_importers` | Add custom importers to the import process  |

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

## Example: Aliasing a Custom Exporter

If you have multiple exporters for the same step type, implement the `HasAlias` interface to give each exporter a unique alias. This helps distinguish them in the export UI or API.

```php
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;

class ProfilerOptionsExporter implements StepExporter, HasAlias {
    public function export() {
        // ...
    }
    public function get_step_name() {
        return SetSiteOptions::get_step_name();
    }
    public function check_step_capabilities(): bool {
        return current_user_can('manage_options');
    }
    public function get_alias() {
        return 'profilerOptions';
    }
}
```

You can now use the alias to export the step:

```bash
wp wc blueprint export wc-blueprint.json --steps=profilerOptions
```

## Example: Adding a Custom Importer

In most cases, the default importers will be sufficient. However, if you need to import data not supported by the default importers, creating a custom importer might be necessary. Keep in mind that this could result in a blueprint file that is not compatible with the standard WordPress Blueprint format.

1. To add a custom importer, you need to define a new step first. Extend the abstract `Step` class. Steps represent actions that can be exported/imported.

```php
use Automattic\WooCommerce\Blueprint\Steps\Step;

class MyCustomStep extends Step {
    private $my_data;
    public function __construct($my_data) {
        $this->my_data = $my_data;
    }
    public static function get_step_name(): string {
        return 'myCustomStep';
    }
    public static function get_schema(int $version = 1): array {
        return [
            'type' => 'object',
            'properties' => [
                'step' => [ 'type' => 'string', 'enum' => [static::get_step_name()] ],
                'myData' => [ 'type' => 'string' ],
            ],
            'required' => ['step', 'myData'],
        ];
    }
    public function prepare_json_array(): array {
        return [
            'step' => static::get_step_name(),
            'myData' => $this->my_data,
        ];
    }
}
```

2. Then, you need to implement the `StepProcessor` interface. Importers process step data during import.

```php
use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;

class MyCustomImporter implements StepProcessor {
    public function process($schema): StepProcessorResult {
        // Your import logic here
        return StepProcessorResult::success(MyCustomStep::get_step_name());
    }
    public function get_step_class(): string {
        return MyCustomStep::class;
    }
    public function check_step_capabilities($schema): bool {
        return current_user_can('manage_options');
    }
}
```

2. Register your importer using the `wooblueprint_importers` filter:

```php
add_filter('wooblueprint_importers', function(array $importers) {
    $importers[] = new MyCustomImporter();
    return $importers;
});
```

## Need More?

- See the `src/` directory for built-in exporters, importers, and steps.
- Review the interfaces: `StepExporter`, `StepProcessor`, and `Step` for more details.
- Built-in steps can be found in `src/Steps/`.
- For advanced usage, see the source code and tests for real-world examples.
