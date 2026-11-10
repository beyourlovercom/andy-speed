<?php
/** Chinese admin-facing copy contract: no runtime code changes or WordPress writes. */
declare(strict_types=1);
$source = file_get_contents( dirname( __DIR__ ) . '/andy-speed.php' );
function assert_copy( $check, $label ) {
    if ( ! $check ) { fwrite( STDERR, "FAIL: $label\n" ); exit( 1 ); }
    echo "PASS: $label\n";
}
$labels = array(
    'Description: WordPress 性能诊断与可回滚优化插件',
    '<h2>安全运行设置</h2>',
    '<th>运行模式</th>',
    '0 级 — 仅审计（不启用优化）',
    '1 级 — 安全优化（需逐项开启）',
    '<th>紧急关闭优化</th>',
    '<th>Andy Core 诊断功能</th>',
    '<th>订阅脚本延迟加载</th>',
    '<th>BYL V3 样式合并</th>',
    '<th>PairDoll 手机轮播稳定性</th>',
    '<th>PairDoll 手机首屏 WebP</th>',
    '<th>PairDoll Logo 响应式 WebP</th>',
    '保存 Andy Speed 设置',
    '<h2>只读环境信息</h2>',
    '<h2>导出只读诊断报告</h2>',
    '导出只读 JSON 报告',
    '设置已保存。',
    "esc_html__( '设置', 'andy-speed' )",
);
foreach ( $labels as $label ) {
    assert_copy( false !== strpos( $source, $label ), 'Chinese UI label found: ' . $label );
}
foreach ( array( 'Independent opt-in performance gate.', 'Settings saved.', 'Safe runtime settings', 'Keep all performance modifications OFF',
    'Prevent timed auto-rotation', 'Export read-only JSON snapshot', "esc_html__( 'Settings', 'andy-speed' )" ) as $old ) {
    assert_copy( false === strpos( $source, $old ), 'Old English UI copy removed' );
}
foreach ( array( "const OPTION = 'andy_speed_settings_v1'", "const SLUG = 'andy-speed'",
    "name=\"andy_speed[mode]\"", "name=\"andy_speed[emergency_off]\"",
    "name=\"andy_speed[pairdoll_mobile_slider_stability]\"",
    "name=\"andy_speed[pairdoll_mobile_hero_webp]\"",
    "name=\"andy_speed[pairdoll_logo_webp]\"",
    "wp_safe_redirect( admin_url(" ) as $api ) {
    assert_copy( false !== strpos( $source, $api ), 'Persistent settings contract unchanged' );
}
echo "PASS: Andy Speed full Simplified Chinese admin UI contract\n";
