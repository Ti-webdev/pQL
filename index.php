<?php
$versions = glob('pql-*.php');
natsort($versions);
list($lastVersion) = array_reverse($versions);
$href = '/download'.rtrim(dirname($_SERVER['PHP_SELF']), '/')."/$lastVersion";
header('Content-Type: text/html; charset=UTF-8');
?>
<html>
<head>
	<title>pQL - ORM для PHP</title>
	<meta name="keywords" content="ORM for PHP, ActiveRecord for PHP, pQL" />
</head>

<body>
<h1>pQL</h1>

<p>Дед мороз на носу, а пока предлагаю попробовать на вкус свежый ORM для PHP</p>


<p>
	<a href="<?=$href?>">Скачать <?=$lastVersion?></a> (<?=round(filesize($lastVersion)/1000)?> Кб)<br />
	Примеры использования на <a href="https://github.com/Ti-webdev/pQL/wiki">wiki гитхаба</a><br />
	<a href="https://github.com/Ti-webdev/pQL">Исходный код</a><span title="на гитхабе"> там же</span>
</p>

<p><b>Отличия от других ORM:</b></p>
<ul>
	<li>нет необходимости определять схему - все информация достается из базы данных;</li>
	<li>приятный интерфейс запросов;</li>
	<li>реализация жадной выборки с помощью привязки переменных;</li>
	<li>автоматическое объединение таблиц A и C в запросе, даже если они не связанны между собой напрямую;</li>
</ul>

<!-- Yandex.Metrika -->
<script src="//mc.yandex.ru/metrika/watch.js" type="text/javascript"></script>
<div style="display:none;"><script type="text/javascript">
try { var yaCounter1921399 = new Ya.Metrika(1921399);
yaCounter1921399.clickmap(true);
yaCounter1921399.trackLinks(true);
} catch(e){}
</script></div>
<noscript><div style="position:absolute"><img src="//mc.yandex.ru/watch/1921399" alt="" /></div></noscript>
<!-- /Yandex.Metrika -->
</body>
</html>
