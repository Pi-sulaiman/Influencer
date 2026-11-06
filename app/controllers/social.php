<?php
function social_profile_lookup()
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    try {
        $platform = input('platform');
        if (!is_string($platform) || !isset(enabled_platforms()[$platform])) throw new InvalidArgumentException('Choose an enabled platform.');
        $identity = social_profile_identity($platform, input('profile'));
        // A failed retry releases manual entry, including any previous successful result.
        unset($_SESSION['social_lookups'][$platform]);
        if (!social_rate_limit(ip_hash())) {
            http_response_code(429);
            header('Retry-After: 3600');
            echo json_encode(['status' => 'limited', 'message' => 'Too many lookups. Enter your stats manually or try again later.']);
            return;
        }
        $result = social_lookup($identity);
        if (in_array($result['status'], ['ok', 'partial'], true)) $_SESSION['social_lookups'][$platform] = $result;
        echo json_encode($result);
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(['status' => 'invalid', 'message' => $e->getMessage()]);
    } catch (Throwable $e) {
        // Do not log URLs, tokens, response bodies or private profile/session data.
        error_log('[peakilo] Social metrics lookup failed (' . get_class($e) . ')');
        http_response_code(503);
        echo json_encode(['status' => 'unavailable', 'message' => 'Stats are temporarily unavailable. You can still enter them manually.']);
    }
}
