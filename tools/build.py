"""Costruisce dist/plg_system_webgconsent-<versione>.zip dal manifest. Date interne fisse: stesso contenuto, stesso hash."""
import zipfile, os, re, hashlib
root = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..')
src = os.path.join(root, 'plugins', 'plg_system_webgconsent')
ver = re.search(r'<version>([^<]+)</version>', open(os.path.join(src, 'webgconsent.xml'), encoding='utf-8').read()).group(1)
out = os.path.join(root, 'dist')
os.makedirs(out, exist_ok=True)
z = os.path.join(out, 'plg_system_webgconsent-%s.zip' % ver)
files = []
for d, _, fs in os.walk(src):
    for f in fs:
        full = os.path.join(d, f)
        files.append((os.path.relpath(full, src).replace(os.sep, '/'), full))
with zipfile.ZipFile(z, 'w', zipfile.ZIP_DEFLATED) as zf:
    for rel, full in sorted(files):
        zi = zipfile.ZipInfo(rel, (2026, 10, 26, 0, 0, 0))
        zi.compress_type = zipfile.ZIP_DEFLATED
        zi.external_attr = 0o644 << 16
        zf.writestr(zi, open(full, 'rb').read())
print(z, os.path.getsize(z), hashlib.sha256(open(z, 'rb').read()).hexdigest())
