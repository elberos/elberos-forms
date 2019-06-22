<?php

define('MYSQL_SERVER', 'localhost');
define('MYSQL_DB', 'phplistdb');
define('MYSQL_USER', 'phplistdb');
define('MYSQL_PASSWORD', 'password');
define('API_KEY', 'SECRET_API_KEY');

global $pdo;

$pdo = new PDO(
	'mysql:host='.MYSQL_SERVER.';dbname='.MYSQL_DB, MYSQL_USER, MYSQL_PASSWORD, 
	array(
		PDO::ATTR_PERSISTENT => false
	)
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("set names utf8");

function pdo_query($sql, $arr)
{
	global $pdo;
	$st = $pdo->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$st->execute($arr);
	return $st;
}

function pdo_get_one($sql, $arr)
{
    $st = pdo_query($sql, $arr);
    return $st->fetch(PDO::FETCH_ASSOC);
}

function gmdatetime($time=null){
	$d = new \DateTime();
	$d->setTimezone( new DateTimeZone('UTC') );
	
	if ($time !== null) $d->setTimestamp($time);
	else $d->setTimestamp(time());
	
	return $d;
}

function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}


$email = isset($_GET['email']) ? $_GET['email'] : '';
$key = isset($_GET['key']) ? $_GET['key'] : '';
$subscribe_list = 'newsletter';
$subscribe_list_id = isset($_GET['list_id']) ? $_GET['list_id'] : 2;

if ($key != API_KEY)
{
    exit('Hello');
}
if ($email == '')
{
    exit('Email not found');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
{
    exit('Wrong email');
}

$datetime=gmdatetime()->format('Y-m-d H:i:s');
$uniqid=bin2hex(random_bytes(16));
$uuid=gen_uuid();

pdo_query(
    "
	insert phplist_user_user (email, uniqid, uuid, confirmed, blacklisted, entered, htmlemail)
	values (:email, :uniqid, :uuid, :confirmed, :blacklisted, :entered, :htmlemail)
	on duplicate key update
	    email=:email,
	    confirmed=:confirmed,
	    blacklisted=:blacklisted,
	    htmlemail=:htmlemail
    ",
    [
		'email'=>$email,
		'confirmed'=>1,
		'blacklisted'=>0,
		'htmlemail'=>1,
		'entered'=>$datetime,
		'uniqid'=>$uniqid,
		'uuid'=>$uuid,
    ]
);


$item = pdo_get_one('select * from phplist_user_user where email=:email',['email'=>$email]);
if ($item == null)
{
    exit('Email user not found');
}
$user_id = $item['id'];


pdo_query(
    "
	insert phplist_listuser (userid, listid, entered, modified)
	values (:userid, :listid, :entered, :modified)
	on duplicate key update
	    entered=:entered,
	    modified=:modified
    ",
    [
		'userid'=>$user_id,
		'listid'=>$subscribe_list_id,
		'entered'=>$datetime,
		'modified'=>$datetime,
    ]
);


pdo_query(
    "delete from phplist_user_blacklist_data where email=:email",
    [
		'email'=>$email,
    ]
);

pdo_query(
    "delete from phplist_user_blacklist where email=:email",
    [
		'email'=>$email,
    ]
);


echo "Ok";
