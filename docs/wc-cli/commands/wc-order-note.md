---
title: wc order_note
sidebar_label: wc order_note
---

## wc order_note list `<order_id>`

- `--context` - Scope under which the request is made; determines fields present in response.
- `--type` - Limit result to customers or internal notes.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc order_note create `<order_id>`

- `--note` - Order note content. (*Required*)
- `--customer_note` - If true, the note will be shown to customers and they will be notified. If false, the note will be for admin reference only.
- `--porcelain` - Output just the id when the operation is successful.

## wc order_note get `<order_id>` `<id>`

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc order_note delete `<order_id>` `<id>`

- `--force` - Required to be true, as resource does not support trashing.
- `--porcelain` - Output just the id when the operation is successful.
