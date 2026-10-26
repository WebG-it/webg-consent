# WebG Consent

Plugin di sistema Joomla (4.4, 5 e 6) di WebG: un avviso a tutta pagina ("age gate") per i siti con contenuti riservati ai maggiorenni, con registro del consenso pseudonimo. Gratuito, licenza GNU GPL v2 o successiva, con o senza YOOtheme.

Versione 1.0.0. Elemento `webgconsent`, gruppo `system`.

- I testi escono dai file lingua di Joomla (italiano e inglese) e si cambiano da Sistema, Override lingua.
- Il modale nasce nel browser: le pagine in cache restano valide e Googlebot vede lo stesso HTML di tutti.
- Accessibile da tastiera e da lettore di schermo, nessun jQuery, nessuna richiesta esterna.
- Registro del consenso con ID casuale e impronta dell'IP (mai l'IP in chiaro), esportabile in CSV.
- Gli aggiornamenti arrivano da Sistema, Aggiorna.

## Installazione

Scarica `plg_system_webgconsent-1.0.0.zip` dalla pagina delle release (o dalla cartella `dist/`), poi Estensioni, Installa. Alla prima installazione il plugin resta disabilitato: aprilo, controlla i parametri, abilitalo e svuota le cache.

## Guide

- Guida d'uso in italiano: [docs/webgconsent/USO.md](docs/webgconsent/USO.md)
- User guide in English: [docs/webgconsent/USAGE.md](docs/webgconsent/USAGE.md)

## Sviluppo

`python tools/build.py` costruisce lo zip in `dist/`, `python tools/check_jed.py` controlla il pacchetto, `python tools/gen_lang.py` rigenera i file lingua. Prova in Docker con `tools/test-local.sh`. Un solo repository per prodotto: questo repository ha il proprio server di aggiornamento (`collection.xml` e `updates/`).

Autore: Marco Galassi (WebG), https://www.webg.it
