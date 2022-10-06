FormSection
===

A layout wrapper to help align form title, description, and fields.

## Usage

```jsx
<FormSection
    title="My form section"
    description="Some text to describe what this section covers"
>
    <Card>
        <CardBody>
            My form fields
        </CardBody>
    </Card>
</FormSection>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`children` | JSX.Element | JSX.Element[] | `undefined` | The children to be rendered content area of the form section.
`description` | String | `undefined` | The description to be used beneath the section title.
`title` | String | `undefined` | The title of the form section.
