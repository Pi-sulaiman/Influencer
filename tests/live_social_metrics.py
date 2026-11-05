"""Optional public network smoke check; no logins or secrets are required."""
import json
from pathlib import Path
import sys
import time

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'services' / 'social-metrics'))
from app import application
import io
import os

os.environ['SOCIAL_METRICS_TOKEN'] = 'local-test-only-' + 'x' * 32
for platform, handle in [('youtube', 'GoogleDevelopers'), ('instagram', 'instagram'), ('tiktok', 'tiktok')]:
    start = time.monotonic()
    body = json.dumps({'platform': platform, 'handle': handle}).encode()
    response = b''.join(application({'REQUEST_METHOD': 'POST', 'CONTENT_LENGTH': str(len(body)),
                                    'wsgi.input': io.BytesIO(body), 'HTTP_AUTHORIZATION': 'Bearer ' + os.environ['SOCIAL_METRICS_TOKEN']},
                                   lambda status, headers: None))
    print(platform, response.decode(), 'elapsed=', round(time.monotonic() - start, 1), flush=True)
