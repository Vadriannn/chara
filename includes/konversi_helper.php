<?php
/**
 * Helper untuk menghitung konversi satuan secara berantai menggunakan BFS
 */

function getKonversiGraph($koneksi) {
    $graph = [];
    $stmt = $koneksi->query("SELECT SatuanBesar_id, SatuanKecil_id, Konversi FROM tkonversisatuan");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as $row) {
        $besar = $row['SatuanBesar_id'];
        $kecil = $row['SatuanKecil_id'];
        $konv = (float)$row['Konversi'];

        if (!isset($graph[$besar])) $graph[$besar] = [];
        if (!isset($graph[$kecil])) $graph[$kecil] = [];

        $graph[$besar][$kecil] = $konv;
        if ($konv != 0) {
            $graph[$kecil][$besar] = 1.0 / $konv;
        }
    }
    return $graph;
}

function cariKonversiPHP($graph, $from_id, $to_id) {
    if ($from_id == $to_id) return 1.0;
    if (!isset($graph[$from_id])) return 1.0;

    $queue = [
        ['id' => $from_id, 'multiplier' => 1.0]
    ];
    $visited = [];
    $visited[$from_id] = true;

    while (count($queue) > 0) {
        $curr = array_shift($queue);
        $curr_id = $curr['id'];
        $curr_mult = $curr['multiplier'];

        if ($curr_id == $to_id) {
            return $curr_mult;
        }

        if (isset($graph[$curr_id])) {
            foreach ($graph[$curr_id] as $neighbor_id => $edge_weight) {
                if (!isset($visited[$neighbor_id])) {
                    $visited[$neighbor_id] = true;
                    $queue[] = [
                        'id' => $neighbor_id,
                        'multiplier' => $curr_mult * $edge_weight
                    ];
                }
            }
        }
    }

    return 1.0;
}
?>
