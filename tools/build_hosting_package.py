"""Build a cPanel release from tracked application files, without private data."""
from pathlib import Path
import hashlib
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[1]
DEST = ROOT / 'dist' / 'school-website-hosting.zip'
DEST.parent.mkdir(exist_ok=True)
files = subprocess.check_output(['git', 'ls-files', '-z'], cwd=ROOT).decode().split('\0')
allowed_roots = {'backend', 'frontend', 'daycare', 'tkit', 'sdit', 'smpit', 'docs', 'tools'}
allowed_files = {'.env.example', '.htaccess', '.user.ini', 'index.php', 'composer.json', 'composer.lock', 'php.ini', 'README.md'}
excluded_suffixes = {'.zip', '.sql', '.log', '.bak', '.pyc'}
count = 0
with zipfile.ZipFile(DEST, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=6) as archive:
    for name in sorted(files):
        path = Path(name)
        if not name or not (ROOT/path).is_file():
            continue
        if name not in allowed_files and path.parts[0] not in allowed_roots:
            continue
        if path.suffix.lower() in excluded_suffixes or any(p in {'private','careers','__pycache__','.git'} for p in path.parts[:-1]):
            continue
        if path.name.startswith('.env') and name != '.env.example':
            continue
        archive.write(ROOT/path, name)
        count += 1
digest = hashlib.file_digest(DEST.open('rb'), 'sha256').hexdigest()
DEST.with_suffix('.zip.sha256').write_text(digest+'  '+DEST.name+'\n')
print(f'{count} files; {DEST.stat().st_size / 1024**2:.1f} MiB; SHA256 {digest}')
print(DEST)
