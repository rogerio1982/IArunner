<?php

function require_admin(): void
{
    if (empty($_SESSION['coach_id'])) {
        json_response(['error' => 'unauthorized'], 401);
    }
}
