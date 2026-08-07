<?php
declare(strict_types=1);
namespace App\Controllers;
use PDO;
use Throwable;

final class UserController
{
    private function database(): PDO
    {
        $connect=require dirname(__DIR__,2).'/config/database.php';
        $pdo=$connect();
        if(!$pdo instanceof PDO) throw new \RuntimeException('La base de datos no está disponible.');
        $pdo->exec("INSERT INTO roles(nombre) VALUES('Lector Windows'),('Administrador limitado') ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)");
        $initialUsers=[
            ['Lector Windows','Lector Windows 1','lector1@quasartech.cl','$2y$12$ZGLNg0DPcYmP6d8WC5Z/YuuTaoJiCi14/G/XO.QI8sOXEKIYsp412'],
            ['Lector Windows','Lector Windows 2','lector2@quasartech.cl','$2y$12$DRC4kDywW.IxzwuL5XeOCO8ZePkLUDmSZWWe3qv3jYkOnOr88qMtG'],
            ['Administrador limitado','Administrador Operaciones','admin.operaciones@quasartech.cl','$2y$12$l9Ks/ys8KzCj5a9YmkQDEO/FyPSHESxwKbde9/kV1v97DK5ocF3ti'],
        ];
        $seed=$pdo->prepare('INSERT INTO usuarios(rol_id,nombre,email,password_hash,activo) SELECT id,:nombre,:email,:password,1 FROM roles WHERE nombre=:rol ON DUPLICATE KEY UPDATE email=VALUES(email)');
        foreach($initialUsers as[$role,$name,$email,$password])$seed->execute(['rol'=>$role,'nombre'=>$name,'email'=>$email,'password'=>$password]);
        return $pdo;
    }

    public function index(): void
    {
        try {
            $pdo=$this->database();
            $users=$pdo->query('SELECT u.id,u.nombre,u.email,u.activo,u.rol_id,r.nombre AS rol FROM usuarios u JOIN roles r ON r.id=u.rol_id ORDER BY u.nombre')->fetchAll();
            $roles=$pdo->query('SELECT id,nombre FROM roles ORDER BY nombre')->fetchAll();
        } catch(Throwable $error) {
            record_system_error($pdo??null,'Usuarios',$error->getMessage());
            $_SESSION['flashes']['error']='No fue posible consultar los usuarios.';
            $users=[];$roles=[];
        }
        view('usuarios',['title'=>'Usuarios','subtitle'=>'Creación y administración de cuentas de acceso','active'=>'usuarios','users'=>$users,'roles'=>$roles]);
    }

    public function action(): void
    {
        if(!hash_equals($_SESSION['_csrf']??'',(string)($_POST['_csrf']??''))){http_response_code(419);exit('Sesión expirada');}
        $action=(string)($_POST['action']??'');
        if(!in_array($action,['create','update','toggle','delete'],true)){$_SESSION['flashes']['error']='Solicitud no válida.';header('Location: '.url('usuarios'));return;}
        try {
            $pdo=$this->database();
            if($action==='create') {
                [$name,$email,$roleId]=$this->validatedIdentity();
                $password=(string)($_POST['password']??'');
                if(strlen($password)<10) throw new \RuntimeException('La contraseña debe contener al menos 10 caracteres.');
                $statement=$pdo->prepare('INSERT INTO usuarios(rol_id,nombre,email,password_hash,activo) VALUES(:rol,:nombre,:email,:password,1)');
                $statement->execute(['rol'=>$roleId,'nombre'=>$name,'email'=>$email,'password'=>password_hash($password,PASSWORD_DEFAULT)]);
                audit_event($pdo,'Usuario creado',['email'=>$email,'rol_id'=>$roleId]);
                $_SESSION['flashes']['success']='Usuario creado correctamente.';
            } else {
                $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
                if(!$id) throw new \RuntimeException('Usuario no válido.');
                if($action==='update') {
                    [$name,$email,$roleId]=$this->validatedIdentity();
                    $current=$pdo->prepare('SELECT email,rol_id FROM usuarios WHERE id=:id');$current->execute(['id'=>$id]);$currentUser=$current->fetch();
                    if($currentUser && $currentUser['email']===($_SESSION['user']['email']??'') && (int)$currentUser['rol_id']!==$roleId) throw new \RuntimeException('No puede cambiar el rol de su propia sesión.');
                    $password=(string)($_POST['password']??'');
                    if($password!==''&&strlen($password)<10) throw new \RuntimeException('La nueva contraseña debe contener al menos 10 caracteres.');
                    $sql='UPDATE usuarios SET nombre=:nombre,email=:email,rol_id=:rol'.($password!==''?',password_hash=:password':'').' WHERE id=:id';
                    $params=['nombre'=>$name,'email'=>$email,'rol'=>$roleId,'id'=>$id];
                    if($password!=='') $params['password']=password_hash($password,PASSWORD_DEFAULT);
                    $pdo->prepare($sql)->execute($params);
                    audit_event($pdo,'Usuario actualizado',['usuario_id'=>$id,'email'=>$email,'rol_id'=>$roleId,'password_changed'=>$password!=='']);
                    if($currentUser && $currentUser['email']===($_SESSION['user']['email']??'')) { $_SESSION['user']['name']=$name; $_SESSION['user']['email']=$email; }
                    $_SESSION['flashes']['success']='Usuario actualizado correctamente.';
                } elseif($action==='toggle') {
                    $email=$pdo->prepare('SELECT email FROM usuarios WHERE id=:id');$email->execute(['id'=>$id]);
                    if($email->fetchColumn()===($_SESSION['user']['email']??'')) throw new \RuntimeException('No puede desactivar su propia cuenta.');
                    $pdo->prepare('UPDATE usuarios SET activo=IF(activo=1,0,1) WHERE id=:id')->execute(['id'=>$id]);
                    audit_event($pdo,'Estado de usuario cambiado',['usuario_id'=>$id]);
                    $_SESSION['flashes']['success']='Estado del usuario actualizado.';
                } else {
                    $email=$pdo->prepare('SELECT email FROM usuarios WHERE id=:id');$email->execute(['id'=>$id]);
                    if($email->fetchColumn()===($_SESSION['user']['email']??'')) throw new \RuntimeException('No puede eliminar su propia cuenta.');
                    $pdo->beginTransaction();
                    $pdo->prepare('UPDATE auditoria SET usuario_id=NULL WHERE usuario_id=:id')->execute(['id'=>$id]);
                    $pdo->prepare('DELETE FROM usuarios WHERE id=:id')->execute(['id'=>$id]);
                    $pdo->commit();
                    audit_event($pdo,'Usuario eliminado',['usuario_id'=>$id]);
                    $_SESSION['flashes']['success']='Usuario eliminado correctamente.';
                }
            }
        } catch(Throwable $error) {
            if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();
            record_system_error($pdo??null,'Usuarios',$error->getMessage());
            $_SESSION['flashes']['error']=str_contains($error->getMessage(),'Duplicate')?'El correo ya está registrado.':$error->getMessage();
        }
        header('Location: '.url('usuarios'));
    }

    private function validatedIdentity(): array
    {
        $name=trim((string)($_POST['nombre']??''));
        $email=strtolower(trim((string)($_POST['email']??'')));
        $roleId=filter_var($_POST['rol_id']??null,FILTER_VALIDATE_INT);
        if($name===''||mb_strlen($name)>120||!filter_var($email,FILTER_VALIDATE_EMAIL)||!$roleId) throw new \RuntimeException('Complete correctamente nombre, correo y rol.');
        return[$name,$email,$roleId];
    }
}
