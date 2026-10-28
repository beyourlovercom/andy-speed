<?php
declare(strict_types=1);
$root = dirname( __DIR__ );
$prefix = $root . '/assets/byl-v3/';
$manifest = json_decode( (string) file_get_contents( $prefix . 'common.bundle.manifest.json' ), true );
if ( ! is_array( $manifest ) ) { fwrite( STDERR, "FAIL manifest invalid\n" ); exit( 1 ); }
$expected = [ 'style.css', 'assets/css/tokens.css', 'assets/css/components.css',
    'assets/css/component-library.css', 'assets/css/shell.css', 'assets/css/motion.css' ];
if ( array_keys( $manifest['sources'] ?? [] ) !== $expected ) { fwrite( STDERR, "FAIL source order\n" ); exit( 1 ); }
if ( ! hash_equals( $manifest['bundle_sha256'], hash_file( 'sha256', $prefix . 'common.bundle.css' ) ) ) {
    fwrite( STDERR, "FAIL immutable bundle checksum\n" ); exit( 1 );
}
$bundle = file_get_contents( $prefix . 'common.bundle.css' );
foreach ( $expected as $name ) {
    if ( substr_count( $bundle, 'Andy Speed BYL V3 source: ' . $name ) !== 1 ) {
        fwrite( STDERR, "FAIL source label $name\n" ); exit( 1 );
    }
}
if ( str_contains( $bundle, "url('../fonts/" ) || str_contains( $bundle, 'url("../fonts/' ) ) {
    fwrite( STDERR, "FAIL unresolved font path\n" ); exit( 1 );
}
if ( substr_count( $bundle, '../../../../themes/beyourlover-v3/assets/fonts/' ) !== 4 ) {
    fwrite( STDERR, "FAIL Poppins font rewrite count\n" ); exit( 1 );
}
echo "PASS deterministic BYL CSS bundle manifest, fingerprint, font URLs, dependency order\n";
