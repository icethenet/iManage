<?php
require 'app/Utils/ImageManipulator.php';

echo "Checking ImageManipulator methods...\n\n";
$methods = get_class_methods('ImageManipulator');

$filterMethods = ['sepia', 'vignette', 'blur'];
foreach ($filterMethods as $method) {
    if (in_array($method, $methods)) {
        echo "✅ $method() method exists\n";
    } else {
        echo "❌ $method() method NOT FOUND\n";
    }
}

echo "\nAll public methods:\n";
foreach ($methods as $m) {
    if (!str_starts_with($m, '__')) {
        echo "  - $m\n";
    }
}
