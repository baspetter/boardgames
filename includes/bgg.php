<?php
require_once __DIR__ . '/config.php';

const BGG_BASE = 'https://boardgamegeek.com/xmlapi2';

/**
 * Cross-request throttle: ensures at least MIN_INTERVAL seconds pass between
 * outgoing BGG requests, even across separate PHP request processes (which,
 * unlike a persistent Node server, don't share in-memory state). Uses a
 * lock file + flock so concurrent requests serialize safely.
 */
function bgg_throttle(): void
{
    $minInterval = 2.0; // seconds
    $lockFile = sys_get_temp_dir() . '/mygamecircle_bgg_throttle.lock';
    $fp = fopen($lockFile, 'c+');
    if ($fp === false) {
        return; // best-effort; don't block requests if temp dir isn't writable
    }
    flock($fp, LOCK_EX);
    $contents = fread($fp, 64);
    $last = $contents !== false && $contents !== '' ? (float) $contents : 0.0;
    $now = microtime(true);
    $wait = ($last + $minInterval) - $now;
    if ($wait > 0) {
        usleep((int) ($wait * 1_000_000));
    }
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string) microtime(true));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

class BggException extends Exception {}

function bgg_fetch(string $url, int $attempts = 5, int $delaySeconds = 2): string
{
    if (BGG_API_TOKEN === '') {
        throw new BggException(
            'BGG_API_TOKEN is not set. BoardGameGeek requires a registered, approved ' .
            'application with an Authorization: Bearer token ' .
            '(see https://boardgamegeek.com/using_the_xml_api) — register at ' .
            'https://boardgamegeek.com/applications, create a token, and set ' .
            'BGG_API_TOKEN in your .env.'
        );
    }

    for ($i = 0; $i < $attempts; $i++) {
        bgg_throttle();

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/xml,text/xml,*/*',
                'User-Agent: MyGameCircle/1.0 (self-hosted board game collection app)',
                'Authorization: Bearer ' . BGG_API_TOKEN,
            ],
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new BggException("BGG request failed (network error): $curlError");
        }
        if ($status === 202) {
            // BGG queued the request for processing (documented behavior); wait and retry.
            sleep($delaySeconds);
            continue;
        }
        if ($status < 200 || $status >= 300) {
            throw new BggException("BGG request failed ($status): $url");
        }
        return $body;
    }

    throw new BggException("BGG request timed out after $attempts attempts: $url");
}

/** @return array<int, array{bggId:int, name:string, yearPublished:?int}> */
function bgg_search(string $query): array
{
    $url = BGG_BASE . '/search?type=boardgame&query=' . urlencode($query);
    $xml = simplexml_load_string(bgg_fetch($url));
    if ($xml === false) {
        return [];
    }

    $results = [];
    foreach ($xml->item as $item) {
        $bggId = (int) $item['id'];
        if ($bggId <= 0) {
            continue;
        }
        $primary = null;
        foreach ($item->name as $name) {
            if ((string) $name['type'] === 'primary') {
                $primary = (string) $name['value'];
                break;
            }
        }
        $year = isset($item->yearpublished) ? (int) $item->yearpublished['value'] : null;
        $results[] = ['bggId' => $bggId, 'name' => $primary ?? 'Unknown', 'yearPublished' => $year ?: null];
    }
    return $results;
}

/**
 * Picks the player count with the most "Best" votes in BGG's
 * suggested_numplayers poll. Ignores the open-ended "N+" bucket.
 */
function bgg_best_player_count(SimpleXMLElement $item): ?int
{
    $poll = null;
    foreach ($item->poll as $p) {
        if ((string) $p['name'] === 'suggested_numplayers') {
            $poll = $p;
            break;
        }
    }
    if ($poll === null) {
        return null;
    }

    $best = null;
    $bestVotes = 0;
    foreach ($poll->results as $results) {
        $numplayers = (string) $results['numplayers'];
        if (!ctype_digit($numplayers)) {
            continue; // skips the "N+" bucket
        }
        $votes = 0;
        foreach ($results->result as $option) {
            if ((string) $option['value'] === 'Best') {
                $votes = (int) $option['numvotes'];
                break;
            }
        }
        if ($best === null || $votes > $bestVotes) {
            $best = (int) $numplayers;
            $bestVotes = $votes;
        }
    }

    return $bestVotes > 0 ? $best : null;
}

/** @return array<string, mixed> */
function bgg_get_thing(int $bggId): array
{
    $url = BGG_BASE . '/thing?id=' . $bggId . '&stats=1';
    $xml = simplexml_load_string(bgg_fetch($url));
    if ($xml === false || !isset($xml->item)) {
        throw new BggException("Game not found on BGG: $bggId");
    }
    $item = $xml->item;

    $primaryName = null;
    foreach ($item->name as $name) {
        if ((string) $name['type'] === 'primary') {
            $primaryName = (string) $name['value'];
            break;
        }
    }

    $categories = [];
    $mechanics = [];
    $designers = [];
    $artists = [];
    foreach ($item->link as $link) {
        $type = (string) $link['type'];
        $value = (string) $link['value'];
        $id = (int) $link['id'];
        if ($type === 'boardgamecategory') {
            $categories[] = $value;
        } elseif ($type === 'boardgamemechanic') {
            $mechanics[] = $value;
        } elseif ($type === 'boardgamedesigner') {
            $designers[] = ['bggId' => $id, 'name' => $value];
        } elseif ($type === 'boardgameartist') {
            $artists[] = ['bggId' => $id, 'name' => $value];
        }
    }

    $stats = $item->statistics->ratings ?? null;
    $bggRank = null;
    if ($stats !== null) {
        foreach ($stats->ranks->rank as $rank) {
            if ((string) $rank['name'] === 'boardgame') {
                $rankValue = (string) $rank['value'];
                $bggRank = ctype_digit($rankValue) ? (int) $rankValue : null;
                break;
            }
        }
    }

    return [
        'bggId' => $bggId,
        'name' => $primaryName ?? 'Unknown',
        'yearPublished' => isset($item->yearpublished) ? (int) $item->yearpublished['value'] : null,
        'image' => isset($item->image) ? (string) $item->image : null,
        'thumbnail' => isset($item->thumbnail) ? (string) $item->thumbnail : null,
        'description' => isset($item->description) ? trim((string) $item->description) : null,
        'minPlayers' => isset($item->minplayers) ? (int) $item->minplayers['value'] : null,
        'maxPlayers' => isset($item->maxplayers) ? (int) $item->maxplayers['value'] : null,
        'bestPlayers' => bgg_best_player_count($item),
        'playingTime' => isset($item->playingtime) ? (int) $item->playingtime['value'] : null,
        'minPlayTime' => isset($item->minplaytime) ? (int) $item->minplaytime['value'] : null,
        'maxPlayTime' => isset($item->maxplaytime) ? (int) $item->maxplaytime['value'] : null,
        'minAge' => isset($item->minage) ? (int) $item->minage['value'] : null,
        'weight' => $stats !== null && isset($stats->averageweight) ? (float) $stats->averageweight['value'] : null,
        'bggRating' => $stats !== null && isset($stats->average) ? (float) $stats->average['value'] : null,
        'bggRank' => $bggRank,
        'categories' => $categories,
        'mechanics' => $mechanics,
        'designers' => $designers,
        'artists' => $artists,
    ];
}

/**
 * Extracts a BGG id from a pasted boardgamegeek.com URL
 * (e.g. https://boardgamegeek.com/boardgame/13/catan) or a bare numeric id.
 */
function bgg_extract_id(string $input): ?int
{
    $trimmed = trim($input);
    if (ctype_digit($trimmed)) {
        return (int) $trimmed;
    }
    if (preg_match('/boardgame(?:expansion)?\/(\d+)/i', $trimmed, $m)) {
        return (int) $m[1];
    }
    return null;
}
