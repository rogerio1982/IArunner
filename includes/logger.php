<?php

define('LOG_DIR', __DIR__ . '/../logs');
define('LOG_FILE', LOG_DIR . '/app.log');

function log_error(string $context, string $message, array $extra = []): void
{
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }

    $line = sprintf(
        "[%s] %s: %s%s\n",
        date('Y-m-d H:i:s'),
        $context,
        $message,
        $extra ? ' ' . json_encode($extra, JSON_UNESCAPED_UNICODE) : ''
    );

    error_log($line, 3, LOG_FILE);
}

/**
 * Registra automaticamente erros fatais/exceções não tratadas do PHP.
 * Chamar uma vez no bootstrap.
 */
function registrar_handlers_de_erro(): void
{
    set_exception_handler(function (Throwable $e) {
        log_error('exception', $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        http_response_code(500);
        echo json_encode(['error' => 'Erro interno.']);
        exit;
    });

    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            log_error('fatal', $error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
        }
    });
}
