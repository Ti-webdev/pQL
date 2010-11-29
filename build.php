#!/usr/bin/env php
<?php
error_reporting(E_ALL|E_STRICT);
chdir(__DIR__);
$files = glob('src/*.php');
sort($files);

// получаем версию
$version = trim(exec('git tag'));
if (!$version) {
	fwrite(STDERR, 'version not found');
	die(1);
}

$fp = fopen('pql.php', 'w');
fwrite($fp, "<?php");
foreach($files as $file) {
	$code = file_get_contents($file);

	// ставим @version
	$code = preg_replace('#(\s*)\*/\s*((final\s+|abstract\s+)*class|interface)#', '$1* @version '.$version.'$0', $code);

	$code = preg_replace('#^\s*<\?(php)?\s*|\?>\s*$#', '', $code);
	fwrite($fp, "\n$code\n\n");
}
fclose($fp);