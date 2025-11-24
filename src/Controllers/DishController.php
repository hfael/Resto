<?php

require_once __DIR__ . '/../Models/Dish.php';

class DishController
{
    public function index()
    {
        $dishes = Dish::all();

        foreach ($dishes as $d) {
            echo $d['name'].' - '.$d['price'].' € ';
            echo '<a href="/dish/edit?id='.$d['id'].'">Modifier</a> ';
            echo '<a href="/dish/delete?id='.$d['id'].'">Supprimer</a><br>';
        }
        echo '<br><a href="/dish/create">Ajouter un plat</a>';
    }

    public function create()
    {
        echo '
            <form action="/dish/store" method="POST">
                <input type="text" name="name" placeholder="Nom du plat" />
                <input type="number" step="0.01" name="price" placeholder="Prix" />
                <button type="submit">Créer</button>
            </form>
        ';
    }

    public function store()
    {
        $name  = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? '';

        if (!$name || !$price) {
            exit('Champs manquants');
        }

        Dish::create($name, $price);

        echo "Plat ajouté";
    }

    public function edit()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) exit('ID manquant');

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM dishes WHERE id = ?");
        $stmt->execute([$id]);
        $dish = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dish) exit('Plat introuvable');

        echo '
            <form action="/dish/update?id='.$dish['id'].'" method="POST">
                <input type="text" name="name" value="'.$dish['name'].'"/>
                <input type="number" step="0.01" name="price" value="'.$dish['price'].'"/>
                <button type="submit">Mettre à jour</button>
            </form>
        ';
    }

    public function update()
    {
        $id    = $_GET['id'] ?? null;
        $name  = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? '';

        if (!$id || !$name || !$price) exit('Champs manquants');

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE dishes SET name = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $price, $id]);

        echo "Plat mis à jour";
    }
    public function delete()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) exit('ID manquant');

        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM dishes WHERE id = ?");
        $stmt->execute([$id]);

        echo "Plat supprimé";
    }
}
