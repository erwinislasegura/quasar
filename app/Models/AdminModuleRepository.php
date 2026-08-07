<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class AdminModuleRepository
{
    public function __construct(private readonly ?PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function rows(string $module): array
    {
        if ($module === 'mediciones') return (new MeasurementRepository($this->pdo))->all();
        if (!$this->pdo instanceof PDO) return [];

        $sql = match ($module) {
            'usuarios' => 'SELECT u.id AS ID, u.nombre AS Nombre, u.email AS Correo, r.nombre AS Rol, IF(u.activo, "Activo", "Inactivo") AS Estado FROM usuarios u JOIN roles r ON r.id=u.rol_id ORDER BY u.nombre',
            'roles' => 'SELECT r.id AS ID, r.nombre AS Nombre, COUNT(DISTINCT u.id) AS Usuarios, COUNT(DISTINCT rp.permiso_id) AS Permisos FROM roles r LEFT JOIN usuarios u ON u.rol_id=r.id LEFT JOIN rol_permiso rp ON rp.rol_id=r.id GROUP BY r.id,r.nombre ORDER BY r.nombre',
            'permisos' => 'SELECT p.id AS ID, p.codigo AS Código, p.descripcion AS Descripción, COUNT(rp.rol_id) AS Roles FROM permisos p LEFT JOIN rol_permiso rp ON rp.permiso_id=p.id GROUP BY p.id,p.codigo,p.descripcion ORDER BY p.codigo',
            'equipos' => 'SELECT id AS ID, nombre AS Equipo, identificador AS Identificador, IF(conectado,"Conectado","Desconectado") AS Estado, COALESCE(DATE_FORMAT(last_seen_at,"%d-%m-%Y %H:%i:%s"),"—") AS `Última conexión` FROM equipos ORDER BY nombre',
            'archivos' => 'SELECT MAX(a.id) AS ID, a.nombre AS Archivo, e.nombre AS Equipo, e.identificador AS Identificador, COUNT(m.id) AS `Mediciones registradas`, "Procesado" AS Estado, DATE_FORMAT(MAX(a.created_at),"%d-%m-%Y %H:%i:%s") AS `Última recepción` FROM archivos a JOIN equipos e ON e.id=a.equipo_id LEFT JOIN mediciones m ON m.archivo_id=a.id GROUP BY e.id, e.nombre, e.identificador, a.nombre ORDER BY MAX(a.created_at) DESC',
            'errores' => 'SELECT id AS ID, origen AS Origen, detalle AS Detalle, estado AS Estado, DATE_FORMAT(created_at,"%d-%m-%Y %H:%i:%s") AS Fecha FROM errores ORDER BY created_at DESC',
            'auditoria' => 'SELECT a.id AS ID, COALESCE(u.nombre,"Sistema") AS Usuario, a.accion AS Acción, COALESCE(a.contexto,"—") AS Contexto, DATE_FORMAT(a.created_at,"%d-%m-%Y %H:%i:%s") AS Fecha FROM auditoria a LEFT JOIN usuarios u ON u.id=a.usuario_id ORDER BY a.created_at DESC',
            default => null,
        };
        return $sql ? $this->pdo->query($sql)->fetchAll() : [];
    }
}
