<?php
declare(strict_types=1);
namespace App\Controllers;
use PDO;
use Throwable;

final class RoleController
{
    private function database(): PDO
    {
        $connect=require dirname(__DIR__,2).'/config/database.php';$pdo=$connect();
        if(!$pdo instanceof PDO)throw new \RuntimeException('La base de datos no está disponible.');
        $definitions=['dashboard.view'=>'Ver panel principal','measurements.view'=>'Ver mediciones','files.view'=>'Ver archivos procesados','equipment.manage'=>'Administrar equipos','reader.use'=>'Usar Lector Windows','users.manage'=>'Administrar usuarios','roles.manage'=>'Administrar roles y permisos','errors.view'=>'Ver errores','audit.view'=>'Ver auditoría'];
        $insert=$pdo->prepare('INSERT INTO permisos(codigo,descripcion) VALUES(:codigo,:descripcion) ON DUPLICATE KEY UPDATE descripcion=VALUES(descripcion)');
        foreach($definitions as$code=>$description)$insert->execute(['codigo'=>$code,'descripcion'=>$description]);
        $pdo->exec("DELETE rp FROM rol_permiso rp JOIN permisos p ON p.id=rp.permiso_id WHERE p.codigo='config.view'");
        $pdo->exec("DELETE FROM permisos WHERE codigo='config.view'");
        $pdo->exec("INSERT IGNORE INTO rol_permiso(rol_id,permiso_id) SELECT r.id,p.id FROM roles r CROSS JOIN permisos p WHERE r.nombre='Superadministrador'");
        $pdo->exec("INSERT IGNORE INTO rol_permiso(rol_id,permiso_id) SELECT r.id,p.id FROM roles r JOIN permisos p ON p.codigo='reader.use' WHERE r.nombre='Lector Windows'");
        $pdo->exec("INSERT IGNORE INTO rol_permiso(rol_id,permiso_id) SELECT r.id,p.id FROM roles r CROSS JOIN permisos p WHERE r.nombre='Administrador limitado' AND p.codigo NOT IN('users.manage','roles.manage')");
        return$pdo;
    }
    public function index():void
    {
        try{$pdo=$this->database();$roles=$pdo->query('SELECT r.id,r.nombre,COUNT(DISTINCT u.id) AS usuarios,COUNT(DISTINCT rp.permiso_id) AS permisos FROM roles r LEFT JOIN usuarios u ON u.rol_id=r.id LEFT JOIN rol_permiso rp ON rp.rol_id=r.id GROUP BY r.id,r.nombre ORDER BY r.nombre')->fetchAll();$permissions=$pdo->query('SELECT id,codigo,descripcion FROM permisos ORDER BY id')->fetchAll();$assigned=[];foreach($pdo->query('SELECT rol_id,permiso_id FROM rol_permiso')->fetchAll()as$row)$assigned[(int)$row['rol_id']][]=(int)$row['permiso_id'];}
        catch(Throwable$error){record_system_error($pdo??null,'Roles',$error->getMessage());$_SESSION['flashes']['error']='No fue posible consultar roles y permisos.';$roles=[];$permissions=[];$assigned=[];}
        view('roles',['title'=>'Roles y permisos','subtitle'=>'Control de acceso por módulo y función','active'=>'roles','roles'=>$roles,'permissions'=>$permissions,'assigned'=>$assigned]);
    }
    public function action():void
    {
        if(!hash_equals($_SESSION['_csrf']??'',(string)($_POST['_csrf']??''))){http_response_code(419);exit('Sesión expirada');}
        $action=(string)($_POST['action']??'');
        try{$pdo=$this->database();
            if($action==='create'){$name=$this->validName();$pdo->prepare('INSERT INTO roles(nombre) VALUES(:nombre)')->execute(['nombre'=>$name]);audit_event($pdo,'Rol creado',['nombre'=>$name]);$_SESSION['flashes']['success']='Rol creado correctamente.';}
            else{$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);if(!$id)throw new \RuntimeException('Rol no válido.');$role=$pdo->prepare('SELECT nombre FROM roles WHERE id=:id');$role->execute(['id'=>$id]);$roleName=(string)$role->fetchColumn();if($roleName==='')throw new \RuntimeException('Rol no encontrado.');
                if($action==='update'){$name=$this->validName();if(in_array($roleName,['Superadministrador','Lector Windows','Administrador limitado'],true))throw new \RuntimeException('Los roles principales del sistema no se pueden renombrar.');$pdo->prepare('UPDATE roles SET nombre=:nombre WHERE id=:id')->execute(['nombre'=>$name,'id'=>$id]);audit_event($pdo,'Rol actualizado',['rol_id'=>$id,'nombre'=>$name]);$_SESSION['flashes']['success']='Rol actualizado.';}
                elseif($action==='permissions'){$selected=array_values(array_filter(array_map('intval',$_POST['permissions']??[])));if($roleName==='Superadministrador'){$selected=array_map('intval',$pdo->query('SELECT id FROM permisos')->fetchAll(PDO::FETCH_COLUMN));}$pdo->beginTransaction();$pdo->prepare('DELETE FROM rol_permiso WHERE rol_id=:id')->execute(['id'=>$id]);$grant=$pdo->prepare('INSERT INTO rol_permiso(rol_id,permiso_id) VALUES(:rol,:permiso)');foreach($selected as$permissionId)$grant->execute(['rol'=>$id,'permiso'=>$permissionId]);$pdo->commit();audit_event($pdo,'Permisos de rol actualizados',['rol_id'=>$id,'permisos'=>$selected]);$_SESSION['flashes']['success']='Permisos del rol actualizados. Se aplicarán en el próximo inicio de sesión.';}
                elseif($action==='delete'){if(in_array($roleName,['Superadministrador','Lector Windows','Administrador limitado'],true))throw new \RuntimeException('Los roles principales del sistema no se pueden eliminar.');$used=$pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE rol_id=:id');$used->execute(['id'=>$id]);if((int)$used->fetchColumn()>0)throw new \RuntimeException('No puede eliminar un rol que tiene usuarios asignados.');$pdo->prepare('DELETE FROM rol_permiso WHERE rol_id=:id')->execute(['id'=>$id]);$pdo->prepare('DELETE FROM roles WHERE id=:id')->execute(['id'=>$id]);audit_event($pdo,'Rol eliminado',['rol_id'=>$id,'nombre'=>$roleName]);$_SESSION['flashes']['success']='Rol eliminado.';}
                else throw new \RuntimeException('Acción no válida.');
            }
        }catch(Throwable$error){if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();record_system_error($pdo??null,'Roles',$error->getMessage());$_SESSION['flashes']['error']=str_contains($error->getMessage(),'Duplicate')?'Ya existe un rol con ese nombre.':$error->getMessage();}
        header('Location: '.url('roles'));
    }
    private function validName():string{$name=trim((string)($_POST['nombre']??''));if($name===''||mb_strlen($name)>80)throw new \RuntimeException('Ingrese un nombre de rol válido.');return$name;}
}
