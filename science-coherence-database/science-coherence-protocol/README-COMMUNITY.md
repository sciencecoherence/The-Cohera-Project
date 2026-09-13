# Community section — Hostinger setup

The `community/` folder is a small PHP application: open signup, a write page,
and an approval queue. Nothing appears publicly until you approve it.
It runs on the PHP + MySQL that your Hostinger web-hosting plan already includes.

## 1. Create the database

1. hPanel → **Websites** → **Manage** (sciencecoherence.com) → **Databases** → **MySQL databases**.
2. Create a new database + user. Hostinger prefixes both with your account id,
   e.g. database `u123456789_transmission`, user `u123456789_admin`.
3. Write down: database name, username, password. Host stays `localhost`.

## 2. Create the tables

1. Same page → **Enter phpMyAdmin** next to your new database.
2. Select the database in the left sidebar → **SQL** tab.
3. Paste the entire contents of `community/schema.sql` → **Go**.
4. You should see the `users` and `posts` tables appear in the sidebar.

## 3. Configure

Edit `community/inc/config.php` — put your database name, user, and password
in the four constants. Do not commit real credentials to a public repo.

## 4. Upload

Upload the `community/` folder (and the updated `styles.css` and HTML files)
into `public_html/` the same way you deploy the rest of the site
(hPanel File Manager, or your git deploy). Final layout:

```
public_html/
├── index.html
├── directory.html
├── styles.css
├── notes/
├── transmissions/
└── community/
    ├── index.php  post.php  write.php
    ├── register.php  login.php  logout.php  moderate.php
    ├── schema.sql
    └── inc/  (config.php, db.php, auth.php, layout.php)
```

Check PHP version while you are in hPanel (**Advanced → PHP Configuration**):
anything 8.0+ is fine.

## 5. Claim the admin account — do this FIRST

Visit `https://www.sciencecoherence.com/community/register.php` and create
your own account. **The first account ever registered automatically becomes
admin.** Do this before sharing the link with anyone.

## 6. How it works day to day

- Anyone can register at `community/register.php` and submit at `community/write.php`.
- Submissions land in **pending** — invisible to the public.
- You review at `community/moderate.php` (link appears in your nav when logged
  in as admin): preview, then Approve or Reject.
- Approved posts appear at `community/` (the feed) and get their own page at
  `community/post.php?slug=...`.

## Writing format

Plain text. Blank line = new paragraph. HTML is displayed as text, not
executed — this is intentional (prevents script injection).

## Security notes

- Passwords hashed with PHP `password_hash` (bcrypt).
- All database access via prepared statements (no SQL injection).
- All forms carry CSRF tokens.
- All output is escaped (no XSS from post content).
- If you ever need to make another user admin:
  in phpMyAdmin run `UPDATE users SET is_admin = 1 WHERE username = 'name';`

## v2 — media uploads and direct publishing

If you already ran `schema.sql` before v2 existed, run `community/schema-v2.sql`
the same way (phpMyAdmin → SQL tab). It adds the `media` table, the
`transmission` type, and the site/community channel.

What v2 changes:

- **Admin publishes instantly.** Your own posts skip the review queue.
- **Publish as site or community.** As admin, the write page has a "Publish as"
  choice. "Site" posts appear without the community label, with an authorship
  note, and can be typed as transmissions.
- **Audio & video.** Any author can attach one audio file (mp3, m4a, ogg, wav)
  and one video (mp4, webm), 200 MB max each. Files from community submissions
  stay effectively hidden until you approve the post (random unguessable
  filenames, no directory listing). Players render at the top of the post.
- Uploaded files live in `community/media/` — keep the `.htaccess` in that
  folder (it blocks script execution).

**Upload size limit:** if large files fail, raise the PHP limits in hPanel →
Advanced → PHP Configuration → PHP options: `upload_max_filesize` and
`post_max_size` (set both to e.g. 256M), `max_execution_time` 300.

- **Editing (`community/edit.php`, "Edit" in the nav).** As admin you see every
  post, can change anything (title, body, type, site/community, status), add or
  remove media, and delete posts. Authors see only their own posts — and any
  edit they make sends the post back to pending, so nothing changes publicly
  without your review. Slugs never change on edit, so published links stay valid.

## v3 — the whole site is database-driven

Everything is now browser-editable. What changed:

1. **`community/migrate.php`** — one-time importer. Visit it once in your
   browser (logged in as admin): it moves the three transmissions and the Ethos
   note into the database, with their original dates. Safe to run twice (skips
   what already exists). Delete it from the server afterwards.
2. **`index.php` and `directory.php`** (site root) — the Feed and Index now
   read from the database. The old `index.html` and `directory.html` are
   retired; `.htaccess` makes PHP take priority and 301-redirects all old
   `.html` links (including the transmission pages) to their new database URLs.
3. **Writing format got richer.** In the write/edit body you can now use,
   each on its own line with blank lines around it:
   - `--- Section name ---` → a section divider (like "— The lies come first —")
   - `[eq 7] ∇Ψ = 0` → a numbered equation box (`<sub>` and `<sup>` allowed inside)
   - `[stamp] — Recorded 2 July 2026.` → a closing stamp line
   - `*word*` inside a paragraph → italics

Upload for v3: `.htaccess`, `index.php`, `directory.php`, and the `community/`
folder. Then visit `/community/migrate.php` once. The static `transmissions/`
and `notes/` folders can stay on the server (redirects bypass them) or be
deleted — your local copies remain as backup either way.

**Backup note:** your writing now lives in MySQL, not in git. Hostinger takes
automatic backups (hPanel → Files → Backups), and you can export anytime in
phpMyAdmin → Export. Do this before big changes.

## If something breaks

- **"Connection failed" / blank page** → credentials in `config.php` don't
  match hPanel. Re-check name/user/password.
- **404 on community/** → the folder didn't land inside `public_html/`.
- **Sessions not sticking** → make sure you access the site consistently via
  `https://www.` (cookies are per-domain).
