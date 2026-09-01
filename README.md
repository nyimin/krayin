# PowerEdge — Krayin Docker Image

Custom Docker image for the **PowerEdge Solutions** Krayin CRM (residential solar + battery business), deployed on Dokploy. Source repo for `https://poweredge.altlabdev.com`.

This is the **image build source only**. Krayin's application source is cloned from `krayin/laravel-crm` (v2.2.3) at **build time** inside the Dockerfile — it is not vendored here.

## What's in this repo

| File | Purpose |
|---|---|
| `Dockerfile` | Builds the PHP 8.3+Apache image: installs deps + PHP extensions, clones Krayin 2.2.3, forces HTTPS URL generation, copies the pricing API controller + routes, sets the Apache vhost → `public/`. |
| `entrypoint.sh` | Container start script: ensure `.env`, force https APP_URL/ASSET_URL, wait for DB, migrate + seed, `storage:link`, `optimize:clear`, start Apache. |
| `api/ProductApiController.php` | Custom REST controller (Sanctum auth) for the pricing sync. |
| `api/routes.php` | Replaces `routes/api.php` — token issuance + product upsert/read. |
| `.dockerignore` | Excludes local junk from the build context. |

## Pricing API (added)

Sanctum-token authenticated. Used by the Airtable → n8n → Krayin pricing sync to push updated **sell prices** from the buy-side price book into Krayin products.

- `POST /api/v1/token` — `{email, password}` → `{token, type:"Bearer"}`
- `POST /api/v1/products/upsert` — `{sku, name?, description?, price, quantity?}` → create/update by SKU (preserves name/desc/qty on partial update)
- `GET /api/v1/products/{sku}` — read a product
- `GET /api` — health/status

## Deploy

Dokploy project `PowerEdge` → app `krayin` → Dockerfile build from this repo (`github.com/nyimin/krayin`, branch `main`).

1. Edit files locally.
2. `git add -A && git commit -m "..." && git push`
3. Dokploy redeploys (manual or connected to push). ~4 min build.

## Local dev loop

```bash
git pull                      # get latest
# edit Dockerfile / entrypoint.sh / api/*.php
git add -A && git commit -m "change" && git push
```

## Notes

- The prebuilt `webkul/krayin:2.2.0` image was broken (entrypoint `sed` crashes on URL slashes) — this repo is the working replacement.
- Krayin source is cloned at build → **do not** add it here.
