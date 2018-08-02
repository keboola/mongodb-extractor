<?php

declare(strict_types=1);

set_error_handler(function ($severity, $message) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new \ErrorException($message);
});
