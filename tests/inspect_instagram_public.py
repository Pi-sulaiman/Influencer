"""Inspect only public counter fields to check the PHP fallback's data shape."""
import json
import sys
import instaloader

loader = instaloader.Instaloader(quiet=True, sleep=False, max_connection_attempts=1, request_timeout=8)
page = loader.context.get_page_data('chennaiipl/' + ('reels/' if '--reels' in sys.argv else ''))
print('Query keys:', [list(block) for block in page], flush=True)
for block in page:
    user = block.get('xig_user_by_username')
    if not user:
        continue
    print('Profile fields:', list(user), flush=True)
    print('Counters:', {key: value for key, value in user.items() if key in ['username', 'pk', 'follower_count', 'is_private', 'edge_followed_by']}, flush=True)
    timeline = user.get('polaris_ordered_timeline_connection') or user.get('polaris_clips_connection') or {}
    for edge in (timeline.get('edges') or [])[:3]:
        node = edge.get('node') or {}
        print('Post fields:', list(node), flush=True)
        print('Post counters:', {key: value for key, value in node.items() if 'count' in key or key in ['media_type', 'is_video']}, flush=True)
        if isinstance(node.get('media'), dict):
            print('Media counters:', {key: value for key, value in node['media'].items() if 'count' in key or key in ['media_type', 'is_video']}, flush=True)
