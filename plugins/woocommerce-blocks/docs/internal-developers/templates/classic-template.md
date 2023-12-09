# ClassicTemplate.php <!-- omit in toc -->

The `ClassicTemplate` is a class used to set up the Classic Template block on the server-side, and render the correct template.

## Overview

From this file, we enqueue the front-end scripts necessary to enable dynamic functionality, such as the product gallery, add to basket etc.

From the `render()` method we inspect the `$attributes` object for a `template` property which helps determine which core PHP templating code to execute (e.g. `single-product`) for the front-end views.

![Classic Block Template Attribute](./assets/classic-template-attributes.png)
