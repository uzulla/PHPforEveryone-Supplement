<?php
register_shutdown_function(function () {
    $memory = memory_get_peak_usage(false);
    $url = $_SERVER['REQUEST_URI'] ?? "-";
    error_log("memory peak: " . sprintf("%.2f kbytes", $memory / 1024)) . " {$url}";
});

// your code.

echo "Hello, world" . PHP_EOL;

$big_array = [];
$i = 1000000;
while ($i--) {
    $big_array[] = $i;
}
