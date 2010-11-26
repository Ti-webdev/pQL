#!/usr/bin/env php
<?php
error_reporting(E_ALL|E_STRICT);
chdir(__DIR__);
$files = glob('src/*.php');
sort($files);

$fp = fopen('pql.php', 'w');
fwrite($fp, "<?php");
foreach($files as $file) {
	$code = file_get_contents($file);
	$code = preg_replace('#^\s*<\?(php)?\s*|\?>\s*$#', '', $code);
	fwrite($fp, "\n$code\n\n");
}
fclose($fp);