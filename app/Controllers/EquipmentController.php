<?php
declare(strict_types=1);
namespace App\Controllers;
use PDO;
use Throwable;

final class EquipmentController
{
    private function database(): PDO
    {
        $connect = require dirname(__DIR__, 2) . '/config/database.php';
        $pdo = $connect();
        if (!$pdo instanceof PDO) throw new \RuntimeException('La base de datos no está disponible.');
        foreach ([
            'activo' => 'ALTER TABLE equipos ADD activo BOOLEAN NOT NULL DEFAULT TRUE AFTER conectado',
            'refresh_requested_at' => 'ALTER TABLE equipos ADD refresh_requested_at DATETIME NULL',
            'refresh_acknowledged_at' => 'ALTER TABLE equipos ADD refresh_acknowledged_at DATETIME NULL',
        ] as $column => $migration) {
            try {
                $exists = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='equipos' AND column_name=".$pdo->quote($column)." LIMIT 1")->fetchColumn();
                if (!$exists) $pdo->exec($migration);
            } catch (Throwable $error) {
                error_log('[Quasar Equipos Migration] '.$column.': '.$error->getMessage());
            }
        }
        return $pdo;
    }

    public function index(): void
    {
        try {
            $pdo = $this->database();
            $hasActive = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='equipos' AND column_name='activo' LIMIT 1")->fetchColumn();
            $activeExpression = $hasActive ? 'e.activo' : '1';
            $activeGroup = $hasActive ? ',e.activo' : '';
            $rows = $pdo->query("SELECT e.id,e.nombre,e.identificador,$activeExpression AS activo,IF(e.last_seen_at>=DATE_SUB(NOW(),INTERVAL 2 MINUTE),1,0) AS conectado,e.last_seen_at,COUNT(DISTINCT m.id) AS mediciones FROM equipos e LEFT JOIN mediciones m ON m.equipo_id=e.id GROUP BY e.id,e.nombre,e.identificador,e.conectado,e.last_seen_at$activeGroup ORDER BY e.nombre,e.identificador")->fetchAll();
        } catch (Throwable $error) {
            record_system_error($pdo??null,'Equipos',$error->getMessage());
            $_SESSION['flashes']['error'] = 'No fue posible consultar los equipos.';
            $rows = [];
        }
        view('equipos', ['title'=>'Equipos','subtitle'=>'Administración de equipos y recepción de mediciones','active'=>'equipos','rows'=>$rows]);
    }

    public function action(): void
    {
        if (!hash_equals($_SESSION['_csrf'] ?? '', (string)($_POST['_csrf'] ?? ''))) { http_response_code(419); exit('Sesión expirada'); }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $action = (string)($_POST['action'] ?? '');
        if (!$id || !in_array($action, ['update','toggle','refresh','delete'], true)) { $_SESSION['flashes']['error']='Solicitud no válida.'; header('Location: '.url('equipos')); return; }
        try {
            $pdo = $this->database();
            if ($action === 'update') {
                $name = trim((string)($_POST['nombre'] ?? ''));
                $identifier = trim((string)($_POST['identificador'] ?? ''));
                if ($name==='' || $identifier==='' || mb_strlen($name)>120 || mb_strlen($identifier)>120 || !preg_match('/^[A-Za-z0-9._-]+$/',$identifier)) throw new \RuntimeException('Nombre o identificador no válido.');
                $statement=$pdo->prepare('UPDATE equipos SET nombre=:nombre,identificador=:identificador WHERE id=:id');
                $statement->execute(['nombre'=>$name,'identificador'=>$identifier,'id'=>$id]);
                audit_event($pdo,'Equipo actualizado',['equipo_id'=>$id,'identificador'=>$identifier]);
                $_SESSION['flashes']['success']='Equipo actualizado. Use el mismo identificador en el lector de Windows.';
            } elseif ($action === 'toggle') {
                $pdo->prepare('UPDATE equipos SET activo=IF(activo=1,0,1),conectado=0 WHERE id=:id')->execute(['id'=>$id]);
                audit_event($pdo,'Recepción de equipo pausada o reanudada',['equipo_id'=>$id]);
                $_SESSION['flashes']['success']='Estado de recepción actualizado.';
            } elseif ($action === 'refresh') {
                $pdo->prepare('UPDATE equipos SET refresh_requested_at=NOW() WHERE id=:id AND activo=1')->execute(['id'=>$id]);
                audit_event($pdo,'Actualización remota solicitada',['equipo_id'=>$id]);
                $_SESSION['flashes']['success']='Orden de actualización enviada. El lector la recibirá en menos de 30 segundos.';
            } else {
                $pdo->beginTransaction();
                $pdo->prepare('DELETE FROM mediciones WHERE equipo_id=:id')->execute(['id'=>$id]);
                $pdo->prepare('DELETE FROM archivos WHERE equipo_id=:id')->execute(['id'=>$id]);
                $pdo->prepare('DELETE FROM equipos WHERE id=:id')->execute(['id'=>$id]);
                $pdo->commit();
                audit_event($pdo,'Equipo eliminado',['equipo_id'=>$id]);
                $_SESSION['flashes']['success']='Equipo y sus datos asociados fueron eliminados.';
            }
        } catch (Throwable $error) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
            record_system_error($pdo??null,'Equipos',$error->getMessage());
            $_SESSION['flashes']['error']=str_contains($error->getMessage(),'Duplicate') ? 'Ese identificador ya pertenece a otro equipo.' : $error->getMessage();
        }
        header('Location: '.url('equipos'));
    }
}
