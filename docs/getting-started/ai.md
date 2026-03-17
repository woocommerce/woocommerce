---
post_title: AI
sidebar_label: AI
sidebar_position: 3
---

# AI

This guide provides an overview of AI tools and how to use them to enhance your WooCommerce development workflows.

## MCP

WooCommerce includes native support for the Model Context Protocol (MCP), enabling AI assistants and tools to interact directly with WooCommerce stores through a standardized protocol. Visit our full [MCP documentation](/docs/features/mcp/) for more information.

## AI Documentation Tools

### LLMS.txt Files

To feed the WooCommerce Developer Documentation into your LLM or AI-assisted IDE, you have two options:

1. [`llms.txt`](https://developer.woocommerce.com/docs/llms.txt) - A table of contents that includes the title, URL, and description of each document in the developer docs.
2. [`llms-full.txt`](https://developer.woocommerce.com/docs/llms-full.txt) - A full Markdown-formatted export of the entire documentation in one file.

If you are using an IDE like Cursor or Windsurf, we recommend adding these links as custom documentation so that you can reference them as needed.

**Note** that these do not include the contents of the [WC REST API documentation](https://woocommerce.github.io/woocommerce-rest-api-docs/#introduction) or the [WooCommerce Code Reference](https://woocommerce.github.io/code-reference/).

### Serve as Markdown

If you want to view the documentation as Markdown files, you can use append `.md` to the end of any documentation URL. For example, to view this page as Markdown, you can visit:   

```plain
https://developer.woocommerce.com/docs/getting-started/ai.md
``` 

### Copy to Markdown

On every page of the Developer Docs, you'll see a Clipboard icon in the upper-right-hand corner. Selecting this icon will copy the current doc in Markdown formatting, which you can paste into your LLM's chat interface.

## AI Development Tools

### Agent Skills for Contributors

The WooCommerce monorepo includes agent skills that provide AI assistants with procedural guidance for common development tasks. These skills are located in the `.ai/skills/` directory at the root of the repository.

Each skill contains a `SKILL.md` file with step-by-step instructions for tasks like:

- Backend PHP development and testing conventions
- Code review standards and best practices
- Git workflows and commit conventions
- Build and linting processes
- UI copy and documentation guidelines

Skills are designed to be tool-agnostic and can be used with various AI coding assistants. To explore available skills, browse the [`.ai/skills/` directory](https://github.com/woocommerce/woocommerce/tree/trunk/.ai/skills) in the repository.

### Cursor Rules files for contributors

The `.cursor/rules/` directory contains configuration files that provide AI assistants with specific guidance for working with the WooCommerce codebase. These files help ensure consistent development practices and workflows.
