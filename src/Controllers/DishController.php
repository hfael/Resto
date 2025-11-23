<?php

require_once __DIR__ . '/../Models/Dish.php';

class DishController
{
    public function index()
    {
        $dishes = Dish::all();

        foreach ($dishes as $d) {
            echo $d['name'] . " - " . $d['price'] . " €<br>";
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
}
