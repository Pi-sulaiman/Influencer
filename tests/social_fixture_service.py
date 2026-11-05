"""Local-only fake upstream for PHP transport/submission integration tests."""
import json
from wsgiref.simple_server import make_server


def app(env, respond):
    assert env.get('HTTP_AUTHORIZATION') == 'Bearer ' + 't' * 32
    payload = json.loads(env['wsgi.input'].read(int(env['CONTENT_LENGTH'])))
    assert payload['platform'] == 'instagram'
    data = {'status': 'ok', 'followers': 1500, 'average_views': 25,
            'sample_size': 2, 'posts_scanned': 3, 'source': 'Test fixture', 'sample_description': 'Two videos.'}
    body = json.dumps(data).encode()
    respond('200 OK', [('Content-Type', 'application/json'), ('Content-Length', str(len(body)))])
    return [body]


with make_server('127.0.0.1', 8766, app) as server:
    server.serve_forever()
