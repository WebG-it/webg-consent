# WebG Consent, guida d'uso

Plugin di sistema Joomla (4.4, 5 e 6) di WebG: mostra a tutta pagina un avviso di accettazione ("age gate") per i siti con contenuti riservati ai maggiorenni e registra la scelta in forma pseudonima. Licenza GNU GPL v2 o successiva. Autore: WebG.

Versione 1.0.0, pacchetto `plg_system_webgconsent-1.0.0.zip` (ricostruibile con `python tools/build.py`).
Sorgenti in `plugins/plg_system_webgconsent/`. Elemento `webgconsent`, gruppo `system`, namespace `WebG\Plugin\System\Webgconsent`, tabella `#__webgconsent_log`, cookie `wgc_ok`, prefisso CSS e JS `wgc`.

## Cosa fa

- Inserisce subito dopo `<body>` un blocco in linea (stile e script, circa 8 KB) uguale per tutti i visitatori. Il modale nasce solo nel browser, quindi Cloudflare, LiteSpeed e la cache di Joomla continuano a funzionare.
- Il contenuto della pagina resta nel DOM: nessun redirect, nessun cloaking, Googlebot vede lo stesso HTML. Il modale ha `data-nosnippet`.
- Cookie tecnico `wgc_ok` (ID casuale e versione del testo), durata configurabile, `SameSite=Lax`, `Secure` su https. Contiene solo l'ID generato dal browser.
- Accessibilita': `role="dialog"`, `aria-modal`, focus trap, Esc non chiude, il resto della pagina e' `inert` finche' non si sceglie.
- "Esco" registra il rifiuto e porta all'indirizzo di uscita configurato (default un sito generico, meglio sceglierne uno neutro).
- Percorsi esclusi (informativa privacy e cookie, modificabili). Niente modale su pagine di errore, template `component` o `raw`, dentro un iframe e con i parametri di anteprima dei builder.

## Installazione

1. Estensioni, Installa, carica `plg_system_webgconsent-1.0.0.zip`. Viene creata la tabella `#__webgconsent_log`.
2. Il plugin resta DISABILITATO. Aprilo (Sistema, Plugin, "Sistema - WebG Consent"), controlla testo, indirizzo di uscita, percorsi esclusi e durata, salva.
3. Abilitalo, svuota le cache (Joomla, YOOtheme, Cloudflare) e verifica in una finestra privata: il modale compare, Tab resta dentro il riquadro, Esc non chiude, "Esco" porta fuori, accetta e al ricaricamento non compare piu'.

Se il sito ha una Content-Security-Policy senza `unsafe-inline`, lo stile e lo script in linea vengono bloccati: va concesso o il plugin non puo' funzionare.

## Testi e lingue

Tutti i testi visibili (modale, pulsanti, nota sulla privacy, scheda del backend) escono dai file lingua di Joomla, costanti `PLG_SYSTEM_WEBGCONSENT_*`, in italiano (it-IT) e inglese (en-GB); le altre lingue ricadono sull'inglese. Niente testo e' scritto nel codice o nel manifest.

- **Override lingua**: Sistema, Override lingua, scegli la lingua del sito (Sito), cerca `PLG_SYSTEM_WEBGCONSENT_DEF` e cambia il testo che vuoi: `DEF_TITLE`, `DEF_BODY` (una riga = un paragrafo, `\n` per andare a capo), `DEF_ACCEPT`, `DEF_EXIT`, `DEF_NOTE`, `DEF_PRIVACY`. Provato: l'override cambia davvero il modale e vale solo per la lingua in cui lo crei.
- **Scheda "Testo"**, facoltativa: una riga per lingua (le lingue installate piu' "Tutte le lingue"). I campi sono vuoti di default: un campo vuoto usa la costante di lingua, quindi anche l'override. Ordine: riga con la lingua attiva della pagina, stessa lingua primaria, "Tutte le lingue", costanti dei file lingua.
- **Versione del testo**: parte manuale piu' un'impronta calcolata sulle righe della scheda e sull'indirizzo della privacy. Se cambi una riga della scheda il consenso viene richiesto di nuovo. Se cambi un override o un file lingua l'impronta non cambia: in quel caso aumenta il campo "Versione del testo".

## Registro del consenso

Tabella `#__webgconsent_log`: data UTC, ID visitatore, versione del testo, esito, impronta dell'IP (HMAC-SHA256 con chiave derivata dal segreto del sito e sale facoltativo), user agent a 160 caratteri, solo il percorso della pagina di ingresso. E' pseudonimo, non anonimo: va citato nell'informativa privacy con base giuridica, finalita' e tempo di conservazione (default 730 giorni). Si puo' spegnere (`Registro attivo: No`).

Endpoint `com_ajax` (plugin `webgconsent`, gruppo `system`, formato `raw`): `token` (GET, token firmato valido da 1 secondo a 30 minuti), `log` (POST, richiede token, limite per IP e tetto globale), `export` (solo backend, `core.admin`, CSV).

Con Cloudflare davanti, il parametro "Il sito e' dietro Cloudflare" legge l'IP da `CF-Connecting-IP`: il limite per IP e' affidabile solo se l'origine accetta traffico solo da Cloudflare. Senza Cloudflare, mettilo a No.

Disinstallazione: il registro sopravvive (e' una prova); per cancellarlo imposta prima "Cancella il registro alla disinstallazione" a Si.

## Compatibilita'

- Joomla 4.4, 5.x, 6.x. PHP 8.1 o superiore. MySQL o MariaDB. Provato in Docker: Joomla 4.4 (PHP 8.2), 5.4 (PHP 8.3), 6.2.0 (PHP 8.4).
- YOOtheme Pro: nessuna classe `uk-*`, nessun CSS globale (solo `#wgc`, `.wgc-*`, `--wgc-*`), nessuna dipendenza da jQuery o UIkit, z-index sopra header sticky, offcanvas e modali UIkit. Niente modale nell'anteprima del customizer e del builder.
- Banner cookie di terzi (compreso il Consent Manager di YOOtheme): il plugin non tocca i loro cookie ne' il loro codice e usa un cookie tecnico proprio. L'ordine visivo e' il modale sopra tutto: il banner cookie compare dietro e si usa dopo l'accettazione. Questa convivenza e' progettata, non ancora verificata con il Consent Manager reale.

## Build e prova

- `python tools/build.py` costruisce `dist/plg_system_webgconsent-<versione>.zip` dalla versione del manifest.
- `python tools/check_jed.py` controlli statici ispirati al JED Checker (intestazione GPL, `_JEXEC`, licenza nel manifest, funzioni vietate, stringhe e chiavi lingua).
- `python tools/gen_lang.py` rigenera i file lingua it-IT ed en-GB da un'unica tabella.
- `JTAG=4.4-php8.2-apache N=wgc44 PORT=8744 bash tools/test-local.sh up`, poi `install`, poi `down`: Joomla e MariaDB usa e getta in Docker.

Aggiornamenti: il plugin dichiara come server di aggiornamento `collection.xml` di questo repository, quindi le nuove versioni si installano da Sistema, Aggiorna.
