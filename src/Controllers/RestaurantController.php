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

    public function index()
    {
        $items = Restaurant::all();

        $html = "<h2>Liste des restaurants</h2>";

        foreach ($items as $r) {
            $html .= $r['name'] . " - " . $r['average_price'] . "€ ";
            $html .= '<a href="/restaurant/show?id=' . $r['id'] . '">Détails</a> ';
            $html .= '<a href="/restaurant/edit?id=' . $r['id'] . '">Modifier</a> ';
            $html .= '<a href="/restaurant/delete?id=' . $r['id'] . '">Supprimer</a><br>';
        }

        $html .= '<br><a href="/restaurant/create">Ajouter un restaurant</a>';

        View::render($html);
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
        foreach ($r as $k => $v) {
            $html .= "<p><strong>$k :</strong> $v</p>";
        }

        View::render($html);
    }

    public function create()
    {
        $this->requireLogin();

        $html = '
            <h2>Ajouter un restaurant</h2>
            <form action="/restaurant/store" method="POST">
                <input type="text" name="name" placeholder="Nom"><br>
                <textarea name="description" placeholder="Description"></textarea><br>
                <input type="date" name="event_date"><br>
                <input type="number" name="average_price" placeholder="Prix moyen"><br>
                <input type="text" name="latitude" placeholder="Latitude"><br>
                <input type="text" name="longitude" placeholder="Longitude"><br>
                <input type="text" name="contact_name" placeholder="Contact"><br>
                <input type="email" name="contact_email" placeholder="Email contact"><br>
                <input type="text" name="photo" placeholder="URL photo (temporaire)"><br>
                <button type="submit">Créer</button>
            </form>
        ';

        View::render($html);
    }

    public function store()
    {
        $this->requireLogin();

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
            <form action="/restaurant/update?id=' . $id . '" method="POST">
                <input type="text" name="name" value="' . $r['name'] . '"><br>
                <textarea name="description">' . $r['description'] . '</textarea><br>
                <input type="date" name="event_date" value="' . $r['event_date'] . '"><br>
                <input type="number" name="average_price" value="' . $r['average_price'] . '"><br>
                <input type="text" name="latitude" value="' . $r['latitude'] . '"><br>
                <input type="text" name="longitude" value="' . $r['longitude'] . '"><br>
                <input type="text" name="contact_name" value="' . $r['contact_name'] . '"><br>
                <input type="email" name="contact_email" value="' . $r['contact_email'] . '"><br>
                <input type="text" name="photo" value="' . $r['photo'] . '"><br>
                <button type="submit">Sauvegarder</button>
            </form>
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

        Restaurant::update($id, $_POST);

        header("Location: /restaurant/index");
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
}
