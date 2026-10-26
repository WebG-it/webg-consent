# WebG Consent, user guide

System plugin for Joomla 4.4, 5 and 6 (PHP 8.1 or higher). It shows a full-page age gate on sites with content reserved for adults and records the choice in a pseudonymous register. Free, GNU GPL v2 or later, works with or without YOOtheme. The Italian guide with more detail is in [USO.md](USO.md).

## Install

1. Extensions, Install, upload `plg_system_webgconsent-1.0.0.zip`. The table `#__webgconsent_log` is created.
2. The plugin stays DISABLED after the first install. Open System, Plugins, "System - WebG Consent", check the exit address, excluded paths and cookie lifetime, save.
3. Enable it, clear the caches (Joomla, YOOtheme, Cloudflare) and check in a private window: the modal appears, Tab stays inside the box, Esc does not close it, "Leave" goes away, accept closes it and it does not come back on reload.

If the site sends a Content-Security-Policy without `unsafe-inline`, the inline style and script are blocked and the plugin cannot work.

## Texts and languages

Every visible text (modal, buttons, privacy note, back end) comes from the Joomla language files, keys `PLG_SYSTEM_WEBGCONSENT_*`, English (en-GB) and Italian (it-IT); other languages fall back to English. Nothing is written in the code or in the manifest.

- **Language Overrides** (System, Language Overrides, pick the site language, search `PLG_SYSTEM_WEBGCONSENT_DEF`): `DEF_TITLE`, `DEF_BODY` (one line = one paragraph), `DEF_ACCEPT`, `DEF_EXIT`, `DEF_NOTE`, `DEF_PRIVACY`. An override works for the language in which you create it.
- **Optional "Text" tab**: one row per language, empty by default. An empty field uses the language file, so the override too. Order: row for the page language, row for the same primary language, "All languages" row, language files.
- **Text version**: a manual part plus a fingerprint of the rows in the tab and of the privacy address. Changing a row asks visitors again. Changing an override does not change the fingerprint: raise the manual "Text version" field instead.

## Register

Table `#__webgconsent_log`: UTC date, visitor ID (same as the `wgc_ok` cookie), text version, outcome, HMAC-SHA256 fingerprint of the IP, user agent (160 characters), entry path only. It is pseudonymous, not anonymous: mention it in your privacy policy with legal basis, purpose and retention (default 730 days). It can be turned off. CSV export for super users in the plugin's "Consent register" tab. If the site is behind Cloudflare, set "The site is behind Cloudflare" to Yes so the IP is read from `CF-Connecting-IP` (reliable only if the origin accepts traffic from Cloudflare only).

On uninstall the register is kept; set "Delete the register on uninstall" to Yes first to remove it.

## Compatibility

Tested in Docker on Joomla 4.4 (PHP 8.2), 5.4 (PHP 8.3) and 6.2 (PHP 8.4). No `uk-*` classes, no global CSS (only `#wgc`, `.wgc-*`, `--wgc-*`), no jQuery or UIkit dependency; no modal inside an iframe or in builder previews. Cookie banners of third parties, including the YOOtheme consent manager, are not touched; the visual coexistence is by design and not yet verified with the real YOOtheme Pro.

## Updates

The plugin declares `collection.xml` of this repository as update server: new versions appear in System, Update.
