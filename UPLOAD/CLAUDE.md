# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

GRPG ("Generic RPG") — a mafia-style, browser-based multiplayer text RPG originally written by Brandon Werner (Publius) / MyNeoCorp Productions, 2007. It's a flat-file PHP application: every top-level `.php` file is a directly-requested page (no router, no framework, no build step, no package manager, no test suite). There is no `composer.json`, no `README`, and no existing `CLAUDE.md` — this is the first one.

Because this is a legacy single-era codebase, match the existing style (old-style PHP4 class constructors, mysqli string-concatenated queries, `$_GET`/`$_SESSION` globals) when editing nearby code rather than introducing a modern pattern in isolation. Don't do drive-by modernization of unrelated code.

## Running / developing

There is no CLI build or test process — this is deployed as-is to a PHP + MySQL host and run through a web server (e.g. Apache/PHP-FPM) pointed at this directory. To work on it locally you need PHP with the `mysqli` extension and a MySQL/MariaDB database named `myneocorp` matching the schema implied by the queries (see "Database" below — there is no `.sql` schema file in the repo, so table structure has to be inferred from the queries in `classes.php` and the page scripts).

There is no lint or test command configured for this project.

## Request lifecycle

Nearly every page follows the same shape:

```php
<?php
include 'header.php';
// ... page logic, may echo HTML directly into the response ...
include 'footer.php';
?>
```

`header.php` is the de facto framework entry point and does all of the following, in order, on **every** authenticated page load:
1. `session_start()`; if `$_SESSION['id']` isn't set, it includes `home.php` (the logged-out landing page) and `die()`s — so `header.php` doubles as the auth gate.
2. Includes `dbcon.php` (opens the mysqli connection as `$conn`), `classes.php` (helper functions + `User`/`Gang`/`User_Stats` classes), and `updates.php` (a cron-like "catch up" routine — see below).
3. Handles `?action=logout`.
4. Checks `Is_User_Banned()` and server-down mode (`serverconfig.serverdown`).
5. Constructs `$user_class = new User($_SESSION['id'])` — this is the current player, available to every page after `include 'header.php'`.
6. Loads the user's UI color style from the `styles` table and starts output buffering (`ob_start('callback')`) so a `callback()` closure can rewrite placeholder tokens like `<!_-money-_!>`, `<!_-formhp-_!>`, etc. anywhere in the buffered HTML (used by the persistent top/side nav markup baked into `header.php`'s own HTML output) before it's sent to the browser.
7. Prints the opening `<html>`/layout chrome that every page's markup is nested inside.

`footer.php` closes the layout table/HTML, prints the site stat line, and calls `ob_end_flush()` to flush the buffered/rewritten output.

Logged-out pages use `nliheader.php` / `nlifooter.php` instead (same idea, no auth, no `User` object).

**Implication for edits:** any page-level `.php` file assumes `header.php` has already run and that `$conn`, `$user_class`, and the helper functions from `classes.php` are in scope. Don't `include 'header.php'` twice on one request path.

### The "updates" tick

`updates.php` is included by every page load and acts as a lazy cron: it checks a `lastdone` timestamp in the `updates` table (rows keyed by `name`, e.g. `'trevor'`, `'hospital'`) and, if enough real time has passed, batch-regenerates stock prices and regenerates every player's energy/nerve/hp/awake (`'trevor'`, every 5 min) or decrements hospital/jail timers and status effects (`'hospital'`, every 1 min), across **all** users in `grpgusers`. There is no separate cron job/worker — game-tick simulation piggybacks on whichever player happens to load a page next.

## Core data model (`classes.php`)

`classes.php` has no namespaces/autoloading; everything is a global function or old-style (PHP4-syntax: `function User($id)`) class constructor, all relying on the global `$conn` mysqli link.

- **`User`** — the central entity. Constructing `new User($id)` runs ~6 queries (`grpgusers`, their `gang`, `city`, `house`, `inventory`, equipped weapon/armor from `items`) and computes a large set of derived/formatted properties in the constructor itself: `maxhp`/`maxenergy`/`maxnerve` (derived from `level`), `moddedstrength`/`moddeddefense` (base stat modified by equipped weapon/armor bonus %), `formattedhp`/`formattedenergy`/etc. (display strings like `"80 / 100 [80%]"`), and percentage fields used by the header's placeholder-rewriting `callback()`. Any change to leveling, equipment, or stat math generally belongs here since page scripts just read the precomputed properties.
- **`Gang`** and **`User_Stats`** — same pattern (constructor queries + derives display fields), for gang info and site-wide aggregate stats (total players, players online, etc.) respectively.
- Free functions in the same file cover inventory/shares/land transfer (`Give_Item`/`Take_Item`, `Give_Share`/`Take_Share`, `Give_Land`/`Take_Land`), leveling math (`experience()`, `Get_The_Level()`, `Get_Max_Exp()`), messaging (`Message()`, `Send_Event()`), ban checks (`Is_User_Banned`, `Why_Is_User_Banned`), and formatting helpers (`prettynum`, `howlongago`, `howlongtil`).

`database.php` is **dead code** — a leftover/corrupted legacy DB-abstraction file (mixed `mysql_*`/`mysqli_*`, syntactically broken) that nothing includes. `dbcon.php` (2 lines, opens `$conn` via `mysqli_connect`) is the real connection point. Don't try to "fix" `database.php` into working order unless asked — the live code path doesn't use it.

## Database

MySQL, accessed only via raw `mysqli_query($conn, "...")` string-built queries — no prepared statements, no ORM, no migrations directory. Table names are discoverable via `grep -rho "FROM \`[a-z_]*\`" *.php | sort -u`; the main ones are `grpgusers` (players — id, username, password, money, bank, stats, equipped items, city, gang, house, hospital/jail timers, `admin` flag, etc.), `gangs`, `items`, `inventory`, `cities`, `houses`, `effects`, `events`, `pms`, `stocks`/`shares`, `land`, `cars`/`carlot`, `crimes`, `jobs`, `lottery`, `bans`, `serverconfig`, `styles`, `updates`.

Because there's no schema file in the repo, when a task needs to know a table's exact columns, grep for existing queries against that table across the codebase rather than guessing.

## Authorization patterns

- Player identity: `$_SESSION['id']`, hydrated into `$user_class` by `header.php` on every request.
- Admin-gated pages/actions check `$user_class->admin` (e.g. `if ($user_class->admin != 1) { ... die(); }` in `control.php`, the admin panel). There's no role system beyond this integer flag.
- State-changing actions are routinely triggered via `$_GET` params (e.g. `staff.php?radio=on`, `control.php?givecredit=...`) rather than POST — follow existing conventions in the target file rather than "correcting" this in isolation.

## Known characteristics to be aware of (not a to-do list)

This codebase predates modern PHP security practice and is internally consistent about it — every page does things the same (dated) way:

- Passwords are stored and compared in plaintext (`login.php`: `$worked['password'] == $password`).
- Nearly all SQL is built via direct string concatenation of `$_GET`/`$_POST`/`$_SESSION` values with no escaping/prepared statements — this is the pervasive pattern, not an isolated bug.
- No CSRF tokens on state-changing `$_GET`/POST actions.
- Mixed `<?php` and short-tag `<?` usage across files.

Only touch these when the task specifically asks for a security fix or you're told to modernize a given file — otherwise match the surrounding code's existing (insecure) idiom so the diff stays focused on the actual task.
