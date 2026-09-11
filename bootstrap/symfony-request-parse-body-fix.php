<?php

/**
 * @file
 * Fixes PHP 8.4's request_parse_body() returning an empty body on LiteSpeed.
 *
 * Symfony's Request::createFromGlobals() calls the global request_parse_body()
 * unconditionally on PHP >= 8.4 with no fallback. On this project's staging
 * host, LiteSpeed's implementation of that function silently returns an
 * empty array for url-encoded PATCH/PUT/DELETE bodies (while still consuming
 * php://input), so Canvas's ComponentInstanceForm receives a null
 * form_canvas_tree and crashes in explode() at ComponentInstanceForm.php:94.
 *
 * PHP resolves an unqualified function call to a same-namespace function
 * before falling back to the global one, so defining
 * Symfony\Component\HttpFoundation\request_parse_body() here overrides the
 * call inside Request.php without touching vendor code. This file must be
 * autoloaded (via composer.json's autoload.files) before any Request object
 * is constructed.
 */

namespace Symfony\Component\HttpFoundation;

/**
 * Reimplements Symfony's pre-8.4 manual body parsing for the broken case.
 *
 * Only intercepts url-encoded PUT/PATCH/DELETE/QUERY bodies - the same
 * content-type gate Symfony itself used before 8.4 added the native
 * function. Everything else (JSON, multipart, other methods) is delegated
 * to the real native function unchanged.
 */
function request_parse_body(): array
{
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (\in_array($method, ['PUT', 'DELETE', 'PATCH', 'QUERY'], true)
        && ($contentType === '' || \str_starts_with($contentType, 'application/x-www-form-urlencoded'))
    ) {
        \parse_str((string) \file_get_contents('php://input'), $post);
        return [$post, []];
    }

    return \request_parse_body();
}
