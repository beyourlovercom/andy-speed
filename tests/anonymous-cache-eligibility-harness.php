<?php
declare(strict_types=1);

/*
 * READ-ONLY design fixture for a future host-level cache gate.
 * Does NOT install a cache, touch WordPress options, or alter responses.
 * Default: deny unless a separately verified safe route/variant is explicit.
 */
function dev_cache_candidate(
    string $method,
    string $path,
    array $cookies,
    array $query,
    bool $contains_dynamic_nonce,
    bool $explicit_variant_verified,
    bool $upstream_no_store
): bool {
    if ($method !== 'GET' || $contains_dynamic_nonce || !$explicit_variant_verified || $upstream_no_store) {
        return false;
    }
    if (!in_array($path, ['/', '/shop/'], true)) {
        return false;
    }
    if ($query !== []) {
        return false; // UTM, affiliate, add-to-cart, nonce and preview query strings fail closed
    }
    foreach ($cookies as $name) {
        if (preg_match('/^(wordpress_|wp_|woocommerce_|wp_woocommerce_session_|sbjs_|ac_currency|affiliate|affwp|PHPSESSID|woocommerce_recently_viewed|wpml|pll_|currency|cookieyes|cmplz)/i', (string) $name)) {
            return false;
        }
    }
    return true;
}
$cases = [
    ['anonymous_root_current_no_store', 'GET', '/', [], [], false, true, true, false],
    ['anonymous_root_not_proven', 'GET', '/', [], [], false, false, false, false],
    ['anonymous_root_with_dynamic_nonce', 'GET', '/', [], [], true, true, false, false],
    ['future_eligible_only_after_full_gate', 'GET', '/', [], [], false, true, false, true],
    ['EUR_currency', 'GET', '/', ['ac_currency_code'], [], false, true, false, false],
    ['cart_hash', 'GET', '/', ['woocommerce_cart_hash'], [], false, true, false, false],
    ['cart_session', 'GET', '/', ['wp_woocommerce_session_sample'], [], false, true, false, false],
    ['attribution', 'GET', '/', ['sbjs_current'], [], false, true, false, false],
    ['affiliate_query', 'GET', '/', [], ['ref' => 'test'], false, true, false, false],
    ['coupon_action', 'POST', '/', [], [], false, true, false, false],
    ['cart', 'GET', '/cart/', [], [], false, true, false, false],
    ['checkout', 'GET', '/checkout/', [], [], false, true, false, false],
    ['account', 'GET', '/my-account/', [], [], false, true, false, false],
    ['order_pay', 'GET', '/checkout/order-pay/123/', [], [], false, true, false, false],
    ['webhook', 'GET', '/?wc-api=WC_Gateway_Stripe', [], ['wc-api' => 'x'], false, true, false, false],
];
foreach ($cases as [$label,$method,$path,$cookies,$query,$dynamic,$verified,$no_store,$expected]) {
    $actual = dev_cache_candidate($method,$path,$cookies,$query,$dynamic,$verified,$no_store);
    if ($actual !== $expected) {
        fwrite(STDERR, "FAIL $label expected=".(int)$expected." got=".(int)$actual."\n");
        exit(1);
    }
    echo "PASS $label ".($actual ? 'CANDIDATE_ONLY' : 'BYPASS')."\n";
}
echo "PASS cache guard fail-closed fixture; no live caching enabled\n";
