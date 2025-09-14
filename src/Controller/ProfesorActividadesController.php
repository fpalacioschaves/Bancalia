<?php
declare(strict_types=1);

namespace Src\Controller;

use Src\Core\Session;
use Src\Config\DB;

final class ProfesorActividadesController extends BaseController
{
    /** Vista: listado de actividades del profesor + compartidas */
    public function pageIndex(): void
    {
        if (!Session::check()) { $this->redirect('/Bancalia/public/login'); return; }
        if ((int)Session::roleId() !== 2) { $this->redirect('/Bancalia/public/'); return; }

        // Carga la vista (archivo: public/views/profesor/actividades.php)
        $this->view('profesor/actividades', ['title' => 'Actividades']);
    }

    /**
     * API: GET /api/profesor/actividades
     * Lista: propias (autor_id = sesión) O compartidas
     * Filtros opcionales: q, visibilidad, page, per_page, tipo_id
     */
    public function apiIndex(): void
    {
        if (!Session::check() || (int)Session::roleId() !== 2) {
            $this->json(['error' => 'No autorizado'], 401);
        }
        $uid      = (int)Session::userId();
        $pdo      = DB::pdo();

        $q         = trim((string)($_GET['q'] ?? ''));
        $tipoId    = (int)($_GET['tipo_id'] ?? 0);
        $visib     = (string)($_GET['visibilidad'] ?? '');
        $page      = max(1, (int)($_GET['page'] ?? 1));
        $perPage   = min(100, max(5, (int)($_GET['per_page'] ?? 20)));
        $offset    = ($page - 1) * $perPage;

        $where = [];
        $args  = [];

        // Permisos: mías o compartidas
        $where[] = "(a.autor_id = ? OR a.visibilidad = 'compartida')";
        $args[]  = $uid;

        if ($q !== '') {
            $where[] = "(a.titulo LIKE ? OR MATCH(a.titulo,a.enunciado) AGAINST (? IN BOOLEAN MODE))";
            $args[]  = '%'.$q.'%';
            $args[]  = $q.'*';
        }
        if ($tipoId > 0) { $where[] = "a.tipo_id = ?"; $args[] = $tipoId; }
        if ($visib !== '') {
            // Solo se admiten 'privada' | 'compartida'
            $where[] = "a.visibilidad = ?";
            $args[]  = $visib === 'privada' ? 'privada' : 'compartida';
        }

        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        // Conteo
        $stc = $pdo->prepare("SELECT COUNT(DISTINCT a.id) FROM actividades a $whereSql");
        $stc->execute($args);
        $total = (int)$stc->fetchColumn();

        // Datos (incluye GRADO, Asignatura y Tema)
        $sql = "SELECT
                  a.id,
                  a.titulo,
                  a.autor_id,
                  a.visibilidad,
                  atp.nombre AS tipo,
                  u.nombre  AS autor,
                  COALESCE(GROUP_CONCAT(DISTINCT g.nombre   ORDER BY g.nombre   SEPARATOR ', '), '') AS grados,
                  COALESCE(GROUP_CONCAT(DISTINCT asig.nombre ORDER BY asig.nombre SEPARATOR ', '), '') AS asignaturas,
                  COALESCE(GROUP_CONCAT(DISTINCT te.nombre   ORDER BY te.nombre   SEPARATOR ', '), '') AS temas
                FROM actividades a
                JOIN actividad_tipos atp ON atp.id = a.tipo_id
                JOIN usuarios u          ON u.id   = a.autor_id
                LEFT JOIN actividad_tema at ON at.actividad_id = a.id
                LEFT JOIN temas te          ON te.id = at.tema_id
                LEFT JOIN asignaturas asig  ON asig.id = te.asignatura_id
                LEFT JOIN grados g          ON g.id   = asig.grado_id
                $whereSql
                GROUP BY a.id
                ORDER BY a.actualizado_en DESC, a.id DESC
                LIMIT $perPage OFFSET $offset";

        $st = $pdo->prepare($sql);
        $st->execute($args);
        $items = $st->fetchAll() ?: [];

        foreach ($items as &$it) {
            $it['es_mia'] = ((int)$it['autor_id'] === $uid);
        }

        $this->json([
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage
        ]);
    }
}
