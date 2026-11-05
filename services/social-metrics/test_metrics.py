import io
import json
import os
import subprocess
import unittest
from unittest.mock import patch, MagicMock

from app import application
from worker import summarize, validate_identity, lookup, instagram, video_profile


class MetricsTests(unittest.TestCase):
    def test_average_excludes_missing_but_keeps_zero_and_deduplicates(self):
        result = summarize(0, [{'id': 'a', 'views': 0}, {'id': 'b', 'views': None},
                               {'id': 'c', 'views': 100}, {'id': 'c', 'views': 900}], 'fixture', 'test')
        self.assertEqual((result['followers'], result['average_views'], result['sample_size']), (0, 50, 2))
        self.assertIsNone(result['impressions'])

    def test_latest_twelve_and_negative_values(self):
        result = summarize(None, [{'id': str(i), 'views': i, 'timestamp': i} for i in range(15)], 'test', 'test')
        self.assertEqual(result['sample_size'], 12)
        self.assertEqual(result['average_views'], 9)
        self.assertEqual(result['status'], 'partial')
        self.assertEqual(summarize(-1, [{'id': 'a', 'views': -2}], '', '')['status'], 'unavailable')

    def test_zero_missing_and_no_posts(self):
        self.assertIsNone(summarize(100, [], '', '')['average_views'])
        self.assertEqual(summarize(100, [], '', '')['status'], 'partial')
        self.assertEqual(summarize(True, [], '', '')['status'], 'unavailable')

    def test_identity_cannot_be_arbitrary_url(self):
        for handle in ['https://127.0.0.1', '../secrets', 'user?next=localhost', 'foo\nbar', '']:
            with self.assertRaises(ValueError):
                validate_identity({'platform': 'instagram', 'handle': handle})
        self.assertEqual(validate_identity({'platform': 'instagram', 'handle': 'name.with.dots'})['handle'], 'name.with.dots')
        self.assertEqual(lookup({'platform': 'linkedin', 'handle': 'person'})['status'], 'unsupported')

    def call_service(self, request=None, authorization='Bearer ' + 's' * 32, method='POST', length=None):
        body = json.dumps(request or {'platform': 'youtube', 'handle': 'GoogleDevelopers'}).encode()
        env = {'REQUEST_METHOD': method, 'CONTENT_LENGTH': str(len(body) if length is None else length),
               'wsgi.input': io.BytesIO(body), 'HTTP_AUTHORIZATION': authorization}
        status = []
        with patch.dict(os.environ, {'SOCIAL_METRICS_TOKEN': 's' * 32}):
            result = b''.join(application(env, lambda code, headers: status.append(code)))
        return status[0], json.loads(result)

    def test_service_authentication_and_size(self):
        with patch('app.subprocess.run') as run:
            self.assertEqual(self.call_service(authorization='')[0], '401 Unauthorized')
            self.assertEqual(self.call_service(method='GET')[0], '405 Method Not Allowed')
            self.assertEqual(self.call_service(length=3000)[0], '413 Payload Too Large')
            self.assertEqual(self.call_service({'platform': 'youtube', 'handle': '../secret'})[0], '400 Bad Request')
            run.assert_not_called()

    def test_timeout_and_worker_errors_are_sanitized(self):
        for error in [subprocess.TimeoutExpired('worker', 23), subprocess.CalledProcessError(1, 'SECRET'), ValueError('COOKIE')]:
            with patch('app.subprocess.run', side_effect=error):
                status, body = self.call_service()
                self.assertEqual((status, body), ('200 OK', {'status': 'unavailable'}))

    def test_worker_is_bounded_and_receives_identifier_only(self):
        with patch('app.subprocess.run', return_value=MagicMock(stdout='{"status":"partial","followers":100}')) as run:
            self.assertEqual(self.call_service()[1]['followers'], 100)
            self.assertEqual(run.call_args.kwargs['timeout'], 23)
            self.assertNotIn('shell', run.call_args.kwargs)
            self.assertEqual(json.loads(run.call_args.kwargs['input'])['handle'], 'GoogleDevelopers')

    def test_instagram_keeps_followers_when_feed_fails(self):
        profile = MagicMock(followers=123, is_private=False)
        profile.get_posts.side_effect = RuntimeError('challenge')
        with patch('instaloader.Instaloader'), patch('instaloader.Profile.from_username', return_value=profile):
            result = instagram({'handle': 'somebody'})
        self.assertEqual(result['followers'], 123)
        self.assertIsNone(result['average_views'])
        self.assertEqual(result['status'], 'partial')

    def test_private_instagram_does_not_fetch_media(self):
        profile = MagicMock(followers=123, is_private=True)
        with patch('instaloader.Instaloader'), patch('instaloader.Profile.from_username', return_value=profile):
            result = instagram({'handle': 'somebody'})
        profile.get_posts.assert_not_called()
        self.assertEqual(result['sample_size'], 0)

    def test_youtube_uses_profile_metadata_without_downloading(self):
        with patch('yt_dlp.YoutubeDL') as ydl:
            client = ydl.return_value.__enter__.return_value
            client.extract_info.return_value = {'channel_follower_count': 500, 'entries': [
                {'id': 'one', 'view_count': 10}, {'id': 'two', 'view_count': 30}, {'id': 'three', 'view_count': None}]}
            result = video_profile({'platform': 'youtube', 'handle': 'Example', 'kind': 'handle'})
            client.extract_info.assert_called_once_with('https://www.youtube.com/@Example/videos', download=False)
            self.assertEqual(result['average_views'], 20)
            self.assertEqual(result['sample_size'], 2)
            self.assertTrue(ydl.call_args.args[0]['skip_download'])


if __name__ == '__main__':
    unittest.main()
