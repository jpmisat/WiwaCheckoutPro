# WiwaCheckoutPro — Project Rules

## Project Identity
- **Company**: WiWaTour — Adventure travel operator in Santa Marta, Colombia.
- **Product**: WiwaCheckoutPro — Custom WooCommerce checkout plugin for tour bookings.
- **Chatbot**: Lucía — AI-powered WhatsApp assistant (via Chatwoot) for customer service.

## Architecture & Infrastructure

### Server
- VPS at `72.60.114.71` (SSH key: `~/.ssh/id_ed25519`).
- All services run as Docker containers.

### Services
| Service   | URL                                        | Purpose                     |
|-----------|--------------------------------------------|-----------------------------|
| n8n       | https://n8n.wiwatour.com                   | Workflow automation          |
| Chatwoot  | https://chat.wiwatour.com                  | Customer support platform    |
| Qdrant    | https://qdrant.wiwatour.com/dashboard/     | Vector database (RAG)        |
| Website   | https://wiwatour.com                       | Main tour booking site       |

### Key Containers
- **PostgreSQL**: `lucia-memory-db-lucia-memory-db-1` (chat history / memory)
- **Qdrant**: Internal `http://qdrant:6333`, external port `6333`
- **Qdrant Collection**: `wiwatour-lucia`

## Credentials
> [!CAUTION]
> Never hardcode credentials in source code or rules files. All secrets are stored in `docs/wiwatour_credentials.md` (gitignored). Reference that file when you need API keys, tokens, or database passwords.

## Active n8n Workflows
| ID                  | Name                              | Purpose                      |
|---------------------|-----------------------------------|------------------------------|
| `3EnYmWTf4kjNK0PY`  | Lucía Wiwatour - V18 Qdrant       | Main chatbot workflow         |
| `95jgypg78Fb730Bh`  | 🔄 KB Auto-Sync V4 Qdrant         | Knowledge base auto-sync     |

## Chatbot Commands (Chatwoot Private Notes)
- `/apagar_lucia` — Disable Lucía globally.
- `/encender_lucia` — Enable Lucía globally.

## Key Project Files
| Path                                  | Purpose                              |
|---------------------------------------|--------------------------------------|
| `knowledge_base/PROMPT_LUCIA_V2.md`   | Current Lucía system prompt          |
| `docs/lucia_system_prompt.md`         | Prompt documentation                 |
| `knowledge_base/`                     | RAG knowledge base (synced to GDrive)|
| `docs/wiwatour_credentials.md`        | All credentials (DO NOT commit)      |

## Coding Rules

### WordPress / PHP
- This is a **WordPress plugin** (WiwaCheckoutPro). Follow WordPress Coding Standards.
- Use `wiwa_` prefix for all functions, hooks, and database tables.
- Sanitize all inputs (`sanitize_text_field`, `absint`, etc.) and escape all outputs (`esc_html`, `esc_attr`, etc.).
- Use WordPress nonces for form submissions and AJAX requests.
- Enqueue scripts/styles properly via `wp_enqueue_script` / `wp_enqueue_style`.

### General
- Write all code comments and commit messages in **Spanish**.
- Use descriptive variable names related to tour/travel domain.
- Keep the plugin modular: admin logic in `admin/`, frontend in `templates/`, helpers in `includes/`.

## WhatsApp Bot Rules
- Bot responds from: `+573205109287`.
- **NEVER** tell users to "contact WhatsApp" — they are **already on WhatsApp** with the bot.
- Google Drive sync folder: `Entrenamiento_RAG` in shared drive `CHATBOT-LUCIA-WIWATOUR`.
