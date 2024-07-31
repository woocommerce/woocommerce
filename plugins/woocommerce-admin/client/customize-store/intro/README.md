# Intro page
The logic for the Intro page is complex, and it varies based on multiple factors like:
- The active theme
- The status of the `Customize your store` tasks (completed or not)
- The flow type (AI flow or not)

# Core flow (no AI)
| Theme | Task completed | Intro page |
|--------|--------|--------|
| TT4 | No | <img width="610" src="https://github.com/user-attachments/assets/a5370b5a-621e-44c9-8e78-6aa65524d6f8"> |
| TT4 | Yes | The "Customize your theme" and "Use the store designer" buttons go to the assembler. <img width="598" src="https://github.com/user-attachments/assets/394940ce-4fcd-4317-8809-ed8b3f04e961">  |
| Block | - | The "Go to the Editor" button opens the Editor (wp-admin/site-editor.php). "Use the store designer" opens a modal to inform the user their theme will change if they accept and it will start the CYS flow. <img width="605" src="https://github.com/user-attachments/assets/17233736-545c-44b4-8c2a-755386462f97"> |
| Classic | - | The "Go to the Customizer" button opens the Customizer (wp-admin/customize.php). "Use the store designer" opens a modal to inform the user their theme will change if they accept and it will start the flow. <img width="605" src="https://github.com/user-attachments/assets/c3b15bab-682f-4ff7-8c38-c7873f40953a">  |

## AI flow
| AI generated theme | Task completed | Intro page |
|--------|--------|--------|
| - | No | <img width="697" src="https://github.com/user-attachments/assets/0963c0c1-253c-43d4-8be7-1e5baa6e869f"> |
| No | Yes | The "Use the store designer" button shows a modal to warn about a theme switch. <img width="706" src="https://github.com/user-attachments/assets/c26adf05-cf87-4737-9120-36285cd33148"> |
| Yes | Yes | The "Use the store designer" button shows a modal to warn about a theme switch. <img width="705" src="https://github.com/user-attachments/assets/29e2fdef-528d-4734-bcff-be65aad8b289"> |

## Other

**Network offline**

<img width="905" src="https://github.com/user-attachments/assets/21d3b9ab-fb1b-455f-8575-0921bb91be21">
