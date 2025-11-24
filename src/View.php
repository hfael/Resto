<?php

class View {
    public static function render($content) {
        $layoutPath = __DIR__ . '/Views/layout.php';
        $pageContent = $content;
        include $layoutPath;
    }
}
