<?php

require_once __DIR__ . '/../Models/Restaurant.php';
require_once __DIR__ . '/../View.php';

class RestaurantController
{
    private function requireLogin()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login/index");
            exit;
        }
    }

    public function my()
    {
        $this->requireLogin();

        $items = Restaurant::allByOwner($_SESSION['user_id']);

        $html = '<h2>Mes restaurants</h2>';

        foreach ($items as $r) {
            $html .= '<div style="margin-bottom:20px; padding:10px; border:1px solid #ddd; width:300px">';
            $html .= '<img src="' . $r['photo'] . '" width="120"><br>';
            $html .= '<strong>' . $r['name'] . '</strong><br>';
            $html .= substr($r['description'], 0, 60) . '<br>';

            if ($r['status'] === 'pending') {
                $html .= '<span style="color:orange">En attente</span><br>';
                $html .= '<a href="/restaurant/cancel?id=' . $r['id'] . '">Annuler</a>';
            }

            if ($r['status'] === 'accepted') {
                $html .= '<span style="color:green">Accepté</span><br>';
                $html .= '<a href="/restaurant/show?id=' . $r['id'] . '">Voir</a><br>';
                $html .= '<a href="/restaurant/edit?id=' . $r['id'] . '">Modifier</a><br>';
                $html .= '<a href="/restaurant/delete?id=' . $r['id'] . '">Supprimer</a><br>';
                $html .= '<a href="/reservation/byRestaurant?id=' . $r['id'] . '">Voir les réservations</a><br>';

            }

            if ($r['status'] === 'rejected') {
                $html .= '<span style="color:red">Refusé</span><br>';
                if (!empty($r['rejection_reason'])) {
                    $html .= '<em>' . $r['rejection_reason'] . '</em><br>';
                }
                $html .= '<a href="/restaurant/edit?id=' . $r['id'] . '">Corriger et renvoyer</a>';
            }

            if ($r['status'] === 'cancelled') {
                $html .= '<span style="color:gray">Annulé</span>';
            }

            $html .= '</div>';
        }

        if (empty($items)) {
            $html .= '<p>Aucun restaurant.</p>';
        }

        View::render($html);
    }



    public function index()
    {
        $html = '
        <h2>Liste des restaurants</h2>

        <input type="text" id="searchInput" placeholder="Rechercher..." style="margin-bottom:10px">

        <div id="results">';

        $items = Restaurant::allAccepted();

        foreach ($items as $r) {
            $html .= $r['name'] . " - " . $r['average_price'] . "€ ";
            $html .= '<a href="/restaurant/show?id=' . $r['id'] . '">Détails</a> ';

            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                $html .= '<a href="/restaurant/edit?id=' . $r['id'] . '">Modifier</a> ';
                $html .= '<a href="/restaurant/delete?id=' . $r['id'] . '">Supprimer</a>';
            } else if ($r['created_by'] == $_SESSION['user_id']) {
                $html .= '<a href="/restaurant/edit?id=' . $r['id'] . '">Modifier</a> ';
                $html .= '<a href="/restaurant/delete?id=' . $r['id'] . '">Supprimer</a>';
            }

            $html .= '<br>';
        }

        $html .= '</div>';

        $html .= '<br><a href="/restaurant/create">Ajouter un restaurant</a>';

        $html .= '
        <script>
            const input = document.getElementById("searchInput");
            const results = document.getElementById("results");

            input.addEventListener("input", function() {
                const q = this.value;

                fetch("/restaurant/search?q=" + encodeURIComponent(q))
                    .then(res => res.json())
                    .then(data => {
                        results.innerHTML = "";
                        data.forEach(item => {
                            results.innerHTML += 
                                item.name + " - " + item.average_price + "€ " +
                                "<a href=\'/restaurant/show?id=" + item.id + "\'>Détails</a> " +
                                "<a href=\'/restaurant/edit?id=" + item.id + "\'>Modifier</a> " +
                                "<a href=\'/restaurant/delete?id=" + item.id + "\'>Supprimer</a><br>";
                        });

                        if (data.length === 0) {
                            results.innerHTML = "<p>Aucun résultat</p>";
                        }
                    });
            });
        </script>
        ';

        View::render($html);
    }

    public function search()
    {
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, name, average_price 
            FROM restaurants 
            WHERE name LIKE ?
            ORDER BY id DESC
        ");
        $stmt->execute(['%' . $query . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            View::render("<p>ID manquant.</p>");
            return;
        }

        $r = Restaurant::find($id);
        if (!$r) {
            View::render("<p>Restaurant introuvable.</p>");
            return;
        }

        $html = "<h2>Détails du restaurant</h2>";
        $html .= "<a href='/pdf/restaurant?id={$r['id']}' target='_blank'>Exporter en PDF</a><br><br>";

        $html .= "<img src='" . $r['photo'] . "' width='300'><br><br>";

        $html .= "<p><strong>Nom :</strong> " . $r['name'] . "</p>";
        $html .= "<p><strong>Description :</strong> " . $r['description'] . "</p>";
        $html .= "<p><strong>Date d'ajout:</strong> " . $r['event_date'] . "</p>";
        $html .= "<p><strong>Prix moyen :</strong> " . $r['average_price'] . "€</p>";
        $html .= "<p><strong>Latitude :</strong> " . $r['latitude'] . "</p>";
        $html .= "<p><strong>Longitude :</strong> " . $r['longitude'] . "</p>";
        $html .= "<p><strong>Contact :</strong> " . $r['contact_name'] . "</p>";
        $html .= "<p><strong>Email :</strong> " . $r['contact_email'] . "</p>";

        $html .= "
        <h3>Réserver</h3>
        <form method='POST' action='/reservation/store'>
            <input type='hidden' name='restaurant_id' value='{$r['id']}'>
            
            <label>Date :</label>
            <input type='date' name='reservation_date' required><br>
            
            <label>Heure :</label>
            <input type='time' name='reservation_time' required><br><br>
            
            <button type='submit'>Réserver</button>
        </form>
        ";

        View::render($html);
    }



    public function store()
    {
        $this->requireLogin();

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            View::render("<p>Erreur lors de l'upload de l'image.</p>");
            return;
        }

        $file = $_FILES['photo'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($file['type'], $allowed)) {
            View::render("<p>Format d'image non supporté.</p>");
            return;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = 'restaurant_' . time() . '_' . rand(1000,9999) . '.' . $ext;

        $uploadPath = '/var/www/html/uploads/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            View::render("<p>Impossible de sauvegarder l'image.</p>");
            return;
        }

        $_POST['photo'] = '/uploads/' . $newName;

        Restaurant::create($_POST);

        header("Location: /restaurant/index");
        exit;
    }

    public function edit()
    {
        $this->requireLogin();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            View::render("<p>ID manquant.</p>");
            return;
        }

        $r = Restaurant::find($id);
        if (!$r) {
            View::render("<p>Restaurant introuvable.</p>");
            return;
        }

        $html = '
            <h2>Modifier un restaurant</h2>

            <form action="/restaurant/update?id=' . $id . '" method="POST" enctype="multipart/form-data">
                <input type="text" name="name" value="' . $r['name'] . '"><br>
                <textarea name="description">' . $r['description'] . '</textarea><br>
                <input type="date" name="event_date" value="' . $r['event_date'] . '"><br>
                <input type="number" name="average_price" value="' . $r['average_price'] . '"><br>

                <p>Latitude :</p>
                <input type="text" id="lat" name="latitude" value="' . $r['latitude'] . '" readonly><br>

                <p>Longitude :</p>
                <input type="text" id="lng" name="longitude" value="' . $r['longitude'] . '" readonly><br><br>

                <div id="map" style="height:300px; margin-bottom:15px;"></div>

                <input type="text" name="contact_name" value="' . $r['contact_name'] . '"><br>
                <input type="email" name="contact_email" value="' . $r['contact_email'] . '"><br>

                <p>Photo actuelle :</p>
                <img src="' . $r['photo'] . '" width="150"><br><br>

                <p>Nouvelle photo (optionnel) :</p>
                <input type="file" name="photo"><br><br>

                <button type="submit">Sauvegarder</button>
            </form>

            <script>
                const initialLat = ' . $r['latitude'] . ';
                const initialLng = ' . $r['longitude'] . ';

                const map = L.map("map").setView([initialLat, initialLng], 13);

                L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    maxZoom: 19
                }).addTo(map);

                let marker = L.marker([initialLat, initialLng]).addTo(map);

                map.on("click", function(e) {
                    const lat = e.latlng.lat.toFixed(7);
                    const lng = e.latlng.lng.toFixed(7);

                    document.getElementById("lat").value = lat;
                    document.getElementById("lng").value = lng;

                    marker.setLatLng(e.latlng);
                });
            </script>
        ';

        View::render($html);
    }

    public function update()
    {
        $this->requireLogin();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            View::render("<p>ID manquant.</p>");
            return;
        }

        $r = Restaurant::find($id);
        if (!$r) {
            View::render("<p>Introuvable.</p>");
            return;
        }

        if ($r['created_by'] != $_SESSION['user_id'] && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin')) {
            http_response_code(403);
            exit;
        }

        if ($r['status'] === 'pending' || $r['status'] === 'cancelled') {
            http_response_code(403);
            exit;
        }

        $data = $_POST;
        $currentPhoto = $r['photo'];

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            $allowed = ['image/jpeg','image/png','image/webp'];
            if (in_array($file['type'], $allowed)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = 'restaurant_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $uploadPath = '/var/www/html/uploads/' . $newName;
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $data['photo'] = '/uploads/' . $newName;
                }
            }
        }

        if (!isset($data['photo']) || !$data['photo']) {
            $data['photo'] = $currentPhoto;
        }

        if ($r['status'] === 'rejected') {
            Restaurant::resubmit($id, $data);
        } else {
            Restaurant::update($id, $data);
        }

        header("Location: /restaurant/my");
        exit;
    }


    public function delete()
    {
        $this->requireLogin();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            View::render("<p>ID manquant.</p>");
            return;
        }

        Restaurant::delete($id);

        header("Location: /restaurant/index");
        exit;
    }
    public function cancel()
    {
        $this->requireLogin();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            exit;
        }

        $r = Restaurant::find($id);
        if (!$r) {
            http_response_code(404);
            exit;
        }

        if ($r['created_by'] != $_SESSION['user_id']) {
            http_response_code(403);
            exit;
        }

        if ($r['status'] !== 'pending') {
            http_response_code(403);
            exit;
        }

        Restaurant::cancel($id);

        header("Location: /restaurant/my");
        exit;
    }

    public function accept()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            exit;
        }

        $id = $_GET['id'] ?? null;
        if ($id) {
            Restaurant::accept($id);
        }

        header("Location: /admin/restaurants");
        exit;
    }
    public function reject()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            exit;
        }

        $id = $_GET['id'] ?? null;
        $reason = $_POST['reason'] ?? '';

        if ($id && $reason !== '') {
            Restaurant::reject($id, $reason);
        }

        header("Location: /admin/restaurants");
        exit;
    }
}
