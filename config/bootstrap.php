<?php
declare(strict_types=1);
function cfg(string $key,string $fallback=''):string{
 static $values=null;
 if($values===null){$values=[];$path=dirname(__DIR__).'/.env';if(is_file($path)){foreach(file($path,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line){$line=trim($line);if($line===''||$line[0]==='#'||!str_contains($line,'='))continue;[$k,$v]=explode('=',$line,2);$values[trim($k)]=trim(trim($v)," \"'");}}}
 return getenv($key)!==false?(string)getenv($key):($values[$key]??$fallback);
}
function db():PDO{
 static $pdo=null;if($pdo instanceof PDO)return $pdo;
 $pdo=new PDO('mysql:host='.cfg('DB_HOST','127.0.0.1').';dbname='.cfg('DB_NAME','leave_monitor').';charset=utf8mb4',cfg('DB_USER','root'),cfg('DB_PASSWORD',''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);return $pdo;
}
