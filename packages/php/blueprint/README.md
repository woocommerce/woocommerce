<!-- markdownlint-disable MD029 -->
# Blueprint

This PHP Composer package facilitates exporting and importing WordPress Blueprint
compatible JSON formats. It offers a solid framework for seamless integration with
WordPress sites and supports extensibility, enabling plugins to customize export
and import functionalities. Manage site configurations, options, and settings
effortlessly with JSON files.

## Built-in Steps

Blueprint comes with several built-in steps for common site operations:

| Step             | Description                        |
|------------------|------------------------------------|
| `installPlugin`  | Install a WordPress plugin         |
| `activatePlugin` | Activate a WordPress plugin        |
| `installTheme`   | Install a WordPress theme          |
| `activateTheme`  | Activate a WordPress theme         |
| `setSiteOptions` | Set WordPress site options         |
| `runSql`         | Run custom SQL queries             |

## Extending Blueprint

You can extend Blueprint by adding custom exporters, importers, or steps. This allows you to support new data types or custom site logic.

### Hooks

| Hook                     | Description                                 |
|--------------------------|---------------------------------------------|
| `wooblueprint_exporters` | Add custom exporters to the export process  |
| `wooblueprint_importers` | Add custom importers to the import process  |

---

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

---

## Example: Adding a Custom Importer

1. To add a custom importer, implement the `StepProcessor` interface. Importers process step data during import.

```php
use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;

class MyCustomImporter implements StepProcessor {
    public function process($schema): StepProcessorResult {
        // Your import logic here
        return StepProcessorResult::success(SetSiteOptions::get_step_name());
    }
    public function get_step_class(): string {
        return SetSiteOptions::class;
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

---

## Example: Adding a Custom Step

To define a new step, extend the abstract `Step` class. Steps represent actions that can be exported/imported.

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

---

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

---

## Need More?

- See the `src/` directory for built-in exporters, importers, and steps.
- Review the interfaces: `StepExporter`, `StepProcessor`, and `Step` for more details.
- Built-in steps can be found in `src/Steps/`.
- For advanced usage, see the source code and tests for real-world examples.
