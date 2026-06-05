<?php

function url(string $page): string
{
    return 'index.php?page=' . urlencode($page);
}