# Intro page

The logic for the Intro page is complex, and it varies based on multiple factors like:

- The active theme
- The status of the `Customize your store` tasks (completed or not)
- The flow type (AI flow or not)

## Core flow (no AI)

| Theme   | Task completed | Intro page                                                                                                                                                                                                                                   |
|---------|----------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| TT4     | No             | ![tt4-no](./readme/noai1.jpg)                                                                                                                                                                                                                |
| TT4     | Yes            | The "Customize your theme" and "Use the store designer" buttons go to the assembler. ![tt4-yes](./readme/noai2.png)                                                                                                                          |
| Block   | -              | The "Go to the Editor" button opens the Editor (wp-admin/site-editor.php). "Use the store designer" opens a modal to inform the user their theme will change if they accept and it will start the CYS flow. ![block](./readme/noai3.png)     |
| Classic | -              | The "Go to the Customizer" button opens the Customizer (wp-admin/customize.php). "Use the store designer" opens a modal to inform the user their theme will change if they accept and it will start the flow. ![classic](./readme/noai4.png) |

## AI flow

| AI generated theme | Task completed | Intro page                                                                                                   |
|--------------------|----------------|--------------------------------------------------------------------------------------------------------------|
| -                  | No             | ![task-completed](./readme/ai1.png)                                                                          |
| No                 | Yes            | The "Use the store designer" button shows a modal to warn about a theme switch. ![no-yes](./readme/ai2.png)  |
| Yes                | Yes            | The "Use the store designer" button shows a modal to warn about a theme switch. ![yes-yes](./readme/ai3.png) |

## Other

### Network offline

![offline](./readme/offline.png)
