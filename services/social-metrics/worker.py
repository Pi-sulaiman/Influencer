"""Fetch public profile metrics; nothing is downloaded or written by extractors."""
from datetime import datetime, timezone
from itertools import islice
import json
import math
import os
import re
import sys
import time
from urllib.parse import quote

PLATFORMS = {'instagram', 'youtube', 'tiktok', 'facebook', 'x', 'snapchat', 'linkedin', 'threads'}


def validate_identity(request):
    platform = request.get('platform')
    handle = request.get('handle')
    kind = request.get('kind', 'handle')
    if not isinstance(platform, str) or platform not in PLATFORMS or not isinstance(handle, str):
        raise ValueError('invalid identity')
    pattern = r'[\w.-]{1,100}' if platform == 'youtube' else r'[a-zA-Z0-9_.-]{1,100}'
    if not re.fullmatch(pattern, handle) or kind not in {'handle', 'channel', 'user', 'c', 'in', 'company', 'add', 'p'}:
        raise ValueError('invalid identity')
    if platform == 'instagram' and not re.fullmatch(r'[a-zA-Z0-9_.]{1,30}', handle):
        raise ValueError('invalid Instagram handle')
    if platform in {'instagram', 'tiktok', 'facebook', 'x', 'threads'} and kind != 'handle':
        raise ValueError('invalid kind')
    if platform == 'youtube' and (kind not in {'handle', 'channel', 'user', 'c'} or
                                 (kind == 'channel' and not re.fullmatch(r'UC[a-zA-Z0-9_-]{22}', handle))):
        raise ValueError('invalid YouTube identifier')
    return {'platform': platform, 'handle': handle, 'kind': kind}


def count(value):
    if isinstance(value, bool) or not isinstance(value, (float, int)):
        return None
    if not math.isfinite(value) or value < 0 or value > 2000000000:
        return None
    return int(value)


def summarize(followers, posts, source, description, scanned=None):
    # Missing values are excluded, genuine zero-view videos count in the denominator.
    unique = {}
    for post in posts:
        if post.get('id') and post['id'] not in unique:
            unique[post['id']] = post
    recent = sorted(unique.values(), key=lambda p: p.get('timestamp') or 0, reverse=True)[:12]
    views = [count(post.get('views')) for post in recent]
    views = [value for value in views if value is not None]
    followers = count(followers)
    return {
        'status': 'ok' if followers is not None and views else ('partial' if followers is not None or views else 'unavailable'),
        'followers': followers,
        'average_views': math.floor(sum(views) / len(views) + 0.5) if views else None,
        'sample_size': len(views),
        'posts_scanned': scanned if scanned is not None else len(unique),
        'source': source,
        'sample_description': description,
        'fetched_at': datetime.now(timezone.utc).isoformat(),
        'impressions': None,
    }


def instagram(identity):
    import instaloader

    class NoWaitRateController(instaloader.RateController):
        def sleep(self, secs):
            raise RuntimeError('rate limited')

    loader = instaloader.Instaloader(
        quiet=True, sleep=False, max_connection_attempts=1, request_timeout=6,
        rate_controller=lambda context: NoWaitRateController(context),
        download_pictures=False, download_videos=False, download_video_thumbnails=False,
        download_geotags=False, download_comments=False, save_metadata=False,
    )
    login = os.environ.get('INSTAGRAM_LOGIN', '')
    session = os.environ.get('INSTAGRAM_SESSION_FILE', '')
    if login and session:
        loader.load_session_from_file(login, session)
    profile = instaloader.Profile.from_username(loader.context, identity['handle'])
    followers = profile.followers
    posts = []
    scanned = 0
    start = time.monotonic()
    # Private profile media is deliberately excluded, even if the service account follows it.
    if not profile.is_private:
        try:
            for post in islice(profile.get_posts(), 36):
                scanned += 1
                if post.is_video:
                    try:
                        views = post.video_view_count
                        plays = post.video_play_count if views is None else None
                    except Exception:
                        views, plays = None, None
                    posts.append({'id': str(post.mediaid), 'views': views, 'plays': plays,
                                  'timestamp': post.date_utc.timestamp()})
                if time.monotonic() - start > 10:
                    break
        except Exception:
            # Preserve followers and any available samples when media access is restricted.
            pass
    # Keep the two counters distinct: use plays only when no view counts were returned.
    use_plays = not any(count(post['views']) is not None for post in posts)
    if use_plays:
        posts = [dict(post, views=post.get('plays')) for post in posts]
    metric = 'play' if use_plays else 'view'
    return summarize(followers, posts, 'Instaloader / Instagram',
                     'Latest 12 videos among up to 36 returned feed posts; available ' + metric + ' counts only.', scanned)


class QuietLogger:
    def debug(self, message):
        pass

    def warning(self, message):
        pass

    def error(self, message):
        pass


def video_profile(identity):
    import yt_dlp
    platform, handle, kind = identity['platform'], quote(identity['handle'], safe=''), identity['kind']
    if platform == 'youtube':
        path = '@' + handle if kind == 'handle' else kind + '/' + handle
        target = 'https://www.youtube.com/' + path + '/videos'
        description = 'Latest 12 entries in the Videos tab; Shorts/live streams excluded. Public counts may be rounded.'
    else:
        target = 'https://www.tiktok.com/@' + handle
        description = 'Up to 12 returned profile videos, sorted by date; available view counts only.'
    options = {
        'quiet': True, 'no_warnings': True, 'logger': QuietLogger(),
        'skip_download': True, 'extract_flat': True, 'playlistend': 12,
        'socket_timeout': 6, 'retries': 0, 'extractor_retries': 0,
        'cachedir': False, 'ignoreerrors': True,
    }
    # Only administrator-managed cookie files; never accept uploaded cookie paths or URLs.
    cookie_file = os.environ.get(platform.upper() + '_COOKIE_FILE', '')
    # yt-dlp saves cookie jars on close. Copy into a private temporary file to avoid
    # concurrent writes to the administrator's original cookie file.
    import tempfile
    import shutil
    with tempfile.TemporaryDirectory(prefix='peakilo-metrics-') as temporary:
        if cookie_file:
            destination = os.path.join(temporary, 'cookies.txt')
            shutil.copyfile(cookie_file, destination)
            options['cookiefile'] = destination
        with yt_dlp.YoutubeDL(options) as extractor:
            info = extractor.extract_info(target, download=False) or {}
            followers = info.get('channel_follower_count')
            posts = []
            for entry in islice(info.get('entries') or [], 12):
                if not entry or entry.get('is_live') or entry.get('live_status') in {'is_live', 'is_upcoming'}:
                    continue
                # Flat YouTube tab entries expose public view counts without video downloads.
                # Unknown values stay unknown; we do not make 12 expensive playback requests.
                posts.append({'id': entry.get('id'), 'views': entry.get('view_count'),
                              'timestamp': entry.get('timestamp') or 0})
                if followers is None:
                    followers = entry.get('channel_follower_count')
    return summarize(followers, posts, 'yt-dlp / ' + platform, description)


def lookup(identity):
    identity = validate_identity(identity)
    if identity['platform'] == 'instagram':
        return instagram(identity)
    if identity['platform'] in {'youtube', 'tiktok'}:
        return video_profile(identity)
    return {'status': 'unsupported'}


if __name__ == '__main__':
    # Redirect library chatter to stderr so stdout remains a single JSON document.
    from contextlib import redirect_stdout
    try:
        request = json.loads(sys.stdin.read(2048))
        with redirect_stdout(sys.stderr):
            result = lookup(request)
    except Exception:
        result = {'status': 'unavailable'}
    print(json.dumps(result))
