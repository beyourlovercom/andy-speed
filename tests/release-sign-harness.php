<?php
declare(strict_types=1);
// Uses short-lived test keys generated entirely inside this process. Never commit signing keys.
if (!extension_loaded('sodium')) { fwrite(STDERR, "Sodium extension required\n"); exit(2); }
$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/andy-speed-sign-harness-' . bin2hex(random_bytes(8));
if (!mkdir($tmp, 0700)) { exit(2); }
$run = static function(array $parts, array $extra = []) use ($tmp): int {
    $pipes = [];
    $env = array_merge(['PATH' => getenv('PATH') ?: '/usr/bin:/bin'], $extra);
    $p = proc_open($parts, [0 => ['pipe','r'],1 => ['pipe','w'],2 => ['pipe','w']], $pipes, $tmp, $env);
    if (!is_resource($p)) { throw new RuntimeException('proc_open unavailable'); }
    fclose($pipes[0]); stream_get_contents($pipes[1]); fclose($pipes[1]); stream_get_contents($pipes[2]); fclose($pipes[2]);
    return proc_close($p);
};
$check = static function(bool $ok, string $label): void {
    if (!$ok) { throw new RuntimeException('FAIL ' . $label); }
    echo "PASS " . $label . "\n";
};
try {
    $zip = $tmp . '/andy-speed-v0.2.1.zip';
    file_put_contents($zip, "isolated test package bytes\n");
    $meta = $tmp . '/update-metadata.json'; $sig = $tmp . '/update-metadata.sig';
    $document = [
        'schema_version'=>1,'version'=>'0.2.1','package'=>basename($zip),
        'source_commit'=>str_repeat('a', 40),'sha256'=>hash_file('sha256', $zip)
    ];
    $original = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    file_put_contents($meta, $original);
    $pair = sodium_crypto_sign_keypair();
    $secret = sodium_crypto_sign_secretkey($pair);
    $public64 = base64_encode(sodium_crypto_sign_publickey($pair));
    $secret64 = base64_encode($secret);
    sodium_memzero($secret);
    $sign = [PHP_BINARY, $root . '/scripts/sign-update-metadata.php', $meta, $sig, $public64];
    $verify = [PHP_BINARY, $root . '/scripts/verify-update-metadata.php', $meta, $sig, $zip, $public64];
    $check($run($sign) !== 0, 'no independent signing secret -> fail closed');
    $check($run($sign, ['ANDY_SPEED_UPDATE_SIGNING_SECRET'=>$secret64]) === 0, 'ephemeral Ed25519 sign succeeds');
    $check($run($verify) === 0, 'valid signed metadata + ZIP passes');
    $check($run($sign, ['ANDY_SPEED_UPDATE_SIGNING_SECRET'=>$secret64]) !== 0, 'existing signature refuses overwrite');
    $other = sodium_crypto_sign_keypair();
    $wrong = base64_encode(sodium_crypto_sign_publickey($other));
    $check($run([PHP_BINARY, $root . '/scripts/verify-update-metadata.php', $meta, $sig, $zip, $wrong]) !== 0, 'wrong public key rejected');
    file_put_contents($meta, $original . " ");
    $check($run($verify) !== 0, 'altered metadata rejected');
    file_put_contents($meta, $original);
    file_put_contents($zip, "altered ZIP bytes\n");
    $check($run($verify) !== 0, 'altered ZIP rejected');
    $check($run($sign, ['ANDY_SPEED_UPDATE_SIGNING_SECRET'=>base64_encode(random_bytes(64))]) !== 0, 'nonmatching signing identity rejected');
    unset($secret64);
    echo "PASS isolated signing gate; no production key required\n";
} finally {
    foreach (glob($tmp . '/*') as $file) { @unlink($file); }
    @rmdir($tmp);
}
