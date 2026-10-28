<?php
declare(strict_types=1);
// Detached Ed25519 signing; owner-managed key is required, never generate it in CI.
// Usage: php scripts/sign-update-metadata.php metadata.json metadata.sig base64-public-key
if ($argc !== 4 || !extension_loaded('sodium')) { fwrite(STDERR, "Usage: metadata.json metadata.sig base64-public-key\n"); exit(2); }
$secret64 = getenv('ANDY_SPEED_UPDATE_SIGNING_SECRET');
$secret = is_string($secret64) ? base64_decode(trim($secret64), true) : false;
$pub = base64_decode(trim($argv[3]), true);
unset($secret64);
if (!is_string($secret) || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES || !is_string($pub) || strlen($pub) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
    fwrite(STDERR, "BLOCKED: no valid Andy Speed-specific signing key or public key\n"); exit(3);
}
if (!hash_equals($pub, sodium_crypto_sign_publickey_from_secretkey($secret))) {
    sodium_memzero($secret); fwrite(STDERR, "BLOCKED: signing key does not match approved public key\n"); exit(4);
}
$meta = is_file($argv[1]) ? file_get_contents($argv[1]) : false;
$data = is_string($meta) ? json_decode($meta, true) : null;
if (!is_array($data) || ($data['schema_version'] ?? null) !== 1
    || !preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', (string)($data['version'] ?? ''))
    || !preg_match('/^[a-f0-9]{40}$/', (string)($data['source_commit'] ?? ''))
    || !preg_match('/^[a-f0-9]{64}$/', (string)($data['sha256'] ?? ''))
    || 'andy-speed-v' . $data['version'] . '.zip' !== ($data['package'] ?? '')) {
    sodium_memzero($secret); fwrite(STDERR, "BLOCKED: invalid release metadata\n"); exit(5);
}
$zip = dirname($argv[1]) . '/' . $data['package'];
if (!is_file($zip) || is_link($zip) || !hash_equals($data['sha256'], hash_file('sha256', $zip))) {
    sodium_memzero($secret); fwrite(STDERR, "BLOCKED: ZIP does not match release metadata\n"); exit(6);
}
if (file_exists($argv[2]) || is_link($argv[2])) {
    sodium_memzero($secret); fwrite(STDERR, "BLOCKED: signature already exists\n"); exit(7);
}
$sig = base64_encode(sodium_crypto_sign_detached($meta, $secret)) . "\n";
sodium_memzero($secret);
$handle = @fopen($argv[2], 'x');
if (!$handle) { fwrite(STDERR, "BLOCKED: cannot create detached signature\n"); exit(8); }
if (!@chmod($argv[2], 0600)) {
    fclose($handle); @unlink($argv[2]);
    fwrite(STDERR, "BLOCKED: cannot secure detached signature file\n"); exit(9);
}
$wrote = fwrite($handle, $sig);
fclose($handle);
if ($wrote !== strlen($sig)) { @unlink($argv[2]); fwrite(STDERR, "BLOCKED: partial signature write\n"); exit(9); }
echo "SIGNED_METADATA_SHA256=" . hash('sha256', $meta) . "\n";
