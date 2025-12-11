# Automated WooCommerce REST API documentation generation project


## The problem

Right now the WooCommerce REST API documentation is a static website that needs to be manually updated whenever there are changes to the REST API. This process tedious, time-consuming and error prone. Additionally, the current site (https://woocommerce.github.io/woocommerce-rest-api-docs/) is slow and often becomes irresponsive.


## The idea for a solution

Keep the WooCommerce REST API documentation as a static website, but implement a tool that generates this website automatically from the list of the existing endpoints and their schemas. The tool will be a local script/program, and the site will be generated statically and not included in source control.

Secondary goal: generate a comprehensive [OpenAI](https://learn.openapis.org/) schema that can be queried from a dedicated endpoint of the WooCommerce REST API.


## Structure of the generated website

Take a look at ./.ai/temp/GithubRestApiCommitsReference.mhtml - the goal is to have something similar to this in structure:

- Endpoints are grouped in categories, shown at the left sidebar, that are structured as a tree.
- Each endpoint has a short name and a brief description.
- Details are shown for each endpoint: verb(s), full route, input arguments (in the route, query string, and/or request HTTP headers), response schema.
- There's an example on how to query the endpoint using curl.

For the WooCommerce REST API in particular, the categories root can be the API version (v1 through v4), excluding the `/wp-json/wc/` part.

The visual look and feel of the generated site doesn't need to be identical to this example. You can also take a look at ~/Restplain, a local clone of a project that renders documentation for the WordPress REST API, for inspiration on the design.

The site must display the WooCommerce logo, ./ai/temp/woo_logo.png


## Where to get the information from?

Running a `GET` request to the `/wp-json` endpoint of a WordPress site will return a complere schema of the REST API for the site. You have an example in ./ai/temp/wp-rest-api-schema.json , this schema includes for each existing endpoint:

- Route
- Verb(s)
- Request arguments schema
- Response schema

WordPress REST API schema reference: ./.ai/temp/WordPressRestApiSchemaReference.mhtml

The following information is NOT included in the WordPress schema and we need to provide it ourselves:

- Category
- Name
- Description

These could be provided in _endpoint descriptors_, markdown files with a structure as in this example:

```
|          | |
|----------|-|
| category | v3/Products
| route    | /wc/v3/products/(?P<product_id>[\\d]+)/variations/(?P<id>[\\d]+)
| name     | Create or modify a product variation
| verb     | POST,PUT,PATCH
| ignore   | true

Creates a new product variation or modifies an existing product variation. *Note* that this text is _markdown_.
```

`ignore` is optional and when equal to `true` it signals that this file is to be ignored and not used for the REST API site generation. This can be useful for work-in-progress endpoint descriptors.


## Structure of the tool

Proposed filesystem structure:

```
docs/
├─ rest_api/
|  ├─ default_categories.md
│  ├─ bin
│  │  ├─ RestApiDocsSiteGenerator.php
│  ├─ endpoint_descriptors/
│  │  ├─ v3/
│  │  │  ├─ Products/
│  │  │  │  ├─ get__products_id.md
│  │  │  │  ├─ post_put_patch___products_productid_variations_id.md
│  ├─ temp
│  ├─ html
```

- `bin` is where the code for the site generator lives. In the example it's a single file but it can consist of multiple files.
- `endpoint_descriptors` contains the markdown files that provide the additional information for each endpoint. The suggested structure is a tree shaped as the categories, and then a `{verb(s)}_{route}` - like filename. This structure is intended to make things easier for human readers, the site generator will just scan all the existing files in this directory.
- `temp` is a working directory for the tool, it will be gitignored.
- `html` is where the static documentation site will be generated, it will be gitignored.
- `default_categories.md` is a list of categories to be assigned to routes by regular expression, as in this example:

```
|          | |
|----------|-|
| /wc/v2/products(/.*)? | v2/Products
| /wc/v3/products(/.*)? | v3/Products
```


## Technical details of the tool

The tool will be a PHP command line program. Since it's to be run locally it doesn't need to be constrained by the PHP version requirements of WooCommerce, it can require PHP 8.5.

The tool can be run directly (`php tool_path.php` in the command line) or via a dedicated pnpm command.

The tool must implement the following commands:

- Compare the list of existing endpoints (obtained by querying `/wp-json/` on a WordPress server) against the existing list of endpoint descriptors. List those that are missing or marked for ignore.
- As a complement to the above: generate the missing endpoint descriptors, using the standard `{verb(s)}_{route}` file naming and using `default_categories.md` to infer the category (if there are no matches, use a special "UNCATEGORIZED" category) and a placeholder name. These will have `ignore` set to `true`.
- Generate the static documentation website from the WordPress schema and the endpoint descriptor files.

The tool accepts an argument with the URL of the WordPress site to get the schema from, and it will store the result in `temp/rest-api-schema.json`. In further executions of the tool the URL can be omitted and the stored schema will be used if it exists (and if not, that's an error).


## OpenAPI

The tool should also have an option to generate an OpenAPI schema of the WooCommerce REST API. The schema will be stored in a json file (not reachable via regular HTTP requests), commited to source control, and served via a `/wp-json/wc/openapi` endpoint.

Additionally, a file containing a hash of the entire OpenAPI schema will be stored. This will be used as the value for an `ETag` header to be sent as part of the response; the `If-None-Match` request header/304 response semantics will be supported too.

For reference, ~/wp-openapi is a local clone of a project that generates OpenAPI schemas for the WordPress REST API.


## Task for the AI agent

ULTRATHINK. Analyze the feature design above. Is there anything missing? Something that can be improved/done differently? Ask me any questions you may have.
