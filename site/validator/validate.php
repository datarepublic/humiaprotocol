<?php
declare(strict_types=1);

const HUMIA_MAX_BODY = 131072;
const HUMIA_TIMEOUT = 6;
const HUMIA_CONNECT_TIMEOUT = 3;
const HUMIA_USER_AGENT = 'HUMIA-Validator/0.1 (+https://humiaprotocol.org/validator/)';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function add_check(array &$checks, string $level, string $name, string $message): void
{
    $checks[] = [
        'level' => $level,
        'name' => $name,
        'message' => $message,
    ];
}

function is_public_ip(string $ip): bool
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

function resolve_public_host(string $host): array
{
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        throw new RuntimeException('Validator V0 requires a public DNS hostname, not an IP literal.');
    }

    $records = dns_get_record($host, DNS_A | DNS_AAAA);
    if ($records === false || $records === []) {
        throw new RuntimeException('The hostname could not be resolved.');
    }

    $ips = [];
    foreach ($records as $record) {
        if (isset($record['ip'])) {
            $ips[] = $record['ip'];
        }
        if (isset($record['ipv6'])) {
            $ips[] = $record['ipv6'];
        }
    }

    $ips = array_values(array_unique($ips));
    if ($ips === []) {
        throw new RuntimeException('The hostname has no A or AAAA address.');
    }

    foreach ($ips as $ip) {
        if (!is_public_ip($ip)) {
            throw new RuntimeException('The hostname resolves to a private or reserved network address.');
        }
    }

    return $ips;
}

function normalize_origin(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '' || strlen($raw) > 512) {
        throw new InvalidArgumentException('A valid HTTPS origin is required.');
    }

    $parts = parse_url($raw);
    if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
        throw new InvalidArgumentException('HUMIA v0.3 validation requires a public HTTPS origin.');
    }

    if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
        throw new InvalidArgumentException('Credentials, query strings and fragments are not allowed.');
    }

    $path = $parts['path'] ?? '';
    if ($path !== '' && $path !== '/') {
        throw new InvalidArgumentException('Enter only the website origin, without a path.');
    }

    $port = (int)($parts['port'] ?? 443);
    if ($port !== 443) {
        throw new InvalidArgumentException('Validator V0 supports HTTPS port 443 only.');
    }

    $host = strtolower(rtrim((string)$parts['host'], '.'));
    if ($host === '' || preg_match('/[^\x21-\x7e]/', $host)) {
        throw new InvalidArgumentException('Validator V0 requires an ASCII public DNS hostname.');
    }

    $ips = resolve_public_host($host);

    return [
        'origin' => 'https://' . $host,
        'host' => $host,
        'port' => 443,
        'ips' => $ips,
    ];
}

function curl_resolve_entry(string $host, int $port, string $ip): string
{
    if (str_contains($ip, ':')) {
        $ip = '[' . $ip . ']';
    }
    return $host . ':' . $port . ':' . $ip;
}

function fetch_fixed_url(string $url, array $target, string $accept): array
{
    if (!extension_loaded('curl')) {
        throw new RuntimeException('The server PHP cURL extension is unavailable.');
    }

    $body = '';
    $headers = [];
    $tooLarge = false;
    $chosenIp = $target['ips'][0];

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Unable to initialize HTTPS request.');
    }

    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => HUMIA_CONNECT_TIMEOUT,
        CURLOPT_TIMEOUT => HUMIA_TIMEOUT,
        CURLOPT_USERAGENT => HUMIA_USER_AGENT,
        CURLOPT_HTTPHEADER => ['Accept: ' . $accept],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_RESOLVE => [
            curl_resolve_entry($target['host'], $target['port'], $chosenIp),
        ],
        CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$headers): int {
            $length = strlen($line);
            $trimmed = trim($line);
            if ($trimmed !== '' && str_contains($trimmed, ':')) {
                [$name, $value] = explode(':', $trimmed, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
            return $length;
        },
        CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body, &$tooLarge): int {
            if (strlen($body) + strlen($chunk) > HUMIA_MAX_BODY) {
                $tooLarge = true;
                return 0;
            }
            $body .= $chunk;
            return strlen($chunk);
        },
    ]);

    $ok = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $contentType = (string)(curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '');
    $primaryIp = (string)(curl_getinfo($ch, CURLINFO_PRIMARY_IP) ?: '');
    curl_close($ch);

    if ($tooLarge) {
        throw new RuntimeException('Remote response exceeds the validator size limit.');
    }

    if ($ok === false || $errno !== 0) {
        throw new RuntimeException('HTTPS retrieval failed: ' . ($error !== '' ? $error : 'network error'));
    }

    if ($primaryIp !== '' && !is_public_ip($primaryIp)) {
        throw new RuntimeException('The HTTPS connection resolved to a non-public network address.');
    }

    return [
        'status' => $status,
        'content_type' => $contentType,
        'headers' => $headers,
        'body' => $body,
    ];
}

function same_origin_canonical(string $canonical, string $host): bool
{
    $parts = parse_url($canonical);
    if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
        return false;
    }

    if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
        return false;
    }

    $port = (int)($parts['port'] ?? 443);
    $path = $parts['path'] ?? '';

    return strtolower(rtrim((string)$parts['host'], '.')) === $host
        && $port === 443
        && ($path === '' || $path === '/');
}

function validate_allow_deny_object(array &$checks, array $policy, string $objectName, array $members): void
{
    if (!array_key_exists($objectName, $policy)) {
        add_check($checks, 'info', $objectName, 'Optional object is not present.');
        return;
    }

    if (!is_array($policy[$objectName]) || array_is_list($policy[$objectName])) {
        add_check($checks, 'error', $objectName, 'Must be a JSON object.');
        return;
    }

    foreach ($members as $member) {
        if (!array_key_exists($member, $policy[$objectName])) {
            continue;
        }
        if (!in_array($policy[$objectName][$member], ['allow', 'deny'], true)) {
            add_check($checks, 'error', $objectName . '.' . $member, 'Defined v0.3 value must be "allow" or "deny".');
        } else {
            add_check($checks, 'pass', $objectName . '.' . $member, 'Recognized v0.3 value: ' . $policy[$objectName][$member] . '.');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'POST required.']);
}

$rawBody = file_get_contents('php://input', false, null, 0, 2049);
if ($rawBody === false || strlen($rawBody) > 2048) {
    respond(413, ['error' => 'Request body is too large.']);
}

try {
    $request = json_decode($rawBody, true, 16, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    respond(400, ['error' => 'Request body must be valid JSON.']);
}

if (!is_array($request) || !isset($request['origin']) || !is_string($request['origin'])) {
    respond(400, ['error' => 'Request must contain an origin string.']);
}

try {
    $target = normalize_origin($request['origin']);
} catch (InvalidArgumentException $e) {
    respond(400, ['error' => $e->getMessage()]);
} catch (RuntimeException $e) {
    respond(422, ['error' => $e->getMessage()]);
}

$policyUrl = $target['origin'] . '/.well-known/humia.json';
$robotsUrl = $target['origin'] . '/robots.txt';
$checks = [];

try {
    $response = fetch_fixed_url($policyUrl, $target, 'application/json, text/plain;q=0.5');
} catch (RuntimeException $e) {
    add_check($checks, 'error', 'Policy retrieval', $e->getMessage());
    respond(200, [
        'valid' => false,
        'origin' => $target['origin'],
        'policy_url' => $policyUrl,
        'checks' => $checks,
    ]);
}

if ($response['status'] !== 200) {
    $message = 'Expected HTTP 200, received ' . $response['status'] . '.';
    if ($response['status'] >= 300 && $response['status'] < 400) {
        $message .= ' Validator V0 does not follow redirects.';
    }
    add_check($checks, 'error', 'HTTP status', $message);
} else {
    add_check($checks, 'pass', 'HTTP status', 'Policy returned HTTP 200.');
}

$contentType = strtolower(trim(explode(';', $response['content_type'])[0] ?? ''));
if ($contentType === 'application/json') {
    add_check($checks, 'pass', 'Media type', 'Policy is served as application/json.');
} else {
    add_check(
        $checks,
        'warn',
        'Media type',
        $contentType !== ''
            ? 'Policy is served as ' . $contentType . '; application/json is recommended.'
            : 'No Content-Type was detected; application/json is recommended.'
    );
}

$policy = null;
if ($response['status'] === 200) {
    try {
        $policy = json_decode($response['body'], true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($policy) || array_is_list($policy)) {
            throw new UnexpectedValueException('Top-level JSON value must be an object.');
        }
        add_check($checks, 'pass', 'JSON', 'Policy is valid JSON with a top-level object.');
    } catch (JsonException | UnexpectedValueException $e) {
        add_check($checks, 'error', 'JSON', 'Invalid HUMIA JSON: ' . $e->getMessage());
    }
}

if (is_array($policy) && !array_is_list($policy)) {
    if (($policy['protocol'] ?? null) === 'HUMIA') {
        add_check($checks, 'pass', 'protocol', 'Required protocol value is HUMIA.');
    } else {
        add_check($checks, 'error', 'protocol', 'Required value must be the case-sensitive string "HUMIA".');
    }

    if (($policy['version'] ?? null) === '0.3') {
        add_check($checks, 'pass', 'version', 'Policy declares HUMIA Protocol v0.3.');
    } else {
        add_check($checks, 'error', 'version', 'Validator V0 requires version string "0.3".');
    }

    if (!isset($policy['identity']) || !is_array($policy['identity']) || array_is_list($policy['identity'])) {
        add_check($checks, 'error', 'identity', 'Required identity object is missing or invalid.');
    } elseif (!isset($policy['identity']['canonical']) || !is_string($policy['identity']['canonical'])) {
        add_check($checks, 'error', 'identity.canonical', 'Required canonical HTTPS origin is missing.');
    } elseif (same_origin_canonical($policy['identity']['canonical'], $target['host'])) {
        add_check($checks, 'pass', 'identity.canonical', 'Canonical identity matches the validated HTTPS origin.');
    } else {
        add_check($checks, 'error', 'identity.canonical', 'Canonical identity must be the root of the same HTTPS origin.');
    }

    validate_allow_deny_object($checks, $policy, 'access', ['public_content', 'private_api']);
    validate_allow_deny_object($checks, $policy, 'usage', ['user_assistance', 'search_retrieval', 'bulk_crawl', 'training']);

    if (array_key_exists('attribution', $policy)) {
        if (!is_array($policy['attribution']) || array_is_list($policy['attribution'])) {
            add_check($checks, 'error', 'attribution', 'Must be a JSON object.');
        } else {
            foreach (['required', 'canonical_url'] as $member) {
                if (array_key_exists($member, $policy['attribution'])) {
                    if (is_bool($policy['attribution'][$member])) {
                        add_check($checks, 'pass', 'attribution.' . $member, 'Recognized boolean value.');
                    } else {
                        add_check($checks, 'error', 'attribution.' . $member, 'Defined v0.3 value must be boolean.');
                    }
                }
            }
        }
    } else {
        add_check($checks, 'info', 'attribution', 'Optional object is not present.');
    }

    if (array_key_exists('reciprocity', $policy)) {
        if (!is_array($policy['reciprocity']) || array_is_list($policy['reciprocity'])) {
            add_check($checks, 'error', 'reciprocity', 'Must be a JSON object.');
        } elseif (array_key_exists('usage_reporting', $policy['reciprocity'])) {
            if ($policy['reciprocity']['usage_reporting'] === 'requested') {
                add_check($checks, 'pass', 'reciprocity.usage_reporting', 'Recognized v0.3 value: requested.');
            } else {
                add_check($checks, 'error', 'reciprocity.usage_reporting', 'The only defined v0.3 value is "requested". Omit the member when reporting is not requested.');
            }
        }
    } else {
        add_check($checks, 'info', 'reciprocity', 'Optional object is not present.');
    }
}

try {
    $robots = fetch_fixed_url($robotsUrl, $target, 'text/plain, */*;q=0.1');
    if ($robots['status'] === 200) {
        if (preg_match('/^\s*Humia\s*:\s*(\S+)\s*$/mi', $robots['body'], $match)) {
            if (trim($match[1]) === $policyUrl) {
                add_check($checks, 'pass', 'robots.txt discovery', 'Optional Humia: record points to the canonical policy URL.');
            } else {
                add_check($checks, 'warn', 'robots.txt discovery', 'Humia: record does not point to the canonical policy URL expected by v0.3.');
            }
        } else {
            add_check($checks, 'info', 'robots.txt discovery', 'Optional Humia: discovery record is not present.');
        }
    } else {
        add_check($checks, 'info', 'robots.txt discovery', 'robots.txt returned HTTP ' . $robots['status'] . '; the Humia: bridge is optional.');
    }
} catch (RuntimeException $e) {
    add_check($checks, 'info', 'robots.txt discovery', 'Optional robots.txt check was unavailable: ' . $e->getMessage());
}

$valid = true;
foreach ($checks as $check) {
    if ($check['level'] === 'error') {
        $valid = false;
        break;
    }
}

respond(200, [
    'valid' => $valid,
    'origin' => $target['origin'],
    'policy_url' => $policyUrl,
    'checks' => $checks,
]);
