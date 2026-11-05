"""Create the shared secret for the lookup service, once.

Run it from cPanel > Setup Python App > (this app) > "Execute python script":
    make_secret.py
It writes ~/private/social-metrics.token (readable only by your account) if it
does not exist yet. The PHP site and this service both read that file, so the
secret never has to be copied or pasted. The secret itself is never printed.
"""
import os
import secrets
from pathlib import Path

folder = Path.home() / 'private'
target = folder / 'social-metrics.token'
folder.mkdir(mode=0o700, exist_ok=True)
os.chmod(folder, 0o700)
if target.exists() and len(target.read_text(encoding='utf-8').strip()) >= 32:
    print('Secret already exists at', target)
else:
    fd = os.open(str(target), os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o600)
    with os.fdopen(fd, 'w', encoding='utf-8') as handle:
        handle.write(secrets.token_hex(32))
    os.chmod(target, 0o600)
    print('Secret created at', target)
