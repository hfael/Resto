<?php
namespace API\Controllers;

require_once '/var/www/src/Models/Restaurant.php';

use API\Helpers\Response;
use Dompdf\Dompdf;

class PdfApiController {
    private function h($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    private function imageDataUri($photoPath): string {
        if (!$photoPath) {
            return '';
        }

        $path = (string)$photoPath;
        $mime = null;
        $raw = null;

        if (preg_match('#^https?://#i', $path)) {
            $raw = @file_get_contents($path);
            if ($raw !== false) {
                $mime = 'image/jpeg';
            }
        } else {
            $absolute = '/var/www/html/' . ltrim($path, '/');
            if (file_exists($absolute)) {
                $raw = @file_get_contents($absolute);
                $mime = @mime_content_type($absolute) ?: 'image/jpeg';
            }
        }

        if ($raw === false || $raw === null) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }

    public function restaurantById($id) {
        $row = \Restaurant::find((int)$id);
        if (!$row) {
            Response::json(["error" => "not_found"], 404);
        }

        $img = $this->imageDataUri($row['photo'] ?? '');

        $html = '<html><head><meta charset="UTF-8"></head><body>'
            . '<h1>' . $this->h($row['name'] ?? '') . '</h1>'
            . ($img !== '' ? '<img src="' . $img . '" width="300">' : '')
            . '<p><strong>Description :</strong> ' . $this->h($row['description'] ?? '') . '</p>'
            . '<p><strong>Date d\'ajout :</strong> ' . $this->h($row['event_date'] ?? '') . '</p>'
            . '<p><strong>Prix moyen :</strong> ' . $this->h($row['average_price'] ?? '') . ' EUR</p>'
            . '<p><strong>Latitude :</strong> ' . $this->h($row['latitude'] ?? '') . '</p>'
            . '<p><strong>Longitude :</strong> ' . $this->h($row['longitude'] ?? '') . '</p>'
            . '<p><strong>Contact :</strong> ' . $this->h($row['contact_name'] ?? '') . '</p>'
            . '<p><strong>Email contact :</strong> ' . $this->h($row['contact_email'] ?? '') . '</p>'
            . '</body></html>';

        $dompdf = new Dompdf();
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="restaurant_' . (int)$id . '.pdf"');
        echo $dompdf->output();
        exit;
    }
}
