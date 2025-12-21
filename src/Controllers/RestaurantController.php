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

        View::render('restaurant/my.twig', [
            'items' => $items,
            'session' => $_SESSION
        ]);
    }

    public function index()
    {
        $items = Restaurant::allAccepted();

        View::render('restaurant/index.twig', [
            'items' => $items,
            'session' => $_SESSION
        ]);
    }

    public function search()
    {
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, name, average_price, created_by 
            FROM restaurants 
            WHERE name LIKE ? AND status = 'accepted'
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
            View::render('home/index.twig', ['error' => 'ID manquant.']);
            return;
        }

        $r = Restaurant::find($id);
        if (!$r) {
            View::render('home/index.twig', ['error' => 'Restaurant introuvable.']);
            return;
        }

        View::render('restaurant/show.twig', [
            'r' => $r,
            'session' => $_SESSION
        ]);
    }

    public function create()
    {
        $this->requireLogin();
        View::render('restaurant/create.twig', ['session' => $_SESSION]);
    }

    public function store()
    {
        $this->requireLogin();

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            View::render('restaurant/create.twig', ['error' => "Erreur lors de l'upload de l'image."]);
            return;
        }

        $file = $_FILES['photo'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($file['type'], $allowed)) {
            View::render('restaurant/create.twig', ['error' => "Format d'image non supporté."]);
            return;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = 'restaurant_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $uploadPath = '/var/www/html/uploads/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            View::render('restaurant/create.twig', ['error' => "Impossible de sauvegarder l'image."]);
            return;
        }

        $data = $_POST;
        $data['photo'] = '/uploads/' . $newName;
        $data['created_by'] = $_SESSION['user_id'];

        Restaurant::create($data);

        header("Location: /restaurant/my");
        exit;
    }

    public function edit()
    {
        $this->requireLogin();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: /restaurant/my");
            exit;
        }

        $r = Restaurant::find($id);
        if (!$r) {
            header("Location: /restaurant/my");
            exit;
        }

        if ($r['created_by'] != $_SESSION['user_id'] && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin')) {
            header("Location: /restaurant/my");
            exit;
        }

        View::render('restaurant/edit.twig', [
            'r' => $r,
            'session' => $_SESSION
        ]);
    }

    public function update()
    {
        $this->requireLogin();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: /restaurant/my");
            exit;
        }

        $r = Restaurant::find($id);
        if (!$r || ($r['created_by'] != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin')) {
            header("Location: /restaurant/my");
            exit;
        }

        $data = $_POST;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (in_array($file['type'], $allowed)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = 'restaurant_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $uploadPath = '/var/www/html/uploads/' . $newName;
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $data['photo'] = '/uploads/' . $newName;
                }
            }
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
        if ($id) {
            $r = Restaurant::find($id);
            if ($r && ($r['created_by'] == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin')) {
                Restaurant::delete($id);
            }
        }
        header("Location: /restaurant/my");
        exit;
    }

    public function cancel()
    {
        $this->requireLogin();
        $id = $_GET['id'] ?? null;
        if ($id) {
            $r = Restaurant::find($id);
            if ($r && $r['created_by'] == $_SESSION['user_id'] && $r['status'] === 'pending') {
                Restaurant::cancel($id);
            }
        }
        header("Location: /restaurant/my");
        exit;
    }

    public function accept()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header("Location: /home/index");
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
            header("Location: /home/index");
            exit;
        }

        $id = $_GET['id'] ?? null;
        $reason = $_POST['reason'] ?? '';

        if ($id && !empty($reason)) {
            Restaurant::reject($id, $reason);
        }

        header("Location: /admin/restaurants");
        exit;
    }
}