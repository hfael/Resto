<?php

require_once __DIR__ . '/../Models/Restaurant.php';
require_once '/var/www/vendor/autoload.php';

use Dompdf\Dompdf;

class PDFController
{
    public function restaurant()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) exit("ID manquant");

        $r = Restaurant::find($id);
        if (!$r) exit("Restaurant introuvable");

        // Chemin absolu dans Docker (et pas sur Windows)
        $imagePath = '/var/www/html' . $r['photo'];

        // Image => Base64
        if (file_exists($imagePath)) {
            $mime = mime_content_type($imagePath);
            $encoded = base64_encode(file_get_contents($imagePath));
            $base64Image = "data:$mime;base64,$encoded";
        } else {
            $base64Image = "";
        }

        $html = "
            <h1>{$r['name']}</h1>
            <img src='{$base64Image}' width='300'>
            <p><strong>Description :</strong> {$r['description']}</p>
            <p><strong>Date :</strong> {$r['event_date']}</p>
            <p><strong>Prix moyen :</strong> {$r['average_price']} €</p>
            <p><strong>Latitude :</strong> {$r['latitude']}</p>
            <p><strong>Longitude :</strong> {$r['longitude']}</p>
            <p><strong>Contact :</strong> {$r['contact_name']}</p>
            <p><strong>Email contact :</strong> {$r['contact_email']}</p>
        ";

        $dompdf = new Dompdf();
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper("A4");
        $dompdf->render();
        $dompdf->stream("restaurant_{$id}.pdf", ["Attachment" => true]);
    }
}
