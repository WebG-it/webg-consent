"""Genera i file lingua del plugin WebG Consent (it-IT ed en-GB) da un'unica tabella,
cosi' le due lingue restano allineate. Uso: python tools/gen_lang.py"""
import os

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'plugins', 'plg_system_webgconsent', 'language')

IT = {
    'XML_DESCRIPTION': "Modale a tutta pagina (age gate) per siti con contenuti riservati ai maggiorenni, con registro pseudonimo del consenso. Il testo si modifica dalla scheda del plugin, per lingua. Funziona anche con le cache di pagina: il modale nasce nel browser. Alla prima installazione il plugin resta disabilitato.",
    'PREFLIGHT_PHP': "Per WebG Consent serve PHP %s o superiore.",
    'PREFLIGHT_JOOMLA': "Per WebG Consent serve Joomla %s o superiore.",
    'POSTINSTALL': "WebG Consent installato e DISABILITATO. Controlla testo e parametri, poi abilitalo dalla scheda del plugin.",
    'FS_TEXT': "Testo", 'FS_LOOK': "Aspetto", 'FS_BEHAVIOUR': "Comportamento", 'FS_REGISTER': "Registro del consenso",
    'F_VERSION': "Versione del testo (parte manuale)",
    'F_VERSION_DESC': "Lettere, numeri, trattino e underscore, massimo 24 caratteri. Alla versione si aggiunge da sola un'impronta dei testi inseriti qui sotto: se modifichi un testo il consenso viene richiesto di nuovo. Cambia questo campo a mano solo se modifichi i file lingua o vuoi forzare una nuova richiesta.",
    'F_TEXTS': "Testi per lingua",
    'F_TEXTS_DESC': "Una riga per lingua. Il visitatore vede la riga della lingua attiva della pagina; se manca, la riga Tutte le lingue; se manca anche quella, il testo predefinito dei file lingua (italiano e inglese). I campi lasciati vuoti usano il testo dei file lingua, che si cambia anche da Sistema, Override lingua (chiavi PLG_SYSTEM_WEBGCONSENT_DEF_*). Se cambi un override, cambia anche la versione del testo.",
    'F_LANG': "Lingua", 'OPT_ALL': "Tutte le lingue",
    'F_TITLE': "Titolo", 'F_BODY': "Testo del modale", 'F_BODY_DESC': "Una riga = un paragrafo. Solo testo semplice, niente HTML.",
    'F_BTN_ACCEPT': "Pulsante di accettazione", 'F_BTN_EXIT': "Pulsante di uscita", 'F_FOOTNOTE': "Nota sulla registrazione",
    'F_PRIVACY_LABEL': "Testo del link alla privacy",
    'F_ROW_PRIVACY_URL': "Indirizzo della privacy policy (questa lingua)",
    'F_ROW_PRIVACY_URL_DESC': "Facoltativo. Se vuoto vale l'indirizzo generale qui sotto.",
    'F_EXIT_URL': "Indirizzo di uscita",
    'F_EXIT_URL_DESC': "Dove va chi preme il pulsante di uscita. Deve iniziare con http:// o https://. Scegli un sito neutro.",
    'F_PRIVACY_URL': "Indirizzo della privacy policy",
    'F_PRIVACY_URL_DESC': "Percorso del sito (inizia con /) o indirizzo completo. Lascia vuoto per non mostrare il link.",
    'F_C_OVERLAY': "Colore dello sfondo", 'F_OVERLAY_OPACITY': "Opacità dello sfondo (50-100)",
    'F_C_PANEL': "Colore del riquadro", 'F_C_TEXT': "Colore del testo",
    'F_C_ACCEPT_BG': "Pulsante di accettazione, sfondo", 'F_C_ACCEPT_TEXT': "Pulsante di accettazione, testo",
    'F_C_EXIT_BORDER': "Pulsante di uscita, bordo", 'F_C_EXIT_TEXT': "Pulsante di uscita, testo",
    'F_COOKIE_DAYS': "Durata del consenso (giorni)",
    'F_COOKIE_DAYS_DESC': "Durata del cookie tecnico wgc_ok. Alla scadenza il modale ricompare.",
    'F_COOKIE_DOMAIN': "Dominio del cookie (facoltativo)",
    'F_COOKIE_DOMAIN_DESC': "Lascia vuoto di norma: il cookie vale solo per il nome host corrente (www.esempio.it e esempio.it sarebbero due cookie diversi). Per condividerlo scrivi il dominio senza www, per esempio esempio.it. Meglio comunque un redirect 301 permanente da un nome all'altro.",
    'F_EXCLUDE': "Percorsi esclusi dal modale",
    'F_EXCLUDE_DESC': "Un percorso per riga, deve iniziare con /. Vale come prefisso: /privacy-policy esclude anche /privacy-policy.html. Di norma si escludono privacy e cookie policy, così si possono leggere prima di accettare.",
    'F_LOG_ENABLED': "Registra il consenso",
    'F_RETENTION': "Conservazione del registro (giorni)",
    'F_RETENTION_DESC': "I record più vecchi vengono cancellati in automatico (pulizia progressiva a ogni registrazione).",
    'F_RATE': "Limite registrazioni per IP ogni 10 minuti",
    'F_RATE_DESC': "Protezione contro gli abusi dell'endpoint di registrazione. Oltre il limite le richieste vengono rifiutate.",
    'F_TRUST_CF': "Il sito è dietro Cloudflare",
    'F_TRUST_CF_DESC': "Sì: l'IP del visitatore (usato solo per l'impronta e per il limite) si legge dall'intestazione CF-Connecting-IP. No: da REMOTE_ADDR. Il limite per IP è affidabile solo se il server accetta soltanto connessioni da Cloudflare.",
    'F_SALT': "Sale aggiuntivo (facoltativo)",
    'F_SALT_DESC': "Si somma al segreto del sito per calcolare l'impronta dell'IP. Cambiandolo, le impronte nuove non sono più confrontabili con le vecchie.",
    'F_DELETE': "Cancella il registro alla disinstallazione",
    'F_DELETE_DESC': "Di default NO: il registro è una prova e sopravvive alla disinstallazione del plugin.",
    'F_REGISTRO': "Registro",
    'REG_INFO': "Le scelte dei visitatori (accettato o rifiutato, data, versione del testo, impronta dell'IP) sono registrate in una tabella del database. Qui non vengono mostrate: si consultano scaricando il CSV.",
    'REG_EXPORT': "Esporta tutto in CSV",
    'REG_EXPORT_HINT': "Per un intervallo aggiungi &from=AAAA-MM-GG&to=AAAA-MM-GG al link. Separatore punto e virgola, date in UTC.",
    'DEF_TITLE': "Contenuti per un pubblico adulto",
    'DEF_BODY': "Questo sito contiene contenuti pensati esclusivamente per un pubblico adulto.\nPer entrare devi avere almeno 18 anni e scegliere liberamente di vedere questi contenuti.\nPremendo il pulsante dichiari di essere maggiorenne e di voler proseguire.",
    'DEF_ACCEPT': "Ho 18 anni o più, entro", 'DEF_EXIT': "Esco",
    'DEF_NOTE': "La tua scelta viene registrata in forma pseudonima, senza indirizzo IP in chiaro, per poter dimostrare che il controllo è stato effettuato.",
    'DEF_PRIVACY': "Informativa sulla privacy",
}

EN = {
    'XML_DESCRIPTION': "Full-page modal (age gate) for sites with content reserved for adults, with a pseudonymous consent register. Texts are editable per language in the plugin settings. Works with page caches: the modal is built in the browser. The plugin stays disabled after first install.",
    'PREFLIGHT_PHP': "WebG Consent requires PHP %s or higher.",
    'PREFLIGHT_JOOMLA': "WebG Consent requires Joomla %s or higher.",
    'POSTINSTALL': "WebG Consent installed and DISABLED. Review texts and parameters, then enable it from the plugin settings.",
    'FS_TEXT': "Text", 'FS_LOOK': "Look", 'FS_BEHAVIOUR': "Behaviour", 'FS_REGISTER': "Consent register",
    'F_VERSION': "Text version (manual part)",
    'F_VERSION_DESC': "Letters, numbers, dash and underscore, max 24 characters. A fingerprint of the texts below is appended automatically: editing a text asks for consent again. Change this field by hand only if you edit language files or want to force a new request.",
    'F_TEXTS': "Texts per language",
    'F_TEXTS_DESC': "One row per language. Visitors get the row for the active page language; if missing, the All languages row; if missing too, the default text from the language files (Italian and English). Empty fields use the text of the language files, which can also be changed in System, Language Overrides (keys PLG_SYSTEM_WEBGCONSENT_DEF_*). If you change an override, change the text version too.",
    'F_LANG': "Language", 'OPT_ALL': "All languages",
    'F_TITLE': "Title", 'F_BODY': "Modal text", 'F_BODY_DESC': "One line = one paragraph. Plain text only, no HTML.",
    'F_BTN_ACCEPT': "Accept button", 'F_BTN_EXIT': "Exit button", 'F_FOOTNOTE': "Note about the register",
    'F_PRIVACY_LABEL': "Privacy link text",
    'F_ROW_PRIVACY_URL': "Privacy policy address (this language)",
    'F_ROW_PRIVACY_URL_DESC': "Optional. If empty the general address below is used.",
    'F_EXIT_URL': "Exit address",
    'F_EXIT_URL_DESC': "Where the exit button leads. Must start with http:// or https://. Choose a neutral site.",
    'F_PRIVACY_URL': "Privacy policy address",
    'F_PRIVACY_URL_DESC': "Site path (starting with /) or a full address. Leave empty to hide the link.",
    'F_C_OVERLAY': "Backdrop colour", 'F_OVERLAY_OPACITY': "Backdrop opacity (50-100)",
    'F_C_PANEL': "Panel colour", 'F_C_TEXT': "Text colour",
    'F_C_ACCEPT_BG': "Accept button, background", 'F_C_ACCEPT_TEXT': "Accept button, text",
    'F_C_EXIT_BORDER': "Exit button, border", 'F_C_EXIT_TEXT': "Exit button, text",
    'F_COOKIE_DAYS': "Consent duration (days)",
    'F_COOKIE_DAYS_DESC': "Lifetime of the technical cookie wgc_ok. When it expires the modal shows again.",
    'F_COOKIE_DOMAIN': "Cookie domain (optional)",
    'F_COOKIE_DOMAIN_DESC': "Normally leave empty: the cookie is valid for the current host name only (www.example.com and example.com would be two different cookies). To share it, enter the domain without www, for example example.com. A permanent 301 redirect from one name to the other is better anyway.",
    'F_EXCLUDE': "Paths excluded from the modal",
    'F_EXCLUDE_DESC': "One path per line, must start with /. Used as a prefix: /privacy-policy also excludes /privacy-policy.html. Privacy and cookie policy are usually excluded so they can be read before accepting.",
    'F_LOG_ENABLED': "Record consent",
    'F_RETENTION': "Register retention (days)",
    'F_RETENTION_DESC': "Older records are deleted automatically (gradual clean-up on each record).",
    'F_RATE': "Records per IP every 10 minutes",
    'F_RATE_DESC': "Abuse protection for the recording endpoint. Over the limit requests are refused.",
    'F_TRUST_CF': "The site is behind Cloudflare",
    'F_TRUST_CF_DESC': "Yes: the visitor IP (used only for the fingerprint and the limit) is read from the CF-Connecting-IP header. No: from REMOTE_ADDR. The per-IP limit is reliable only if the server accepts connections from Cloudflare only.",
    'F_SALT': "Extra salt (optional)",
    'F_SALT_DESC': "Added to the site secret to compute the IP fingerprint. If changed, new fingerprints are no longer comparable with old ones.",
    'F_DELETE': "Delete the register on uninstall",
    'F_DELETE_DESC': "Default NO: the register is evidence and survives uninstalling the plugin.",
    'F_REGISTRO': "Register",
    'REG_INFO': "Visitor choices (accepted or declined, date, text version, IP fingerprint) are recorded in a database table. They are not shown here: download the CSV to review them.",
    'REG_EXPORT': "Export all as CSV",
    'REG_EXPORT_HINT': "For a date range append &from=YYYY-MM-DD&to=YYYY-MM-DD to the link. Semicolon separator, dates in UTC.",
    'DEF_TITLE': "Content for adults only",
    'DEF_BODY': "This site contains content intended exclusively for adults.\nTo enter you must be at least 18 years old and freely choose to view this content.\nBy pressing the button you declare that you are of legal age and wish to continue.",
    'DEF_ACCEPT': "I am 18 or older, enter", 'DEF_EXIT': "Leave",
    'DEF_NOTE': "Your choice is recorded pseudonymously, without your IP address in clear, to be able to show that the check was carried out.",
    'DEF_PRIVACY': "Privacy policy",
}


def q(v):
    return '"' + v.replace('"', '\\"').replace('\n', '\\n') + '"'


def write(lang, d, name):
    sys = 'PLG_SYSTEM_WEBGCONSENT="%s"\nPLG_SYSTEM_WEBGCONSENT_XML_DESCRIPTION=%s\n' % (name, q(d['XML_DESCRIPTION']))
    base = os.path.join(ROOT, lang)
    os.makedirs(base, exist_ok=True)
    with open(os.path.join(base, 'plg_system_webgconsent.sys.ini'), 'w', encoding='utf-8', newline='\n') as f:
        f.write(sys)
    body = sys + ''.join('PLG_SYSTEM_WEBGCONSENT_%s=%s\n' % (k, q(v)) for k, v in d.items() if k != 'XML_DESCRIPTION')
    with open(os.path.join(base, 'plg_system_webgconsent.ini'), 'w', encoding='utf-8', newline='\n') as f:
        f.write(body)


assert set(IT) == set(EN), set(IT) ^ set(EN)
write('it-IT', IT, 'Sistema - WebG Consent')
write('en-GB', EN, 'System - WebG Consent')
print('ok', len(IT))
