"""Controllo statico locale, ispirato alle regole del JED Checker (PH1 intestazione GPL, PH2 _JEXEC,
PH3 licenza nel manifest, US1 update server, codifica/offuscamento, error_reporting(0)).
NON e' il checker ufficiale: e' un equivalente per i punti che il checker e la checklist JED dichiarano.
Uso: python tools/check_jed.py [percorso dello zip]"""
import os
import re
import sys
import zipfile
import xml.dom.minidom

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..')
ZIP = sys.argv[1] if len(sys.argv) > 1 else os.path.join(ROOT, 'dist', 'plg_system_webgconsent-1.0.0.zip')

FORBIDDEN = [
    r'\beval\s*\(', r'\bbase64_decode\s*\(', r'\bgzinflate\s*\(', r'\bgzuncompress\s*\(', r'\bstr_rot13\s*\(',
    r'\berror_reporting\s*\(\s*0', r'\bcreate_function\s*\(', r'\bassert\s*\(', r'\bpreg_replace\s*\(\s*[\'"].*/e[\'"]',
    r'\bshell_exec\s*\(', r'\bexec\s*\(', r'\bsystem\s*\(', r'\bpassthru\s*\(', r'\bpopen\s*\(', r'\bproc_open\s*\(',
    r'\bcurl_init\s*\(', r'\bfsockopen\s*\(', r'\bunserialize\s*\(', r'\bextract\s*\(', r'\bmail\s*\(',
    r'\bini_set\s*\(', r'\bset_time_limit\s*\(', r'\bphpinfo\s*\(',
]

def php_strings(src):
    """Estrae i letterali stringa PHP (apici singoli e doppi) ignorando commenti, heredoc e nowdoc."""
    out, i, n = [], 0, len(src)
    while i < n:
        c = src[i]
        two = src[i:i + 2]
        if two == '//' or c == '#':
            if src[i:i + 2] == '#[':
                i += 2
                continue
            j = src.find('\n', i)
            i = n if j < 0 else j
        elif two == '/*':
            j = src.find('*/', i + 2)
            i = n if j < 0 else j + 2
        elif src[i:i + 3] == '<<<':
            m = re.match(r"<<<\s*['\"]?([A-Za-z_]+)['\"]?\r?\n", src[i:])
            if m:
                end = re.search(r'\n\s*' + m.group(1) + r'\b', src[i + m.end():])
                i = n if not end else i + m.end() + end.end()
            else:
                i += 3
        elif c in "'\"":
            q, j, buf = c, i + 1, []
            while j < n and src[j] != q:
                if src[j] == '\\' and j + 1 < n:
                    buf.append(src[j:j + 2])
                    j += 2
                else:
                    buf.append(src[j])
                    j += 1
            out.append(''.join(buf))
            i = j + 1
        else:
            i += 1
    return out


problems = []
ok = []


def check(cond, label, detail=''):
    (ok if cond else problems).append(label + (' | ' + detail if detail and not cond else ''))


z = zipfile.ZipFile(ZIP)
names = z.namelist()
print('Pacchetto:', os.path.basename(ZIP), len(names), 'file')

# file superflui
extra = [n for n in names if re.search(r'(\.git|\.DS_Store|Thumbs\.db|\.map$|\.bak$|\.orig$|\.log$|node_modules|\.zip$|\.md$)', n)]
check(not extra, 'nessun file superfluo nello zip', ', '.join(extra))
check(all('\\' not in n for n in names), 'percorsi con / nello zip')

manifest = [n for n in names if n.endswith('.xml') and '/' not in n]
check(len(manifest) == 1, 'un solo manifest alla radice')
mx = z.read(manifest[0]).decode('utf-8')
dom = xml.dom.minidom.parseString(mx)
ext = dom.documentElement


def tag(name):
    n = ext.getElementsByTagName(name)
    return n[0].firstChild.nodeValue.strip() if n and n[0].firstChild else ''


for t in ['name', 'author', 'creationDate', 'copyright', 'license', 'authorEmail', 'authorUrl', 'version', 'description']:
    check(tag(t) != '', 'manifest: <%s> presente' % t)
check('GNU General Public License' in tag('license'), 'PH3 manifest: licenza GPL dichiarata')
check(ext.getAttribute('type') == 'plugin' and ext.getAttribute('group') == 'system', 'manifest: plugin di gruppo system')
check(ext.getElementsByTagName('languages') != [], 'manifest: file lingua dichiarati')
check(len(ext.getElementsByTagName('updateservers')) == 1 and 'raw.githubusercontent.com/WebG-it/webg-consent' in ext.getElementsByTagName('updateservers')[0].toxml(), 'US1: server di aggiornamento dichiarato (collection del repository)')

# file lingua e stringhe usate
ini = {}
for n in names:
    if n.endswith('.ini'):
        keys = {}
        for line in z.read(n).decode('utf-8').splitlines():
            m = re.match(r'^([A-Z0-9_]+)="(.*)"$', line)
            if m:
                keys[m.group(1)] = m.group(2)
            elif line.strip():
                problems.append('lingua: riga non valida in %s: %s' % (n, line[:60]))
        ini[n] = keys
langs = {}
for n, k in ini.items():
    langs.setdefault(n.split('/')[1], set()).update(k.keys())
check(len(set(frozenset(v) for v in langs.values())) == 1, 'lingue allineate (stesse chiavi in it-IT e en-GB)', str({k: len(v) for k, v in langs.items()}))
allkeys = set().union(*langs.values())
used_all = set(re.findall(r'PLG_SYSTEM_WEBGCONSENT[A-Z0-9_]*', mx))
for n in names:
    if n.endswith('.php'):
        used_all |= set(re.findall(r'PLG_SYSTEM_WEBGCONSENT[A-Z0-9_]*', z.read(n).decode('utf-8')))
for lg, keys in langs.items():
    miss = sorted(k for k in used_all if k not in keys and k != 'PLG_SYSTEM_WEBGCONSENT_')
    check(not miss, 'chiavi usate e definite in ' + lg, ', '.join(miss))
    unused = sorted(k for k in keys if k not in used_all and not k.endswith('_XML_DESCRIPTION') and k != 'PLG_SYSTEM_WEBGCONSENT')
    check(not unused, 'nessuna chiave definita e mai usata in ' + lg, ', '.join(unused))
used = set(re.findall(r'PLG_SYSTEM_WEBGCONSENT[A-Z0-9_]*', mx))
for n in names:
    if n.endswith('.php'):
        used |= set(re.findall(r'PLG_SYSTEM_WEBGCONSENT[A-Z0-9_]*', z.read(n).decode('utf-8')))
missing = sorted(k for k in used if k not in allkeys)
check(not missing, 'tutte le stringhe usate esistono nei file lingua', ', '.join(missing))

# PHP
for n in names:
    if not n.endswith('.php'):
        continue
    src = z.read(n).decode('utf-8')
    head = src[:900]
    check('@license' in head and 'GNU General Public License' in head, 'PH1 intestazione GPL: ' + n)
    check(re.search(r"defined\('_JEXEC'\)\s*or\s*die", src) is not None, 'PH2 _JEXEC: ' + n)
    for rx in FORBIDDEN:
        hit = re.search(rx, src)
        check(not hit, 'nessuna funzione vietata %s in %s' % (rx, n), hit.group(0) if hit else '')
    ext_urls = [u for u in re.findall(r"https?://[^\s'\"<>)]+", src) if not re.search(r'(gnu\.org|www\.webg\.it|example|www\.google\.com|php\.net)', u)]
    check(not ext_urls, 'nessun URL esterno nel codice: ' + n, ', '.join(ext_urls))
    longest = max(len(l) for l in src.splitlines())
    check(longest < 400, 'nessuna riga illeggibile (max %d caratteri): %s' % (longest, n))
    check('base64' not in src.lower(), 'nessuna codifica base64: ' + n)
    check('AgeGate' not in src and 'agegate' not in src.lower(), 'nessun residuo del vecchio nome: ' + n)
    # stringhe visibili hardcoded: letterali con spazi e lettere accentate o parole italiane comuni, fuori dai commenti
    it_words = r'\b(il|lo|la|le|gli|per|non|con|di|che|del|della|nel|una|uno|sono|questo|questa|richiesto|superiore|entro|esco|disabilitato|installato|controlla|abilitalo)\b'
    bad = []
    for lit in php_strings(src):
        if not lit or lit.startswith('PLG_') or ' ' not in lit.strip():
            continue
        if re.search(r'[àèéìòù]', lit, re.I) or (len(re.findall(r'[A-Za-z]{2,}', lit)) >= 3 and re.search(it_words, lit, re.I) and not re.search(r'(SELECT|DELETE|UPDATE|FROM|WHERE|CREATE)', lit)):
            bad.append(lit[:50])
    check(not bad, 'nessuna stringa visibile hardcoded in italiano: ' + n, ' ; '.join(bad[:5]))
    if n.endswith('Assets.php'):
        for rx, label in [(r'innerHTML', 'innerHTML'), (r'outerHTML', 'outerHTML'), (r'insertAdjacentHTML', 'insertAdjacentHTML'),
                          (r'document\.write', 'document.write'), (r'\.exec\(', 'exec()'), (r'new\s+Function', 'new Function'),
                          (r'setTimeout\s*\(\s*[\'"]', 'setTimeout con stringa')]:
            check(re.search(rx, src) is None, 'JS: nessun ' + label + ' in ' + n)

# nessun file non previsto (solo php, xml, sql, ini|txt)$
bad = [n for n in names if not re.search(r'\.(php|xml|sql|ini|txt)$', n)]
check(not bad, 'solo file php, xml, sql, ini, txt nello zip', ', '.join(bad))

print('\nOK (%d):' % len(ok))
print('\n'.join('  + ' + o for o in ok[:200]))
print('\nPROBLEMI (%d):' % len(problems))
print('\n'.join('  - ' + p for p in problems) or '  nessuno')
sys.exit(1 if problems else 0)
