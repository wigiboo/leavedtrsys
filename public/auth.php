<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Manila');
session_name('upi_elementary_attendance');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
session_start();
require dirname(__DIR__).'/config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function respond(int $status,array $data):never{http_response_code($status);echo json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);exit;}
function csrf_token():string{return $_SESSION['token']??=bin2hex(random_bytes(32));}
try{$db=db();}catch(Throwable $e){error_log('Portal database connection error: '.$e->getMessage());respond(503,['ok'=>false,'message'=>'The school database is unavailable. Check the XAMPP MySQL service and database settings.']);}
if($_SERVER['REQUEST_METHOD']==='GET'){try{$db->query('SELECT id FROM users LIMIT 0');$db->query('SELECT id FROM leave_balances LIMIT 0');respond(200,['ok'=>true,'csrf'=>csrf_token()]);}catch(Throwable $e){respond(503,['ok'=>false,'message'=>'Database is unavailable or the leave-credit migration is missing. Import schema.sql or run the documented migrations.']);}}
if($_SERVER['REQUEST_METHOD']!=='POST')respond(405,['ok'=>false,'message'=>'Use POST to sign in or register.']);
$sent=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'');if($sent===''||!hash_equals(csrf_token(),$sent))respond(419,['ok'=>false,'message'=>'Your sign-in session expired. Reload this page and try again.']);
$payload=json_decode((string)file_get_contents('php://input'),true);if(!is_array($payload))respond(400,['ok'=>false,'message'=>'Invalid request.']);
$action=$payload['action']??'login';$ip=$_SERVER['REMOTE_ADDR']??'unknown';
try{
 if($action==='status')respond(200,['ok'=>true,'database'=>'connected']);
 if($action==='login'){
  $email=strtolower(trim((string)($payload['email']??'')));$password=(string)($payload['password']??'');$loginAs=(string)($payload['login_as']??'employee');
  if(!in_array($loginAs,['employee','admin'],true))respond(422,['ok'=>false,'message'=>'Choose Employee or Admin / Personnel Officer.']);
  $q=$db->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address=? AND attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');$q->execute([$ip]);if((int)$q->fetchColumn()>=8)respond(429,['ok'=>false,'message'=>'Too many sign-in attempts. Try again in 15 minutes.']);
  $q=$db->prepare('SELECT u.*,s.name school_name,s.status school_status FROM users u LEFT JOIN schools s ON s.id=u.school_id WHERE u.email=? AND u.active=1');$q->execute([$email]);$u=$q->fetch();
  if(!$u||!password_verify($password,$u['password_hash'])){$db->prepare('INSERT INTO login_attempts(ip_address) VALUES(?)')->execute([$ip]);respond(401,['ok'=>false,'message'=>'Email or password is incorrect.']);}
  if($u['role']!=='platform_admin'&&$u['school_status']!=='active')respond(403,['ok'=>false,'message'=>'This school workspace is not active yet.']);
  $employeeRoles=['employee','teacher','student'];$adminRoles=['school_admin','personnel_officer','approving_authority','platform_admin'];if(($loginAs==='employee'&&!in_array($u['role'],$employeeRoles,true))||($loginAs==='admin'&&!in_array($u['role'],$adminRoles,true)))respond(403,['ok'=>false,'message'=>'This account is not assigned to the selected login role.']);
  unset($u['password_hash']);session_regenerate_id(true);$_SESSION['user']=$u;$page=$loginAs==='employee'?'leaves':($u['role']==='platform_admin'?'dashboard':'admin');$base=rtrim(dirname((string)($_SERVER['SCRIPT_NAME']??'/public/auth.php')),'/');respond(200,['ok'=>true,'redirect'=>$base.'/index.php?page='.$page]);
 }
 if($action==='register'){
  $name=trim((string)($payload['name']??''));$email=strtolower(trim((string)($payload['email']??'')));$password=(string)($payload['password']??'');$position=trim((string)($payload['position']??'Employee'));
  if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<10)respond(422,['ok'=>false,'message'=>'Enter your name, a valid email, and a password of at least 10 characters.']);
  $q=$db->prepare("SELECT id,name FROM schools WHERE status='active' AND name IN ('Jupi Elementary School','UPI Elementary School') ORDER BY (name='Jupi Elementary School') DESC,id ASC LIMIT 1");$q->execute();$school=$q->fetch();if(!$school)respond(403,['ok'=>false,'message'=>'No active Jupi Elementary School workspace is available. Ask the administrator to activate the school first.']);
  $schoolId=(int)$school['id'];$db->beginTransaction();$q=$db->prepare('INSERT INTO users(school_id,name,email,password_hash,role) VALUES(?,?,?,?,?)');$q->execute([(int)$schoolId,$name,$email,password_hash($password,PASSWORD_DEFAULT),'employee']);$userId=(int)$db->lastInsertId();
  $defaults=['Vacation Leave'=>15,'Sick Leave'=>15,'Special Privilege Leave'=>3,'Wellness Leave'=>5,'Other Applicable Leave Credits'=>0];$b=$db->prepare('INSERT INTO leave_balances(school_id,user_id,category,allocated_days,used_days) VALUES(?,?,?, ?,0)');$l=$db->prepare("INSERT INTO leave_credit_ledger(school_id,user_id,category,entry_type,days_change,balance_after,description) VALUES(?,?,?,'allocation',?,?,?)");foreach($defaults as $category=>$days){$b->execute([(int)$schoolId,$userId,$category,$days]);$l->execute([(int)$schoolId,$userId,$category,$days,$days,'Initial leave allocation']);}
  $db->commit();$u=['id'=>$userId,'school_id'=>(int)$schoolId,'name'=>$name,'email'=>$email,'role'=>'employee','school_name'=>$school['name'],'school_status'=>'active'];session_regenerate_id(true);$_SESSION['user']=$u;$base=rtrim(dirname((string)($_SERVER['SCRIPT_NAME']??'/public/auth.php')),'/');respond(201,['ok'=>true,'redirect'=>$base.'/index.php?page=leaves']);
 }
 respond(422,['ok'=>false,'message'=>'Unsupported account action.']);
}catch(PDOException $e){if($db->inTransaction())$db->rollBack();if($e->getCode()==='23000')respond(409,['ok'=>false,'message'=>'An account with this email already exists.']);error_log('Portal authentication database error: '.$e->getMessage());respond(500,['ok'=>false,'message'=>'The request could not be completed. Check the database setup and try again.']);}catch(Throwable $e){if($db->inTransaction())$db->rollBack();error_log('Portal authentication error: '.$e->getMessage());respond(500,['ok'=>false,'message'=>'The request could not be completed.']);}
