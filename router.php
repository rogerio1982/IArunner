<?php
// Router para o servidor embutido do PHP (uso local/dev apenas).
// Docroot é /public; /api e /webhook.php são roteados para a raiz do projeto (um nível acima).

$root = __DIR__;
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri === '/webhook.php' || str_starts_with($uri, '/api/')) {
    $script = $root . $uri;
    if (is_file($script)) {
        chdir(dirname($script));
        require $script;
        return true;
    }
    http_response_code(404);
    return true;
}

return false; // deixa o servidor embutido resolver arquivos estáticos dentro do docroot (public/)
