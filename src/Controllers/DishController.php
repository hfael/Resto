<?php

require_once __DIR__ . '/../Models/Dish.php';
require_once __DIR__ . '/../View.php';
require_once __DIR__ . '/../Database.php';

class DishController
{
    public function index()
    {
        $dishes = Dish::all();

        $html = "<h2>Liste des plats</h2>";

        foreach ($dishes as $d) {
            $html .= $d['name'] . " - " . $d['price'] . " € ";
            $html .= '<a href="/dish/edit?id=' . $d['id'] . '">Modifier</a> ';
            $html .= '<a href="/dish/delete?id=' . $d['id'] . '">Supprimer</a><br>';
        }

        $html .= '<br><a href="/dish/create">Ajouter un plat</a>';

        View::render($html);
    }

    public function create()
    {
        $html = '
            <h2>Ajouter un plat</h2>
            <form action="/dish/store" method="POST">
                <input type="text" name="name" placeholder="Nom du plat" />
                <input type="number" step="0.01" name="price" placeholder="Prix" />
                <button type="submit">Créer</button>
            </form>
        ';

        View::render($html);
    }

    public function store()
    {
        $name  = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? '';

        if (!$name || !$price) {
            exit("Champs manquants");
        }

        Dish::create($name, $price);

        View::render("<p>Plat ajouté</p>");
    }

    public function edit()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) exit("ID manquant");

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM dishes WHERE id = ?");
        $stmt->execute([$id]);
        $dish = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dish) exit("Plat introuvable");

        $html = '
            <h2>Modifier un plat</h2>
            <form action="/dish/update?id=' . $dish['id'] . '" method="POST">
                <input type="text" name="name" value="' . $dish['name'] . '"/>
                <input type="number" step="0.01" name="price" value="' . $dish['price'] . '"/>
                <button type="submit">Mettre à jour</button>
            </form>
        ';

        View::render($html);
    }

    public function update()
    {
        $id    = $_GET['id'] ?? null;
        $name  = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? '';

        if (!$id || !$name || !$price) exit("Champs manquants");

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE dishes SET name = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $price, $id]);

        View::render("<p>Plat mis à jour</p>");
    }

    public function delete()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) exit("ID manquant");

        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM dishes WHERE id = ?");
        $stmt->execute([$id]);

        View::render("<p>Plat supprimé</p>");
    }
}
