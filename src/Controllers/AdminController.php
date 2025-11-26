<?php

require_once __DIR__ . '/../Models/Restaurant.php';
require_once __DIR__ . '/../View.php';

class AdminController
{
    private function requireAdmin()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            exit;
        }
    }

    public function restaurants()
    {
        $this->requireAdmin();
        $items = Restaurant::allPending();

        $html = '<h2>Validation restaurants</h2>';

        foreach ($items as $r) {
            $html .= '<div style="margin-bottom:20px">';
            $html .= '<strong>' . $r['name'] . '</strong><br>';
            $html .= '<img src="' . $r['photo'] . '" width="150"><br><br>';
            $html .= '<a href="/restaurant/show?id=' . $r['id'] . '">Voir</a><br><br>';

            $html .= '
                <form action="/restaurant/accept?id=' . $r['id'] . '" method="POST" style="display:inline-block">
                    <button type="submit">Accepter</button>
                </form>
            ';

            $html .= '
                <form action="/restaurant/reject?id=' . $r['id'] . '" method="POST" style="display:inline-block; margin-left:10px">
                    <input type="text" name="reason" placeholder="Raison" required>
                    <button type="submit">Refuser</button>
                </form>
            ';

            $html .= '</div>';
        }

        if (empty($items)) {
            $html .= '<p>Aucun restaurant en attente.</p>';
        }

        View::render($html);
    }
}
