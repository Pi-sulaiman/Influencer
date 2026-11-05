"""Small authenticated WSGI service for cPanel Passenger (no browser required)."""
import hmac
import json
import os
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parent


def service_secret():
    """SOCIAL_METRICS_TOKEN, else the private file made by make_secret.py."""
    secret = os.environ.get('SOCIAL_METRICS_TOKEN', '')
    if secret:
        return secret
    path = os.environ.get('SOCIAL_METRICS_TOKEN_FILE') or str(Path.home() / 'private' / 'social-metrics.token')
    try:
        return Path(path).read_text(encoding='utf-8').strip()
    except OSError:
        return ''


def application(environ, start_response):
    def respond(status, data):
        body = json.dumps(data).encode('utf-8')
        start_response(status, [('Content-Type', 'application/json'),
                                ('Cache-Control', 'no-store'),
                                ('Content-Length', str(len(body)))])
        return [body]

    secret = service_secret()
    if len(secret) < 32:
        return respond('503 Service Unavailable', {'status': 'unconfigured'})
    if not hmac.compare_digest(environ.get('HTTP_AUTHORIZATION', ''), 'Bearer ' + secret):
        return respond('401 Unauthorized', {'status': 'unauthorized'})
    if environ.get('REQUEST_METHOD') != 'POST':
        return respond('405 Method Not Allowed', {'status': 'invalid'})
    try:
        length = int(environ.get('CONTENT_LENGTH', '0'))
        if length < 1 or length > 2048:
            return respond('413 Payload Too Large', {'status': 'invalid'})
        request = json.loads(environ['wsgi.input'].read(length))
        if not isinstance(request, dict):
            raise ValueError('object required')
        # Validate in both processes; only canonical identifiers cross the boundary.
        from worker import validate_identity
        identity = validate_identity(request)
    except (ValueError, TypeError, KeyError):
        return respond('400 Bad Request', {'status': 'invalid'})

    try:
        # A process deadline bounds library retries/rate-limit sleeps on shared hosting.
        completed = subprocess.run(
            [sys.executable, str(ROOT / 'worker.py')], input=json.dumps(identity),
            capture_output=True, text=True, timeout=23, cwd=str(ROOT), check=True,
        )
        result = json.loads(completed.stdout)
        if not isinstance(result, dict):
            raise ValueError('invalid worker response')
    except (subprocess.SubprocessError, OSError, ValueError):
        # Library errors can contain account/session details; never send them to PHP.
        result = {'status': 'unavailable'}
    return respond('200 OK', result)


if __name__ == '__main__':
    from wsgiref.simple_server import make_server
    with make_server('127.0.0.1', 8765, application) as server:
        print('Social metrics listening on http://127.0.0.1:8765', flush=True)
        server.serve_forever()
