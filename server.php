<?php
// server.php - Servidor PHP simple
$port = getenv('PORT') ?: 8000;
$host = '0.0.0.0';

echo "🚀 Starting PHP server on $host:$port\n";
echo "📁 Serving from: " . __DIR__ . "\n";

exec("php -S $host:$port -t .");
?>