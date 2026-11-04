<?php
// Capa de NEGOCIO
// Calculo de paginas para los listados. Cada listado cuenta sus filas, crea una
// Paginacion y le pide al DAO solo las filas de la pagina actual (LIMIT / OFFSET).

class Paginacion {

    public int $total;
    public int $porPagina;
    public int $paginas;
    public int $pagina;

    public function __construct(int $total, int $paginaPedida, int $porPagina = 10) {
        $this->total     = max(0, $total);
        $this->porPagina = max(1, $porPagina);
        $this->paginas   = max(1, (int) ceil($this->total / $this->porPagina));
        // si piden una pagina que no existe, se usa la ultima
        $this->pagina    = min(max(1, $paginaPedida), $this->paginas);
    }

    // Numero de pagina que viene en la direccion (?pagina=2)
    public static function paginaPedida(): int {
        return isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
    }

    public function limite(): int {
        return $this->porPagina;
    }

    public function desplazamiento(): int {
        return ($this->pagina - 1) * $this->porPagina;
    }
}
