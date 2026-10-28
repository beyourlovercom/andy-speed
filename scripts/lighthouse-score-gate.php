<?php
declare(strict_types=1);
// Local synthetic gate only; does not certify a GTmetrix result.
// Usage: php scripts/lighthouse-score-gate.php desktop|mobile report1.json report2.json report3.json
if ($argc < 5 || !in_array($argv[1], ['desktop','mobile'], true)) {
    fwrite(STDERR, "Usage: php scripts/lighthouse-score-gate.php desktop|mobile first.json second.json third.json [more.json ...]\n");
    exit(2);
}
$profile=$argv[1];$scores=[];$lcps=[];$tbts=[];
for ($i=2; $i<$argc; $i++) {
    $j=json_decode((string) @file_get_contents($argv[$i]),true);
    if (!is_array($j) || !isset($j['categories']['performance']['score'],$j['audits']['largest-contentful-paint']['numericValue'],$j['audits']['total-blocking-time']['numericValue'])) {
        fwrite(STDERR,"INVALID_REPORT: {$argv[$i]}\n");exit(2);
    }
    $scores[]=(float) $j['categories']['performance']['score']*100;
    $lcps[]=(float) $j['audits']['largest-contentful-paint']['numericValue'];
    $tbts[]=(float) $j['audits']['total-blocking-time']['numericValue'];
}
$median=static function(array $v):float {sort($v,SORT_NUMERIC);$n=count($v);return $n%2 ? (float)$v[intdiv($n,2)] : ($v[$n/2-1]+$v[$n/2])/2;};
$s=$median($scores);$l=$median($lcps);$t=$median($tbts);
$pass=$s>=90 && ($profile==='desktop' ? $t<=150 : $l<2500);
printf("LOCAL_LIGHTHOUSE_%s %s | runs=%d median_score=%.1f median_LCP=%.0fms median_TBT=%.0fms\n",strtoupper($profile),$pass?'PASS':'FAIL',count($scores),$s,$l,$t);
echo "NOTE: This is NOT a GTmetrix Grade A certification; GTmetrix must be retested separately.\n";
exit($pass?0:1);
