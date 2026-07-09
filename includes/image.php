<?php
require_once __DIR__ . '/config.php';

/**
 * Downloads a source image and stores locally-optimized JPEG versions
 * (a "detail" size for the game page, a smaller "thumbnail" for the grid),
 * instead of hotlinking/storing the original — which can be several MB for
 * a single box art photo we only ever display at a few hundred pixels wide.
 *
 * @return array{image: ?string, thumbnail: ?string} relative URL paths, or
 *         both null if the source couldn't be downloaded/decoded.
 */
function optimize_and_store_image(string $sourceUrl, int $gameId): array
{
    $raw = fetch_binary($sourceUrl);
    if ($raw === null) {
        return ['image' => null, 'thumbnail' => null];
    }
    return optimize_and_store_image_data($raw, $gameId);
}

/** Same pipeline as optimize_and_store_image(), but for a file already on disk (an upload). */
function optimize_and_store_uploaded_image(string $tmpFilePath, int $gameId): array
{
    $raw = @file_get_contents($tmpFilePath);
    if ($raw === false || $raw === '') {
        return ['image' => null, 'thumbnail' => null];
    }
    return optimize_and_store_image_data($raw, $gameId);
}

/** @return array{image: ?string, thumbnail: ?string} */
function optimize_and_store_image_data(string $raw, int $gameId): array
{
    $source = @imagecreatefromstring($raw);
    if ($source === false) {
        return ['image' => null, 'thumbnail' => null];
    }

    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }

    $unique = $gameId . '-' . substr(md5($raw . microtime()), 0, 8);
    $imagePath = resize_and_save($source, UPLOADS_DIR . "/$unique-detail.jpg", 800);
    $thumbPath = resize_and_save($source, UPLOADS_DIR . "/$unique-thumb.jpg", 400);

    imagedestroy($source);

    return [
        'image' => $imagePath ? UPLOADS_URL . '/' . basename($imagePath) : null,
        'thumbnail' => $thumbPath ? UPLOADS_URL . '/' . basename($thumbPath) : null,
    ];
}

/** Returns the tmp path of a validly-uploaded file in $_FILES[$field], or null if none/invalid. */
function uploaded_image_tmp_path(string $field): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return null;
    }
    return $_FILES[$field]['tmp_name'];
}

/** Downloads a URL into memory, capped in size and time. Returns null on failure. */
function fetch_binary(string $url, int $maxBytes = 15_000_000): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'MyGameCircle/1.0 (self-hosted board game collection app)',
        CURLOPT_RANGE => '0-' . $maxBytes,
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $status < 200 || $status >= 300 || $body === '') {
        return null;
    }
    return $body;
}

/**
 * Resizes $source proportionally so its longest edge is at most $maxEdge,
 * saves as a quality-82 JPEG at $destPath. Never upscales. Returns the
 * saved path, or null on failure.
 */
function resize_and_save(GdImage $source, string $destPath, int $maxEdge): ?string
{
    $width = imagesx($source);
    $height = imagesy($source);
    if ($width <= 0 || $height <= 0) {
        return null;
    }

    $scale = min(1.0, $maxEdge / max($width, $height));
    $newWidth = max(1, (int) round($width * $scale));
    $newHeight = max(1, (int) round($height * $scale));

    $resized = imagecreatetruecolor($newWidth, $newHeight);
    // Flatten transparency onto white (JPEG has no alpha channel).
    $white = imagecolorallocate($resized, 255, 255, 255);
    imagefill($resized, 0, 0, $white);
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $ok = imagejpeg($resized, $destPath, 82);
    imagedestroy($resized);

    return $ok ? $destPath : null;
}
