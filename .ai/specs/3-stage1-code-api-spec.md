# WooCommerce GraphQL API - Code API Specification (Stage 1)

## 1. Overview

The Code API is the **authoritative, manually maintained** layer from which the GraphQL API infrastructure is automatically generated. It follows the **command pattern**: each query or mutation is represented by a dedicated PHP class with a single `execute` method.

Key principles:

- **Code-first**: developers write PHP classes; the build script generates GraphQL infrastructure.
- **Convention over configuration**: directory placement and naming conventions determine behavior by default.
- **Attributes where conventions fall short**: PHP 8 attributes provide metadata that can't be inferred from the code structure itself.
- **GraphQL-agnostic**: the code API classes have zero knowledge of GraphQL. They are usable as a standalone code API even without the GraphQL infrastructure.

---

## 2. Directory Structure

```
src/Api/
├── Queries/              # Query command classes (one class = one GraphQL query)
├── Mutations/            # Mutation command classes (one class = one GraphQL mutation)
├── Types/                # Output DTO classes (GraphQL object types)
├── InputTypes/           # Input DTO classes (GraphQL input types)
├── Enums/                # PHP 8.1 backed enum classes (GraphQL enum types)
├── Interfaces/           # Traits defining reusable field sets (GraphQL interfaces)
├── Scalars/              # Custom scalar type definitions (e.g. DateTime)
└── Pagination/           # Built-in cursor connection types (Connection, Edge, PageInfo)
```

Namespace root: `Automattic\WooCommerce\Api`

Each sub-directory maps to a sub-namespace: `Automattic\WooCommerce\Api\Queries`, `Automattic\WooCommerce\Api\Types`, etc.

### Subdirectory nesting

Within each directory, deeper nesting is allowed for organizational purposes.
For example, `src/Api/Queries/Orders/GetOrder.php` is a valid query class in namespace
`Automattic\WooCommerce\Api\Queries\Orders`. The nesting does NOT affect the GraphQL
name (which is always derived from the class name alone).

---

## 3. Naming Conventions

### GraphQL names from class names

The default GraphQL name for any entity is derived from its PHP class name:

| PHP class name | Entity type | GraphQL name | Rule |
|---|---|---|---|
| `GetOrder` | Query | `getOrder` | PascalCase → camelCase |
| `ListOrders` | Query | `listOrders` | PascalCase → camelCase |
| `CreateOrder` | Mutation | `createOrder` | PascalCase → camelCase |
| `Order` | Object type | `Order` | Kept as-is (PascalCase) |
| `CreateOrderInput` | Input type | `CreateOrderInput` | Kept as-is |
| `OrderStatus` | Enum | `OrderStatus` | Kept as-is |
| `HasId` | Interface | `HasId` | Kept as-is |

The `#[Name('custom_name')]` attribute can be applied to any class to override the default name.

### Property and method names

PHP snake_case property/method names are converted to snake_case in GraphQL
(GraphQL has no casing convention, but snake_case is consistent with WooCommerce conventions).

| PHP | GraphQL |
|---|---|
| `$total_amount` | `total_amount` |
| `$date_created` | `date_created` |
| `function line_items(...)` | `line_items(...)` |

---

## 4. Queries

A query is a command class in `src/Api/Queries/` with a single public `execute` method that **does not modify server state**.

```php
namespace Automattic\WooCommerce\Api\Queries;

use Automattic\WooCommerce\Api\Types\Order;

#[Description('Retrieve a single order by its ID.')]
#[RequiredCapability('read_shop_orders')]
class GetOrder {
    public function execute(
        #[Description('The ID of the order to retrieve.')]
        int $id
    ): ?Order {
        // Implementation: load and return the order, or null if not found.
    }
}
```

### Rules

1. **One class = one query.** Each class has exactly one public method named `execute`.
2. **Class placement** in `src/Api/Queries/` is what makes it a query (convention). No attribute is needed to mark it as a query.
3. **`execute` method parameters** become the GraphQL query arguments. Each parameter must be typed with:
   - A primitive type: `int`, `float`, `string`, `bool`
   - An enum type from `src/Api/Enums/`
   - An input type from `src/Api/InputTypes/`
   - `array` with an `#[ArrayOf(SomeType::class)]` attribute
   - Any of the above as nullable (`?Type`)
   - Parameters with default values become optional GraphQL arguments.
4. **`execute` return type** must be:
   - An output type from `src/Api/Types/`
   - An interface type from `src/Api/Interfaces/`
   - A `Connection` (for paginated results, see section 11)
   - Any of the above as nullable (`?Type`)
   - `array` with an `#[ArrayOf]` attribute (for non-paginated lists)
5. **Dependencies** are injected via the constructor (resolved by the DI container). The `execute` method receives only the API arguments.

### Optional: requested fields hint

The `execute` method MAY accept a final optional parameter `?array $requested_fields = null`. When provided, it contains the list of fields the caller has requested, as a nested associative array (keys = field names, values = `null` for scalar fields or nested arrays for object fields). When `null`, all fields should be returned.

This parameter is **not** a GraphQL concept — it is a code API optimization hint. The build script detects it and, in the generated GraphQL resolver, passes the field selection from the incoming query.

```php
public function execute(
    int $id,
    ?array $requested_fields = null
): ?Order {
    $order = new Order();
    $order->id = $wc_order->get_id();
    $order->status = OrderStatus::from($wc_order->get_status());

    // Only load expensive data if requested
    if ($requested_fields === null || isset($requested_fields['line_items'])) {
        $order->line_items = $this->load_line_items($wc_order);
    }

    return $order;
}
```

---

## 5. Mutations

A mutation is a command class in `src/Api/Mutations/` with a single public `execute` method that **modifies server state**.

```php
namespace Automattic\WooCommerce\Api\Mutations;

use Automattic\WooCommerce\Api\InputTypes\CreateOrderInput;
use Automattic\WooCommerce\Api\Types\Order;

#[Description('Create a new order.')]
#[RequiredCapability('publish_shop_orders')]
class CreateOrder {
    public function execute(
        #[Description('Data for the new order.')]
        CreateOrderInput $input
    ): Order {
        // Implementation: create the order and return it.
    }
}
```

### Rules

All rules from section 4 (Queries) apply, with the only difference being:
- Class placement in `src/Api/Mutations/` makes it a mutation.
- Mutations typically accept a single input type argument (following GraphQL best practice), but multiple arguments (including primitives) are allowed.

---

## 6. Output Types (Object Types)

Output types are DTO classes in `src/Api/Types/` that represent the data returned by queries and mutations.

```php
namespace Automattic\WooCommerce\Api\Types;

use Automattic\WooCommerce\Api\Enums\OrderStatus;
use Automattic\WooCommerce\Api\Interfaces\HasId;

#[Description('Represents a WooCommerce order.')]
class Order {
    use HasId;

    #[Description('The total amount of the order.')]
    public float $total_amount;

    #[Description('The date the order was created, in ISO 8601 format.')]
    public string $date_created;

    #[Description('The current status of the order.')]
    public OrderStatus $status;

    #[Description('The customer who placed the order.')]
    public ?Customer $customer;

    #[Description('Coupons applied to this order.')]
    #[ArrayOf(AppliedCoupon::class)]
    public array $applied_coupons;
}
```

### Rules

1. Output types are **pure DTOs**: public properties, no constructor logic, no business logic (except methods for field arguments — see section 10).
2. **Properties** must be typed with:
   - A primitive type: `int`, `float`, `string`, `bool`
   - Another output type from `src/Api/Types/`
   - An enum from `src/Api/Enums/`
   - An interface from `src/Api/Interfaces/` (via `use` trait)
   - `array` with `#[ArrayOf(SomeType::class)]`
   - A `Connection` type (for paginated sub-collections)
   - Any of the above as nullable
3. **`use` clauses** (traits from `src/Api/Interfaces/`) add shared fields AND declare that the GraphQL type implements the corresponding GraphQL interface.
4. **`#[Description]`** is recommended on the class and on each property.

### Backing fields (underscore convention)

Properties whose name starts with `_` are **internal backing fields**. They are excluded from the GraphQL schema and are intended to hold raw data for use by field argument methods (see section 10).

```php
public float $_raw_total;  // NOT exposed in GraphQL
```

---

## 7. Input Types

Input types are DTO classes in `src/Api/InputTypes/` used as arguments for mutations (and occasionally queries).

```php
namespace Automattic\WooCommerce\Api\InputTypes;

use Automattic\WooCommerce\Api\Enums\OrderStatus;

#[Description('Data required to create a new order.')]
class CreateOrderInput {
    #[Description('The initial status of the order.')]
    public OrderStatus $status;

    #[Description('The customer ID.')]
    public int $customer_id;

    #[Description('Billing address.')]
    public AddressInput $billing;
}
```

### Rules

1. Input types are **pure DTOs**: public properties only, no methods, no constructor logic.
2. **Properties** must be typed with:
   - A primitive type: `int`, `float`, `string`, `bool`
   - Another input type from `src/Api/InputTypes/`
   - An enum from `src/Api/Enums/`
   - `array` with `#[ArrayOf(SomeType::class)]`
   - Any of the above as nullable (nullable = optional in GraphQL)
3. Properties with a default value become optional GraphQL input fields.
4. Interfaces (traits) can be used for shared field sets, but note that GraphQL input types cannot implement interfaces — the trait is purely for PHP code reuse.

---

## 8. Enums

Enums are PHP 8.1 backed enum classes in `src/Api/Enums/`.

```php
namespace Automattic\WooCommerce\Api\Enums;

#[Description('The status of an order.')]
enum OrderStatus: string {
    #[Description('Order is pending payment.')]
    case Pending = 'pending';

    #[Description('Order is being processed.')]
    case Processing = 'processing';

    #[Description('Order is on hold.')]
    case OnHold = 'on-hold';

    #[Description('Order has been completed.')]
    case Completed = 'completed';

    #[Description('Order has been cancelled.')]
    case Cancelled = 'cancelled';

    #[Description('Order has been refunded.')]
    case Refunded = 'refunded';

    #[Description('Order payment has failed.')]
    case Failed = 'failed';
}
```

### Rules

1. Enums **must** be backed enums (`enum Foo: string` or `enum Foo: int`).
2. The backed value is the serialization value in GraphQL.
3. The case name (PascalCase) becomes the GraphQL enum value name, converted to SCREAMING_SNAKE_CASE by the build script: `Pending` → `PENDING`, `OnHold` → `ON_HOLD`.
4. `#[Description]` is recommended on the enum class and each case.
5. No `EnumType` attribute is needed on properties that reference enums — the PHP type hint is sufficient.

---

## 9. Interfaces (Traits)

Interfaces are PHP traits in `src/Api/Interfaces/` that define reusable sets of fields.

```php
namespace Automattic\WooCommerce\Api\Interfaces;

#[Description('An entity with a unique identifier.')]
trait HasId {
    #[Description('The unique identifier.')]
    public int $id;
}
```

### Rules

1. Interfaces are **PHP traits** (because PHP interfaces cannot declare properties).
2. When an output type `use`s a trait from `src/Api/Interfaces/`, the corresponding GraphQL object type is declared as implementing the GraphQL interface.
3. Interface traits follow the same property rules as output types (section 6).
4. Trait properties can have `#[Description]` attributes.
5. Traits can `use` other traits (interface inheritance).
6. When an input type `use`s a trait, it gains the PHP properties but there is no GraphQL interface relationship (GraphQL input types don't support interfaces).

---

## 10. Field Arguments

Some output type fields need to accept arguments (e.g., `total_amount(decimals: 4)`). This is modeled via **public methods on the output type class**.

### Convention

- **Public properties** → simple GraphQL fields (no arguments).
- **Public methods** → GraphQL fields WITH arguments (method parameters = field arguments, return type = field type).
- Backing data for methods is stored in underscore-prefixed properties (`$_raw_total`).

### Example

```php
#[Description('Represents a WooCommerce order.')]
class Order {
    public int $id;

    // Backing field: stores raw data, excluded from GraphQL schema.
    public float $_raw_total_amount;

    // GraphQL field "total_amount" with a "decimals" argument.
    #[Description('The total amount of the order.')]
    public function total_amount(
        #[Description('Number of decimal places to round to.')]
        int $decimals = 2
    ): float {
        return round($this->_raw_total_amount, $decimals);
    }

    // Backing field for line items.
    /** @var LineItem[] */
    public array $_raw_line_items;

    // GraphQL field "line_items" with filtering arguments.
    #[Description('The line items in this order.')]
    #[ArrayOf(LineItem::class)]
    public function line_items(
        #[Description('Filter by line item type.')]
        ?LineItemType $type = null
    ): array {
        if ($type === null) {
            return $this->_raw_line_items;
        }
        return array_filter($this->_raw_line_items, fn($li) => $li->type === $type);
    }
}
```

### How the command populates the DTO

```php
class GetOrder {
    public function execute(int $id): ?Order {
        $wc_order = wc_get_order($id);
        if (!$wc_order) {
            return null;
        }

        $order = new Order();
        $order->id = $wc_order->get_id();
        $order->_raw_total_amount = (float) $wc_order->get_total();
        $order->_raw_line_items = $this->load_line_items($wc_order);
        return $order;
    }
}
```

### How the code API consumer uses it

```php
$order = $getOrder->execute(42);
echo $order->id;                       // simple property
echo $order->total_amount();           // default decimals (2)
echo $order->total_amount(decimals: 4); // explicit decimals
```

### Rules

1. Field argument methods must be **public**, **non-static**, and return a non-void type.
2. Method parameters become the field's GraphQL arguments (name, type, default value from signature).
3. All method parameters SHOULD have default values (making them optional) since in GraphQL a field can be queried without arguments.
4. The `#[ArrayOf]` attribute is required on methods that return `array`.
5. Field argument methods should be **pure functions of the DTO's data** (no external data access, no side effects). They perform transformations, filtering, or formatting on data already held by the DTO.
6. The build script distinguishes properties and methods:
   - Properties (excluding `_`-prefixed ones) → simple field definitions.
   - Methods → field definitions with argument specs and a resolver that calls the method.

---

## 11. Pagination (Cursor Connections)

Paginated collections use the **Relay-style cursor connections** pattern.

### Built-in types

These types live in `src/Api/Pagination/` and are provided by the framework:

```php
namespace Automattic\WooCommerce\Api\Pagination;

class Connection {
    /** @var Edge[] */
    public array $edges;

    /** @var object[] The raw nodes without cursor wrappers. */
    public array $nodes;

    public PageInfo $page_info;

    public int $total_count;
}

class Edge {
    public string $cursor;
    public object $node;
}

class PageInfo {
    public bool $has_next_page;
    public bool $has_previous_page;
    public ?string $start_cursor;
    public ?string $end_cursor;
}
```

### Usage in queries

```php
#[Description('List orders with cursor-based pagination.')]
#[RequiredCapability('read_shop_orders')]
class ListOrders {
    #[ConnectionOf(Order::class)]
    public function execute(
        #[Description('Return the first N results.')]
        ?int $first = null,
        #[Description('Return the last N results.')]
        ?int $last = null,
        #[Description('Return results after this cursor.')]
        ?string $after = null,
        #[Description('Return results before this cursor.')]
        ?string $before = null,
        #[Description('Filter by order status.')]
        ?OrderStatus $status = null
    ): Connection {
        // Implementation builds and returns a Connection object.
    }
}
```

### `#[ConnectionOf(Type::class)]` attribute

Applied to the `execute` method, this attribute tells the build script:
1. The return type is logically `Connection` but the node type is `Type`.
2. The build script generates `TypeConnection` and `TypeEdge` GraphQL types in the schema.
3. `$first`, `$last`, `$after`, `$before` parameters are recognized as standard pagination arguments.

### Usage in output types (sub-collections)

Output type properties can also be connections:

```php
class Order {
    #[Description('The line items in this order.')]
    #[ConnectionOf(LineItem::class)]
    public Connection $line_items;
}
```

### Cursor encoding

Cursors are opaque strings. The recommended implementation is base64-encoded identifiers
(e.g., `base64_encode("order:$id")`), but the encoding is an implementation detail
not exposed to API consumers.

---

## 12. Custom Scalar Types

Custom scalar types represent values serialized as primitives but with specific semantics.

Custom scalars are defined as classes in `src/Api/Scalars/`:

```php
namespace Automattic\WooCommerce\Api\Scalars;

#[Description('An ISO 8601 encoded date and time string.')]
class DateTime {
    /**
     * Serialize a PHP value to the scalar's transport format.
     */
    public static function serialize(mixed $value): string {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }
        return (string) $value;
    }

    /**
     * Parse a value received from a client (variable or literal).
     */
    public static function parse(string $value): \DateTimeImmutable {
        return new \DateTimeImmutable($value);
    }
}
```

### Usage

Reference custom scalars via the `#[ScalarType(DateTime::class)]` attribute on properties
or method return types whose PHP type is a primitive but whose GraphQL type should be the custom scalar:

```php
class Order {
    #[Description('The date the order was created.')]
    #[ScalarType(DateTime::class)]
    public string $date_created;
}
```

### Rules

1. Custom scalar classes must have static `serialize` and `parse` methods.
2. The `#[ScalarType]` attribute is needed because the PHP type (`string`) differs from the GraphQL type (`DateTime`).
3. The build script generates the corresponding `GraphQL\Type\Definition\CustomScalarType` in the schema.

---

## 13. Authentication & Authorization

### Mechanism

Authentication leverages WordPress's built-in mechanisms (cookies, application passwords, etc.).
Authorization uses WordPress capabilities via the `#[RequiredCapability]` attribute.

### `#[RequiredCapability('capability_name')]`

Applied to query/mutation classes to restrict access:

```php
#[RequiredCapability('manage_woocommerce')]
class GetShopSettings {
    public function execute(): ShopSettings { ... }
}
```

### Rules

1. **Every query and mutation class MUST have a `#[RequiredCapability]` attribute** (no anonymous access by default).
2. If a query should be publicly accessible, use `#[PublicAccess]` instead.
3. When the current user lacks the required capability, the generated GraphQL resolver returns a standard GraphQL error with the message "You do not have permission to perform this action." and an `UNAUTHORIZED` error code in the extensions.
4. The capability check happens before the `execute` method is called.
5. Multiple capabilities can be specified: `#[RequiredCapability('read_shop_orders', 'edit_shop_orders')]` — the user must have ALL listed capabilities.

---

## 14. Error Handling

### ApiException

A dedicated exception class for errors that should be surfaced to the API client:

```php
namespace Automattic\WooCommerce\Api;

class ApiException extends \RuntimeException {
    private string $error_code;
    private array $extensions;

    public function __construct(
        string $message,
        string $error_code = 'INTERNAL_ERROR',
        array $extensions = [],
        int $http_status = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $http_status, $previous);
        $this->error_code = $error_code;
        $this->extensions = $extensions;
    }

    public function getErrorCode(): string { return $this->error_code; }
    public function getExtensions(): array { return $this->extensions; }
}
```

### Behavior

| Exception type | Client receives |
|---|---|
| `ApiException` | The exception message + error code + extensions |
| `\InvalidArgumentException` | The exception message + `INVALID_ARGUMENT` code |
| Any other exception | Generic "An unexpected error occurred." + `INTERNAL_ERROR` code |

In all cases, the error follows the GraphQL errors specification format:

```json
{
  "errors": [
    {
      "message": "Order not found.",
      "extensions": {
        "code": "NOT_FOUND",
        "order_id": 999
      }
    }
  ]
}
```

### Debug mode

When `WP_DEBUG` is enabled and the current user is an administrator, all errors include
the full exception message and a stack trace in the `extensions.debug` field.

---

## 15. Dependency Injection

Command classes (queries and mutations) are resolved via the WooCommerce DI container.
Dependencies are injected through the constructor.

```php
class GetOrder {
    private OrderRepository $order_repo;

    public function __construct(OrderRepository $order_repo) {
        $this->order_repo = $order_repo;
    }

    public function execute(int $id): ?Order {
        return $this->order_repo->find($id);
    }
}
```

The build script generates a service provider that registers all query and mutation
classes in the DI container. The classes themselves don't need manual registration.

---

## 16. Attributes Reference

All attributes live in `Automattic\WooCommerce\Api\Attributes` namespace. They are
consumed at build time by the code generation script.

| Attribute | Applies to | Purpose |
|---|---|---|
| `#[Description('...')]` | Classes, properties, methods, method params, enum cases | Human-readable description for GraphQL schema |
| `#[Name('...')]` | Classes | Override the default GraphQL name |
| `#[RequiredCapability('...')]` | Query/Mutation classes | WordPress capability required to execute |
| `#[PublicAccess]` | Query/Mutation classes | Allow unauthenticated access (mutually exclusive with `RequiredCapability`) |
| `#[ArrayOf(Type::class)]` | Properties, methods | Specify the element type for `array` typed values |
| `#[ConnectionOf(Type::class)]` | Methods, properties | Declare a cursor connection with the specified node type |
| `#[ScalarType(Type::class)]` | Properties | Map a primitive PHP type to a custom GraphQL scalar |
| `#[Deprecated('reason')]` | Properties, methods, enum cases | Mark as deprecated in the GraphQL schema |

### Attribute location

Attributes are defined in `src/Api/Attributes/`. Although they are consumed only at
build time, they live alongside the code API (not in `Internal/`) because:
- They are part of the public API surface (extensions may use them).
- They must be available at runtime for the PHP parser not to error out.

---

## 17. Build Script Behavior

The build script lives in `src/Internal/Api/DesignTime/Scripts/` and is **not included
in the WooCommerce build package**.

### Input

All PHP classes in `src/Api/` (recursively).

### Process

1. **Scan** all classes using PHP reflection.
2. **Classify** each class by its directory: Queries, Mutations, Types, InputTypes, Enums, Interfaces, Scalars, Pagination.
3. **Validate** all rules (type constraints, required attributes, naming).
4. **Generate** a single `GraphQLSchema.php` file in `src/Internal/Api/Autogenerated/` that:
   - Defines all GraphQL types (object types, input types, enums, interfaces, scalars, connection types).
   - Defines the root `Query` type with all query fields and their resolvers.
   - Defines the root `Mutation` type with all mutation fields and their resolvers.
   - Each resolver: checks capabilities → resolves the command via DI container → calls `execute` → returns the result.
5. **Generate** an `ApiServiceProvider.php` that registers all command classes in the DI container.
6. **Write** a `api_generation_date.txt` timestamp file.

### Output

```
src/Internal/Api/Autogenerated/
├── GraphQLSchema.php        # Complete schema definition
├── ApiServiceProvider.php   # DI container registrations
├── GraphQLController.php    # WordPress REST endpoint for GraphQL queries
└── api_generation_date.txt  # Build timestamp
```

### Running the build

```sh
pnpm run build:api
```

### Staleness check

The WooCommerce build script (`pnpm run build:zip`) checks if any file in `src/Api/`
is newer than `api_generation_date.txt`. If so, it aborts with a message instructing
the developer to run `pnpm run build:api` first.

---

## 18. GraphQL Endpoint

The generated `GraphQLController.php` registers a single WordPress REST route:

```
POST /wp-json/wc/v4/graphql
```

Request body:
```json
{
  "query": "query { getOrder(id: 42) { id total_amount status } }",
  "variables": { }
}
```

The controller:
1. Parses the GraphQL query.
2. Executes it against the generated schema (which handles capability checks, DI resolution, and method invocation).
3. Returns the result in standard GraphQL response format.

---

## 19. Complete Example

### Enum

```php
// src/Api/Enums/WebhookStatus.php

namespace Automattic\WooCommerce\Api\Enums;

#[Description('The status of a webhook.')]
enum WebhookStatus: string {
    case Active = 'active';
    case Paused = 'paused';
    case Disabled = 'disabled';
}
```

### Interface

```php
// src/Api/Interfaces/WebhookDefinition.php

namespace Automattic\WooCommerce\Api\Interfaces;

use Automattic\WooCommerce\Api\Enums\WebhookStatus;

#[Description('The static definition fields of a webhook.')]
trait WebhookDefinition {
    #[Description('Display name of the webhook.')]
    public string $name;

    #[Description('The topic of the webhook.')]
    public string $topic;

    #[Description('The URL where the webhook payload will be delivered.')]
    public string $delivery_url;

    #[Description('Optional secret for the webhook payload.')]
    public ?string $secret;
}
```

### Output type

```php
// src/Api/Types/Webhook.php

namespace Automattic\WooCommerce\Api\Types;

use Automattic\WooCommerce\Api\Enums\WebhookStatus;
use Automattic\WooCommerce\Api\Interfaces\HasId;
use Automattic\WooCommerce\Api\Interfaces\WebhookDefinition;

#[Description('Represents a WooCommerce webhook.')]
class Webhook {
    use HasId;
    use WebhookDefinition;

    #[Description('The current status of the webhook.')]
    public WebhookStatus $status;

    #[Description('Number of times delivery has failed.')]
    public int $failure_count;

    #[Description('Whether a delivery is pending.')]
    public bool $pending_delivery;

    #[Description('The date the webhook was created.')]
    #[ScalarType(DateTime::class)]
    public string $date_created;
}
```

### Input type

```php
// src/Api/InputTypes/CreateWebhookInput.php

namespace Automattic\WooCommerce\Api\InputTypes;

use Automattic\WooCommerce\Api\Enums\WebhookStatus;
use Automattic\WooCommerce\Api\Interfaces\WebhookDefinition;

#[Description('Data required to create a new webhook.')]
class CreateWebhookInput {
    use WebhookDefinition;

    #[Description('ID of the user who owns the webhook.')]
    public int $user_id;

    #[Description('Initial status of the webhook.')]
    public WebhookStatus $status;
}
```

### Query

```php
// src/Api/Queries/GetWebhook.php

namespace Automattic\WooCommerce\Api\Queries;

use Automattic\WooCommerce\Api\Types\Webhook;

#[Description('Retrieve a single webhook by its ID.')]
#[RequiredCapability('manage_woocommerce')]
class GetWebhook {
    public function execute(
        #[Description('The ID of the webhook.')]
        int $id
    ): ?Webhook {
        $core = wc_get_webhook($id);
        if (!$core) {
            return null;
        }

        $w = new Webhook();
        $w->id = $core->get_id();
        $w->name = $core->get_name();
        $w->topic = $core->get_topic();
        $w->delivery_url = $core->get_delivery_url();
        $w->secret = $core->get_secret();
        $w->status = WebhookStatus::from($core->get_status());
        $w->failure_count = $core->get_failure_count();
        $w->pending_delivery = $core->get_pending_delivery();
        $w->date_created = $core->get_date_created()->format(\DateTimeInterface::ATOM);
        return $w;
    }
}
```

### Paginated query

```php
// src/Api/Queries/ListWebhooks.php

namespace Automattic\WooCommerce\Api\Queries;

use Automattic\WooCommerce\Api\Enums\WebhookStatus;
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Api\Types\Webhook;

#[Description('List all webhooks with optional status filter and cursor pagination.')]
#[RequiredCapability('manage_woocommerce')]
class ListWebhooks {
    #[ConnectionOf(Webhook::class)]
    public function execute(
        ?int $first = null,
        ?int $last = null,
        ?string $after = null,
        ?string $before = null,
        #[Description('Filter by webhook status.')]
        ?WebhookStatus $status = null
    ): Connection {
        // Build and return a Connection with edges, nodes, and page_info.
    }
}
```

### Mutation

```php
// src/Api/Mutations/CreateWebhook.php

namespace Automattic\WooCommerce\Api\Mutations;

use Automattic\WooCommerce\Api\InputTypes\CreateWebhookInput;
use Automattic\WooCommerce\Api\Types\Webhook;

#[Description('Create a new webhook.')]
#[RequiredCapability('manage_woocommerce')]
class CreateWebhook {
    public function execute(
        #[Description('The webhook data.')]
        CreateWebhookInput $input
    ): Webhook {
        $core = new \WC_Webhook();
        $core->set_name($input->name);
        $core->set_topic($input->topic);
        $core->set_delivery_url($input->delivery_url);
        $core->set_secret($input->secret);
        $core->set_status($input->status->value);
        $core->set_user_id($input->user_id);
        $core->set_date_created(gmdate('Y-m-d H:i:s'));
        $core->save();

        // Reuse the query to return the created webhook.
        return wc_get_container()->get(GetWebhook::class)->execute($core->get_id());
    }
}
```

### Resulting GraphQL schema (generated)

```graphql
type Query {
    getWebhook(id: Int!): Webhook
    listWebhooks(
        first: Int
        last: Int
        after: String
        before: String
        status: WebhookStatus
    ): WebhookConnection!
}

type Mutation {
    createWebhook(input: CreateWebhookInput!): Webhook!
}

interface HasId {
    id: Int!
}

interface WebhookDefinition {
    name: String!
    topic: String!
    delivery_url: String!
    secret: String
}

type Webhook implements HasId & WebhookDefinition {
    id: Int!
    name: String!
    topic: String!
    delivery_url: String!
    secret: String
    status: WebhookStatus!
    failure_count: Int!
    pending_delivery: Boolean!
    date_created: DateTime!
}

type WebhookConnection {
    edges: [WebhookEdge!]!
    nodes: [Webhook!]!
    page_info: PageInfo!
    total_count: Int!
}

type WebhookEdge {
    cursor: String!
    node: Webhook!
}

type PageInfo {
    has_next_page: Boolean!
    has_previous_page: Boolean!
    start_cursor: String
    end_cursor: String
}

enum WebhookStatus {
    ACTIVE
    PAUSED
    DISABLED
}

input CreateWebhookInput {
    name: String!
    topic: String!
    delivery_url: String!
    secret: String
    user_id: Int!
    status: WebhookStatus!
}

scalar DateTime
```

### Example GraphQL queries

```graphql
# Get a single webhook
query {
    getWebhook(id: 1) {
        id
        name
        status
        date_created
    }
}

# List active webhooks
query {
    listWebhooks(first: 10, status: ACTIVE) {
        edges {
            cursor
            node {
                id
                name
                delivery_url
            }
        }
        page_info {
            has_next_page
            end_cursor
        }
        total_count
    }
}

# Create a webhook
mutation {
    createWebhook(input: {
        name: "Order created hook"
        topic: "order.created"
        delivery_url: "https://example.com/webhook"
        secret: "my-secret"
        user_id: 1
        status: ACTIVE
    }) {
        id
        name
        status
    }
}
```
