<?php
/**
 * Safe image uploads. Files are checked by content (not by extension),
 * renamed randomly and, when GD is available, resized and re-encoded,
 * which also strips any embedded metadata.
 */

function upload_error_message($code)
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: return 'That image is too large. Please use one under ' . (int) cfg('upload_max_mb', 5) . ' MB.';
        case UPLOAD_ERR_PARTIAL:   return 'The upload was interrupted. Please try again.';
        case UPLOAD_ERR_NO_FILE:   return 'Please choose an image.';
        default:                   return 'We could not save that image. Please try another one.';
    }
}

/**
 * @param array  $file    One entry from $_FILES
 * @param string $folder  Sub folder of /uploads (creators, blog, campaigns)
 * @param int    $maxSize Longest edge in px when resizing
 * @return string Relative path like uploads/creators/ab12cd.jpg
 * @throws RuntimeException with a friendly message
 */
function save_image_upload(array $file, $folder, $maxSize = 1200)
{
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Invalid upload.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($file['error']));
    }
    $maxBytes = (int) cfg('upload_max_mb', 5) * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException(upload_error_message(UPLOAD_ERR_INI_SIZE));
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid upload.');
    }

    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$info || !isset($types[$info[2]])) {
        throw new RuntimeException('Please upload a JPG, PNG, WEBP or GIF image.');
    }
    if (function_exists('finfo_open')) {
        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new RuntimeException('Please upload a JPG, PNG, WEBP or GIF image.');
        }
    }

    $folder = preg_replace('/[^a-z0-9_-]/', '', $folder);
    $dir = ROOT . '/uploads/' . $folder . '/' . date('Y/m');
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Upload folder is not writable. Check permissions on /uploads.');
    }
    $name = bin2hex(random_bytes(10));
    $ext = $types[$info[2]];

    $saved = gd_resize($file['tmp_name'], $info, $dir . '/' . $name, $maxSize);
    if ($saved) {
        $relative = 'uploads/' . $folder . '/' . date('Y/m') . '/' . basename($saved);
    } else {
        $target = $dir . '/' . $name . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('We could not save that image. Please try again.');
        }
        @chmod($target, 0644);
        $relative = 'uploads/' . $folder . '/' . date('Y/m') . '/' . $name . '.' . $ext;
    }
    return $relative;
}

/** Resize + re-encode with GD. Returns the saved path, or null if GD is missing. */
function gd_resize($tmp, array $info, $targetNoExt, $maxSize)
{
    if (!function_exists('imagecreatetruecolor')) {
        return null;
    }
    list($w, $h, $type) = $info;
    switch ($type) {
        case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($tmp); break;
        case IMAGETYPE_PNG:  $src = @imagecreatefrompng($tmp); break;
        case IMAGETYPE_WEBP: $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false; break;
        case IMAGETYPE_GIF:  return null; // keep animated GIFs untouched
        default: $src = false;
    }
    if (!$src) {
        return null;
    }
    // Respect phone camera rotation.
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);
        $o = $exif['Orientation'] ?? 1;
        if ($o == 3) { $src = imagerotate($src, 180, 0); }
        if ($o == 6) { $src = imagerotate($src, -90, 0); list($w, $h) = [$h, $w]; }
        if ($o == 8) { $src = imagerotate($src, 90, 0); list($w, $h) = [$h, $w]; }
    }
    $scale = min(1, $maxSize / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    $hasAlpha = $type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP;
    if ($hasAlpha) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, imagesx($src), imagesy($src));

    if (function_exists('imagewebp')) {
        $path = $targetNoExt . '.webp';
        imagewebp($dst, $path, 82);
    } elseif ($hasAlpha) {
        $path = $targetNoExt . '.png';
        imagepng($dst, $path, 6);
    } else {
        $path = $targetNoExt . '.jpg';
        imagejpeg($dst, $path, 84);
    }
    imagedestroy($src);
    imagedestroy($dst);
    @chmod($path, 0644);
    return $path;
}

function delete_upload($relative)
{
    if (!$relative || !str_starts_with($relative, 'uploads/')) {
        return;
    }
    $full = realpath(ROOT . '/' . $relative);
    $uploads = realpath(ROOT . '/uploads');
    if ($full && $uploads && str_starts_with($full, $uploads) && is_file($full)) {
        @unlink($full);
    }
}
