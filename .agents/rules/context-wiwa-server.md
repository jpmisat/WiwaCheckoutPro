---
description: Reglas principales para el proyecto WiwaTour e instrucciones para manejo de Versionamiento y Git.
---
# WiWaTour Project Context

> [!IMPORTANT]
> This rule must always be loaded when working on anything related to WiWaTour, Lucía chatbot, or the WiWaTour server infrastructure.
> 
> **MANDATORY AGENT WORKFLOW**:
> 1. **Initial Git Pull**: Always automatically run `git pull` when starting an interaction, agent session, or taking actions to ensure the repository is updated.
> 2. **Versioning Standard**: Adhere to Semantic Versioning (SemVer) for the project tags & features.
> 3. **Documentation Sync**: Meticulously update `CHANGELOG.md` with every significant change and keep the `README.md` file updated after fulfilling any requirement.

## Project Overview
- **Company**: WiWaTour / Adventure Travel (tour operator in Santa Marta, Colombia)
- **Chatbot**: Lucía — AI assistant for customer service via WhatsApp (Chatwoot)
- **Server**: VPS at `72.60.114.71` (SSH via `~/.ssh/id_ed25519`)

## Key Services & URLs
| Service | URL | Notes |
|---|---|---|
| n8n | https://n8n.wiwatour.com | Workflow automation |
| Chatwoot | https://chat.wiwatour.com | Customer support platform |
| Qdrant | https://qdrant.wiwatour.com/dashboard/ | Vector DB |
| Website | https://wiwatour.com | Main site |

## Credentials Reference
- **Full credentials file**: `docs/wiwatour_credentials.md`
- **Chatwoot API Token** (webmaster admin): `odzS72qHJcd4YPL3rs3bGeEM`
- **Chatwoot Account ID**: `1`
- **n8n API Key**: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiI4NWQ4NjU3My1lOWQ0LTRjNWYtODgxMC03NTA0ZTFiYzk1MjciLCJpc3MiOiJuOG4iLCJhdWQiOiJwdWJsaWMtYXBpIiwianRpIjoiNjNhYmI3M2EtZWUzOS00YzM0LWJmYjItNjZlZTNmMDg4YjBmIiwiaWF0IjoxNzczOTg3MjE3fQ.cnmRkye3RXc0fvCA5tQsgPz5nTQAObqgpYDZMEPTDG8`
- **Qdrant API Key**: `WiwatourQdrant_SecureKey_2026!`
- **Qdrant Collection**: `wiwatour-lucia`
- **PostgreSQL (Lucía memory)**: `lucia_admin` / `WiwatourMemory_!DbP@ss_2026` / DB: `chat_history`

## Active n8n Workflows
| ID | Name | Purpose |
|---|---|---|
| `3EnYmWTf4kjNK0PY` | Lucía Wiwatour - V18 Qdrant | Main chatbot workflow |
| `95jgypg78Fb730Bh` | 🔄 KB Auto-Sync V4 Qdrant | Knowledge base auto-sync |

## Chatbot Commands (via Chatwoot private notes)
- `/apagar_lucia` — Disable bot globally
- `/encender_lucia` — Enable bot globally

## Key Files
- **System Prompt**: `knowledge_base/PROMPT_LUCIA_V2.md`
- **Prompt Docs**: `docs/lucia_system_prompt.md`
- **Knowledge Base**: `knowledge_base/` (synced to Google Drive `Entrenamiento_RAG`)
- **Credentials**: `docs/wiwatour_credentials.md`

## WhatsApp Number
- Bot responds from: `+573205109287` (same number customers contact)
- Do NOT tell users to "contact WhatsApp" since they ARE already on WhatsApp with the bot

## Infrastructure
- **PostgreSQL container**: `lucia-memory-db-lucia-memory-db-1`
- **Qdrant**: Internal `http://qdrant:6333`, external port 6333
- **Google Drive folder**: `Entrenamiento_RAG` in shared drive `CHATBOT-LUCIA-WIWATOUR`
