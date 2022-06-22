<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
echo dirname(__DIR__);

require_once dirname(__DIR__) . '/vendor/autoload.php';
$logsPath = "/var/www/html/composer/log/messages.log";
$loader = new FilesystemLoader(dirname(__DIR__) . "/template/");
$log = new Logger('log');
$loggerHandler = new StreamHandler($logsPath, Logger::INFO);
$log->pushHandler($loggerHandler);
$twig = new Environment($loader);
echo $twig->render("main.html.twig");

$users = [
    "admin" =>"admin",
    "guest"=>"123"
];
$rows=[];

if (isset($_GET['logs'])) {
    echo("Логи: ");
    $file = file_get_contents("/var/www/html/composer/log/messages.log");
    $Nfile = "\n$file";
    $ArrFile = array($Nfile);
    echo '<pre>';
    print_r($ArrFile);
    echo '</pre>';
}

if (isset($_GET['bd'])) {
    echo("Элементы БД: ");
    $dbh = new PDO('mysql:host=localhost;dbname=msgDB', 'delloys', 'delloyspass');
    $rows = $dbh->query('SELECT * from msgs');
    foreach($rows as $row) {
        echo nl2br($row['login'] . ' ' .$row['msg'] . "\r\n");
    }
}

function add_msg($login, $message, $password)
{
    if ($message !== '') {
        $info = json_decode(file_get_contents("messages.json"), true);
        $info['messages'] [] = ['date' => date('d.m.y h:i:s'), 'user' => $login, 'message' => $message];
        file_put_contents("messages.json", json_encode($info));

        try {
            $dbh = new PDO('mysql:host=localhost;dbname=msgDB', 'delloys', 'delloyspass');
            $info = $dbh->prepare("insert into msgs(login,pass,msg) values ('$login  ',' $password ',' $message ')");
            $info->execute();
            //$rows = $dbh->query('SELECT * from msgs');
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }

    }
}

function print_msgs(){
    $info = json_decode(file_get_contents("messages.json"),false);
    foreach ($info->messages as $mes){
        echo '<p font-weight: bold">' . $mes->date . ' | ' . $mes->user . ' say:';
        echo '<p style="padding-left: 125px">' . $mes->message;
    }
}

if ((string)$_GET['login'] !== '' && isset($_GET['login']) && isset($_GET['password']) && isset($_GET['message']))
    if ($users[(string)$_GET['login']] == (string)$_GET['password']) {
    $login = (string)$_GET['login'];
    $pass = (string)$_GET['password'];
    $msg = (string)$_GET['message'];
    add_msg($login, $msg, $pass, $users);
    $log->info('user send message',['user' => $login, 'send' => $msg]);
}
else {
    echo "<script> alert(\"Неверный пароль\") </script>";
    $log->error('wrong password');
}

print_msgs();
?>

