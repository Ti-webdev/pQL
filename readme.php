<?php

// configure
function db() {
	static $pQL;
	if (!$pQL) {
		// при первом вызове
		// подключаемся к базе
		$dbh = new PDO('mysql:host=localhost;dbname=test', 'test', '');
		// инициализируем pQL
		$pQL = new pQL($dbh);
	}
	return $pQL->creater();
}

// SQL: INSERT INTO user(login, password, info) VALUES('guest', 'myPassword', 'auto \'quoted\" string!')
$user = db()->user();
$user->login = 'guest';
$user->password = 'myPassword';
$user->info = 'auto \'quoted" string!';
$user->save();
$id = $user->id;

// INSERT inline syntaxis
$id = db()->user()->set('login', 'guest')->set('password', 'myPassword')->save()->id;

// object to string:
echo $user; // 'guest'

// add phone number
$phone = db()->phoneNumber();
$phone->user = $user; // foreign key
$phone->number = 1234567890;
$phone->save();

// find by id
$user = db()->user($id);

// field iterator
foreach(db()->phoneNumber->user->is($user)->number as $number) {
	echo "$number<br />";
}

// hash
foreach(db()->user->id->key()->name as $id=>$name) {
	echo "$id: $name\n";
}

// done

// custom select query
$list =   db()->user->login->in('guset', 'anonimous')
		->db()->phoneNumber->number->like('%678%');
foreach($list as $foundUser) {
	echo "$foundUser<br />";
}

// жадная выборка: привязка переменной
foreach(db()->user->phoneNumber->bind($phone) as $user) {
	echo "$user->name: $phone->number <br />";
}


// жадная выборка: коллекция
// чероновик!
foreach(db()->user->phoneNumber->add() as $collection) {
	echo "{$collection->user->name}: {$collection->phone->number}<br />";
}

// цепочки условий к одному полю:
db()->table->field->not(null)->between(10, 25)->in($vals);

// SQL: ... WHERE field = value
db()->table->field->in($value);

// SQL: ... WHERE field <> value
db()->table->field->not($value);

// SQL ... WHERE field ISNULL
db()->table->field->in(null);

// SQL ... WHERE field ISNOTNULL
db()->table->field->not(null);

// SQL: ... WHERE field IN (val1, val2, val3)
db()->table->field->in($val2, $val2, $val3);

// SQL: ... WHERE field NOT IN (val1, val2, val3)
db()->table->field->not($val2, $val2, $val3);

// SQL: ... WHERE field BETWEEN (lt, gt)
db()->table->field->between($lt, $gt);

// SQL: ... WHERE NOT field BETWEEN (lt, gt)
db()->table->field->not()->between($lt, $gt);

// SQL: ... WHERE field < value
db()->table->field->lt($value);

// SQL: ... WHERE field <= value
db()->table->field->lte($value);

// SQL: ... WHERE field > value
db()->table->field->gt($value);

// SQL: ... WHERE field >= value
db()->table->field->gte($value);

// SQL: ... WHERE field BETWEEN (min, max)
db()->table->field->between($min, $max);

// SQL: ... ORDER BY RANDOM()
db()->table->random();

// SQL: UPDATE table SET field1 = field1 * field2 / 5 WHERE field3 < 6
$t = db()->table;
$affectRows = $t->field1->set("$t->field1 * $t->field2 / 5")->field3->lt(6)->update();

// SQL: DELETE FROM table
$deleteRows = db()->table->delete();

// SQL: DELETE FROM table WHERE id = 67
$deleteRows = db()->table->id->is(67)->delete();

// SQL: ... LIMIT 20, 10.....
db()->table->limit(10)->offset(20);

// SQL: ... ORDER BY field1, filed2 DESC
db()->table->field1->asc()->filed2->desc();

// SQL: SELECT a+b FROM table WHERE c LIKE 'waka%'
// Чероновик 
foreach(db()->table->select('a+b')->c->like('waka') as $sum) {
	echo "a+b: $sum";
}

// SQL: SELECT a+b, c+d FROM table
// Чероновик
foreach(db()->table->select('a+b', 'sum1')->select('c+d', 'sum2') as $collection) {
	echo "a + b = $collection->sum1; c + d = $collection->sum2<br />";
}

// Show SQL
echo db()->table->id->in(1,2,3); // SELECT * FROM table WHERE id IN (1,2,3)

// RIGHT JOIN
// LEFT JOIN
// INNER JOIN
// SQL SELECT * FROM table WHERE field IN ($subsql)
// Чероновик
db()->table->field->in()->sql($subsql);

// SQL: SELECT * FROM user LIMIT 1
$user = db()->user->one();


// get num rows in SELECT
$q = db()->user->name->not(null);
$count = count($q);
foreach($q as $user) {
	echo $user;
}