# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

GRPG ("Generic RPG" / GRPG Framework v1.1) — a mafia-style, browser-based multiplayer text RPG written by Brandon Werner (Publius), 2007. It's a flat-file PHP application distributed as a template: every top-level `.php` file under `UPLOAD/` is a directly-requested page (no router, no framework, no build step, no package manager, no test suite, no autoloading).

Repo layout:
- `UPLOAD/` — the entire application. Everything a deployment needs, meant to be uploaded as-is to a PHP+MySQL webserver document root.
- `grpgtables.sql` — the full MySQL schema (the only source of truth for table/column definitions; there are no migrations).
- `README.md` — original install instructions (dump `grpgtables.sql`, edit `UPLOAD/dbcon.php` with real DB creds, upload `UPLOAD/*`, promote your account via `grpgusers.admin = 1`, cron `rollover.php` at midnight).

As the README states directly: **this code contains short tags, SQL injection vulnerabilities, and deprecated calls throughout**, and it uses the old, removed-in-PHP7 `mysql_*` extension (not `mysqli_*`) and PHP4-style class constructors (`function User($id)` inside `class User`), so it will not run at all on PHP7+ without porting. Because the whole codebase is internally consistent about these dated patterns, match the existing style when editing nearby code rather than introducing a modern pattern in isolation — don't do drive-by modernization of unrelated code unless a task specifically asks for it.

## Running / developing

There is no CLI build, lint, or test command. To run it: PHP with the legacy `mysql` extension (PHP 5.x or earlier, or a `mysql_*` shim) is required as-is, plus a MySQL/MariaDB database loaded from `grpgtables.sql`. Set real credentials in `UPLOAD/dbcon.php` (ships with placeholder `YOUR_USERNAME`/`YOUR_PASSWORD`/`YOUR_DB`), point a webserver at `UPLOAD/`, and schedule `rollover.php` via cron at midnight server time — it's the one piece of logic that isn't triggered by a page load (see below).

## Request lifecycle

Nearly every page in `UPLOAD/` follows the same shape:

```php
<?
include 'header.php';
// ... page logic, may echo HTML directly into the response ...
include 'footer.php';
?>
```

`header.php` is the de facto framework entry point and does all of the following, in order, on **every** authenticated page load:
1. `session_start()`; if `$_SESSION['id']` isn't set, it includes `home.php` (the logged-out landing page) and `die()`s — so `header.php` doubles as the auth gate.
2. Includes `dbcon.php` (opens the mysql connection), `classes.php` (helper functions + `User`/`Gang`/`User_Stats` classes), and `updates.php` (a cron-like "catch up" routine — see below).
3. Handles `?action=logout`.
4. Checks `Is_User_Banned()` and server-down mode (`serverconfig.serverdown`).
5. Constructs `$user_class = new User($_SESSION['id'])` — this is the current player, available to every page after `include 'header.php'`.
6. Loads the user's UI color style from the `styles` table and starts output buffering (`ob_start('callback')`) so a `callback()` closure can rewrite placeholder tokens like `<!_-money-_!>`, `<!_-formhp-_!>`, etc. anywhere in the buffered HTML (used by the persistent top/side nav markup baked into `header.php`'s own HTML output) before it's sent to the browser.
7. Prints the opening `<html>`/layout chrome that every page's markup is nested inside.

`footer.php` closes the layout table/HTML, prints the site stat line, and calls `ob_end_flush()` to flush the buffered/rewritten output.

Logged-out pages use `nliheader.php` / `nlifooter.php` instead (same idea, no auth, no `User` object).

**Implication for edits:** any page-level `.php` file assumes `header.php` has already run and that `$user_class` and the helper functions from `classes.php` are in scope (the mysql connection is implicit/global, not passed around). Don't `include 'header.php'` twice on one request path.

### The "updates" tick

`updates.php` is included by every page load and acts as a lazy cron: it checks a `lastdone` timestamp in the `updates` table (rows keyed by `name`, e.g. `'trevor'`, `'hospital'`) and, if enough real time has passed, batch-regenerates stock prices and regenerates every player's energy/nerve/hp/awake (`'trevor'`, every 5 min) or decrements hospital/jail timers and status effects (`'hospital'`, every 1 min), across **all** users in `grpgusers`. There is no separate cron job/worker for this tick — game-tick simulation piggybacks on whichever player happens to load a page next.

`rollover.php` is the one exception: it's meant to run via an actual crontab entry at midnight (per the README), not via a page-load tick.

## Core data model (`classes.php`)

`classes.php` has no namespaces/autoloading; everything is a global function or old-style (PHP4-syntax: `function User($id)`) class constructor, all relying on an implicit global mysql connection.

- **`User`** — the central entity. Constructing `new User($id)` runs ~6 queries (`grpgusers`, their `gang`, `city`, `house`, `inventory`, equipped weapon/armor from `items`) and computes a large set of derived/formatted properties in the constructor itself: `maxhp`/`maxenergy`/`maxnerve` (derived from `level`), `moddedstrength`/`moddeddefense` (base stat modified by equipped weapon/armor bonus %), `formattedhp`/`formattedenergy`/etc. (display strings like `"80 / 100 [80%]"`), and percentage fields used by the header's placeholder-rewriting `callback()`. Any change to leveling, equipment, or stat math generally belongs here since page scripts just read the precomputed properties.
- **`Gang`** and **`User_Stats`** — same pattern (constructor queries + derives display fields), for gang info and site-wide aggregate stats (total players, players online, etc.) respectively.
- Free functions in the same file cover inventory/shares/land transfer (`Give_Item`/`Take_Item`, `Give_Share`/`Take_Share`, `Give_Land`/`Take_Land`), leveling math (`experience()`, `Get_The_Level()`, `Get_Max_Exp()`), messaging (`Message()`, `Send_Event()`), ban checks (`Is_User_Banned`, `Why_Is_User_Banned`), and formatting helpers (`prettynum`, `howlongago`, `howlongtil`).

`database.php` is **dead code** — a broken/corrupted leftover DB-abstraction file (garbled `mysql`/`mysqli` text, syntactically invalid) that nothing includes. `dbcon.php` (a few lines, opens the connection via `mysql_connect`) is the real connection point. Don't try to "fix" `database.php` into working order unless asked — the live code path doesn't use it.

## Database

MySQL, accessed only via raw `mysql_query("...")` string-built queries (old, removed-in-PHP7 `mysql_*` API) — no prepared statements, no ORM, no migrations. `grpgtables.sql` is the authoritative schema; consult it directly for exact columns rather than inferring from queries. Tables include: `grpgusers` (players — id, username, password, money, bank, stats, equipped items, city, gang, house, hospital/jail timers, `admin` flag, etc.), `gangs`, `ganginvites`, `ganglog`, `gangarmory`, `items`, `inventory`, `itemmarket`, `cities`, `houses`, `effects`, `events`, `pms`, `message`, `stocks`, `shares`, `land`, `cars`, `carlot`, `parts`, `crimes`, `jobs`, `school`, `growing`, `lottery`, `5050game`, `pointsmarket`, `referrals`, `bans`, `chat`, `shoutbox`, `news`, `ads`, `spylog`, `serverconfig`, `styles`, `todo`, `updates`.

## Authorization patterns

- Player identity: `$_SESSION['id']`, hydrated into `$user_class` by `header.php` on every request.
- Admin-gated pages/actions check `$user_class->admin` (e.g. `if ($user_class->admin != 1) { ... die(); }` in `control.php`, the admin panel). There's no role system beyond this integer flag — promoting an account is a manual `UPDATE grpgusers SET admin = 1` per the README.
- State-changing actions are routinely triggered via `$_GET` params (e.g. `staff.php?radio=on`, `control.php?givecredit=...`) rather than POST — follow existing conventions in the target file rather than "correcting" this in isolation.

## Known characteristics to be aware of (not a to-do list)

This codebase predates modern PHP security practice and is internally consistent about it — every page does things the same (dated) way:

- Passwords are stored and compared in plaintext (`login.php`: `$worked['password'] == $password`).
- Nearly all SQL is built via direct string concatenation of `$_GET`/`$_POST`/`$_SESSION` values with no escaping/prepared statements — this is the pervasive pattern, not an isolated bug.
- No CSRF tokens on state-changing `$_GET`/POST actions.
- Uses PHP short tags (`<?`) throughout, and the deprecated/removed `mysql_*` extension rather than `mysqli`/PDO — the app will not run on any PHP version that dropped `mysql_*` (PHP 7+) without a port.

Only touch these when the task specifically asks for a security fix or porting/modernization — otherwise match the surrounding code's existing idiom so the diff stays focused on the actual task.
