<?php

declare(strict_types=1);

namespace App\Controllers;

final class ModuleController
{
    /** @param array<string, string> $module */
    public function index(array $module): void
    {
        view('partials/module-table', [
            'title' => $module['title'], 'subtitle' => $module['subtitle'],
            'active' => $module['key'], 'module' => $module,
            'rows' => $this->rows($module['key']),
        ]);
    }

    /** @return list<array<string, string>> */
    private function rows(string $key): array
    {
        return match ($key) {
            'usuarios' => [['Nombre' => 'Administrador local', 'Correo' => 'admin@quasar.local', 'Rol' => 'Administrador', 'Estado' => 'Activo']],
            'roles' => [['Nombre' => 'Administrador', 'Permisos' => 'Todos', 'Usuarios' => '1', 'Estado' => 'Activo']],
            'permisos' => [['Módulo' => 'Dashboard', 'Acción' => 'Visualizar', 'Código' => 'dashboard.view', 'Estado' => 'Activo']],
            'equipos' => [['Equipo' => 'Analizador local 01', 'Dirección' => 'Equipo Windows', 'Última conexión' => date('d-m-Y H:i'), 'Estado' => 'Conectado']],
            'archivos' => [['Archivo' => 'Analisis.txt', 'Registros' => '82', 'Equipo' => 'Analizador local 01', 'Estado' => 'Procesado']],
            'errores' => [['Fecha' => '—', 'Origen' => 'Agente local', 'Detalle' => 'Sin errores pendientes', 'Estado' => 'Resuelto']],
            'auditoria' => [['Fecha' => date('d-m-Y H:i'), 'Usuario' => 'Administrador local', 'Acción' => 'Inicio de sesión', 'Resultado' => 'Correcto']],
            default => [['Parámetro' => 'Ruta de entrada', 'Valor' => 'C:\\SistemaTXT\\Entrada', 'Descripción' => 'Directorio observado por el agente', 'Estado' => 'Activo']],
        };
    }
}
