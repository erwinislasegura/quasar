<?php
declare(strict_types=1);
namespace App\Controllers;
use PDO;
use Throwable;

final class ErrorController
{
    private function database():PDO{$connect=require dirname(__DIR__,2).'/config/database.php';$pdo=$connect();if(!$pdo instanceof PDO)throw new \RuntimeException('Base de datos no disponible.');return$pdo;}
    public function index():void{try{$pdo=$this->database();$rows=$pdo->query('SELECT id,origen,detalle,estado,created_at FROM errores ORDER BY created_at DESC LIMIT 500')->fetchAll();}catch(Throwable$error){error_log('[Quasar Errores] '.$error->getMessage());$rows=[];}view('errores',['title'=>'Errores','subtitle'=>'Incidencias técnicas registradas por el sistema','active'=>'errores','rows'=>$rows]);}
    public function action():void
    {
        if(!hash_equals($_SESSION['_csrf']??'',(string)($_POST['_csrf']??''))){http_response_code(419);exit('Sesión expirada');}
        $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);$action=(string)($_POST['action']??'');
        try{$pdo=$this->database();if(!$id)throw new \RuntimeException('Error no válido.');if($action==='resolve'||$action==='reopen'){$status=$action==='resolve'?'Resuelto':'Pendiente';$pdo->prepare('UPDATE errores SET estado=:estado WHERE id=:id')->execute(['estado'=>$status,'id'=>$id]);audit_event($pdo,'Estado de error actualizado',['error_id'=>$id,'estado'=>$status]);$_SESSION['flashes']['success']='Estado del error actualizado.';}elseif($action==='delete'){$pdo->prepare('DELETE FROM errores WHERE id=:id')->execute(['id'=>$id]);audit_event($pdo,'Registro de error eliminado',['error_id'=>$id]);$_SESSION['flashes']['success']='Registro de error eliminado.';}else throw new \RuntimeException('Acción no válida.');}
        catch(Throwable$error){record_system_error($pdo??null,'Errores',$error->getMessage());$_SESSION['flashes']['error']=$error->getMessage();}
        header('Location: '.url('errores'));
    }
}
