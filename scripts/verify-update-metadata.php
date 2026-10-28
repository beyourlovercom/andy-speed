<?php
declare(strict_types=1);
// Verify a detached Ed25519 signature, signed package binding and SHA256.
// Supply public key through an independently trusted channel; never read it from metadata.
// Usage: php scripts/verify-update-metadata.php metadata.json metadata.sig zip base64-public-key
if ($argc !== 5 || !extension_loaded('sodium')) { fwrite(STDERR, "Usage: metadata.json metadata.sig zip base64-public-key\n"); exit(2); }
$pub = base64_decode(trim($argv[4]), true);
$signature = is_file($argv[2]) ? base64_decode(trim((string) file_get_contents($argv[2])), true) : false;
if (!is_string($pub) || strlen($pub) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
    || !is_string($signature) || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
    fwrite(STDERR, "FAIL: untrusted or malformed signature/key\n"); exit(3);
}
$meta = is_file($argv[1]) ? file_get_contents($argv[1]) : false;
if (!is_string($meta) || !sodium_crypto_sign_verify_detached($signature, $meta, $pub)) {
    fwrite(STDERR, "FAIL: detached signature mismatch\n"); exit(4);
}
$data = json_decode($meta, true);
if (!is_array($data) || ($data['schema_version'] ?? null) !== 1
    || !preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', (string)($data['version'] ?? ''))
    || !preg_match('/^[a-f0-9]{40}$/', (string)($data['source_commit'] ?? ''))
    || !preg_match('/^[a-f0-9]{64}$/', (string)($data['sha256'] ?? ''))
    || 'andy-speed-v' . $data['version'] . '.zip' !== ($data['package'] ?? '')
    || basename($argv[3]) !== $data['package'] || !is_file($argv[3])) {
    fwrite(STDERR, "FAIL: signed release contract mismatch\n"); exit(5);
}
if (!hash_equals($data['sha256'], hash_file('sha256', $argv[3]))) {
    fwrite(STDERR, "FAIL: signed ZIP checksum mismatch\n"); exit(6);
}
echo "PASS_ED25519_AND_ZIP_SHA256=" . $data['sha256'] . "\n";
