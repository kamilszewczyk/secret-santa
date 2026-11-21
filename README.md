# Secret Santa

Family Secret Santa: draws who buys a present for whom, emails everyone their result, and lets each person keep a wishlist.

Symfony 6.4 · PHP 8.3 · SQLite · FrankenPHP (Docker)

## How it works

- Members (name, email, active) are managed in the admin at `/admin`.
- `bin/console app:draw` pairs all **active** members (nobody draws themselves) and sends each one an email with:
  - the name of the person they drew, linking to that person's wishlist (`/wishlist/{name}`),
  - a private link to edit **their own** wishlist (`/wishlist/edit/{uuid}`).
- There are no accounts. The secret UUID in the email link is what lets a person edit their list, so don't share those links.
- Wishlists are plain text: one idea per line, and `http(s)://` links become clickable.
- Draw results are not stored in the database. The only record is `var/log/draw.log`, which **reveals every pair**, so don't open it if you're taking part.

## Configuration

| Variable | Purpose |
|---|---|
| `MAILER_DSN` | Where emails are sent. See [Mailjet](#mailjet) below. |
| `PRICE_LIMIT` | Gift price limit in zł shown in the draw email, default `150`. |
| `DEFAULT_URI` | Public base URL used for links in the emails, e.g. `https://santa.example.com`. |
| `ADMIN_PASSWORD_HASH` | Password hash for the `admin` user (HTTP basic auth on `/admin`). Empty = nobody can log in. |
| `APP_SECRET` | Random string, e.g. `openssl rand -hex 16`. |
| `TRUSTED_PROXIES` | IPs/CIDRs of the reverse proxy, so generated URLs use https and the public host. |
| `DATABASE_URL` | SQLite file, default `var/data.db`. |

Create the admin password hash with:

```sh
docker compose exec php bin/console security:hash-password
```

The emails are sent from `kamil@szewczyk.org`, set in `src/Service/MailSender.php`.

## Local development

```sh
docker compose up -d --build
docker compose exec php composer install
```

- App: https://localhost (self-signed certificate). Set `HTTP_PORT`/`HTTPS_PORT` if 80/443 are taken.
- Emails go to Mailpit, never to real inboxes. Find its URL with `docker compose port mailer 8025`.
- Your real `var/data.db` and `var/log/` are mounted into the container.
- Put local secrets such as `ADMIN_PASSWORD_HASH` in `.env.local`, wrapped in **single quotes**, because the hash contains `$`.

Useful commands:

```sh
docker compose exec php bin/console app:draw --dry-run   # draw + log only, no emails
docker compose exec php bin/console app:test-email       # sends a sample email to kamil@szewczyk.org
```

## Production on TrueNAS Scale

`compose.truenas.yaml` is a standalone stack. Paste it in **Apps → Discover Apps → ⋮ → Install via YAML**.

1. Clone this repo on the NAS, e.g. to `/mnt/tank/apps/secret-santa/src`.
2. Create `/mnt/tank/apps/secret-santa/db` and `/mnt/tank/apps/secret-santa/log`, then copy `var/data.db` to `db/data.db`.
3. Adjust the paths in the YAML and fill in every `CHANGE_ME`. In that file every `$` in `ADMIN_PASSWORD_HASH` must be written as `$$`.
4. Install the app. The image is built on the NAS, and migrations run on start.
5. Point your reverse proxy (which handles HTTPS) at `http://<nas-ip>:8080`.

To update: `git pull` in `src/`, then redeploy the app.

If the container fails to start with a `setfacl` error, set the datasets' ACL type to POSIX.

### Running the draw

From the NAS shell:

```sh
docker exec -it $(docker ps -qf name=secret-santa) bin/console app:draw --dry-run   # check first
docker exec -it $(docker ps -qf name=secret-santa) bin/console app:draw             # sends the emails
```

Run the real draw only once. Every run makes a new random draw and emails everyone again.

## Mailjet

Use Mailjet's SMTP relay. It needs no extra package:

```
MAILER_DSN=smtp://API_KEY:SECRET_KEY@in-v3.mailjet.com:587
```

- `API_KEY` / `SECRET_KEY`: Mailjet → Account settings → API Key Management.
- The sender address (`kamil@szewczyk.org`) must be verified in Mailjet. Adding SPF/DKIM for the domain helps the emails avoid spam folders.
- URL-encode the keys if they contain special characters. In `compose.truenas.yaml`, also write any `$` as `$$`.
