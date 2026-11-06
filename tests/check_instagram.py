"""Read-only reproduction of a public Instagram lookup and the website endpoint."""
import argparse
import json
from pathlib import Path
import re
import sys
import time

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'services' / 'social-metrics'))
import requests
from worker import instagram

parser = argparse.ArgumentParser()
parser.add_argument('handle')
parser.add_argument('--site', default='https://peakilo.com')
args = parser.parse_args()
if not re.fullmatch(r'[A-Za-z0-9_.]{1,30}', args.handle):
    parser.error('Pass an Instagram handle, not a URL.')
session = requests.Session()
try:
    page = session.get(args.site + '/join', timeout=20)
    token = re.search(r'name="_token" value="([^"]+)"', page.text)
    print('Website form:', page.status_code, 'CSRF available:', bool(token), flush=True)
    if token:
        response = session.post(args.site + '/api/social-profile', data={
            '_token': token[1], 'platform': 'instagram',
            'profile': 'https://www.instagram.com/' + args.handle + '/',
        }, headers={'Accept': 'application/json'}, timeout=35)
        print('Website lookup HTTP:', response.status_code, flush=True)
        try:
            data = response.json()
            print(json.dumps({key: data.get(key) for key in ['status', 'message', 'followers', 'average_views', 'sample_size', 'cached', 'reason']}), flush=True)
        except ValueError:
            print('Website returned a non-JSON response.', flush=True)
except requests.RequestException as error:
    print('Website connection:', type(error).__name__, flush=True)
start = time.monotonic()
try:
    print('Local adapter:', json.dumps(instagram({'platform': 'instagram', 'handle': args.handle, 'kind': 'handle'})), flush=True)
except Exception as error:
    print('Local adapter error:', type(error).__name__, flush=True)
print('Adapter elapsed:', round(time.monotonic() - start, 1), flush=True)
