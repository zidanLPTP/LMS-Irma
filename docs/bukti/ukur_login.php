<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require_once dirname(__DIR__, 2).'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// 1. Bcrypt verify cost 12
$h12 = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
$t0 = microtime(true);
$v12 = password_verify('password123', $h12);
$tVerify12 = (microtime(true) - $t0) * 1000;

// 2. Rehash cost 12 ke cost 10
$t0 = microtime(true);
$rehash10 = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 10]);
$tRehash10 = (microtime(true) - $t0) * 1000;

// 3. Bcrypt verify cost 10
$h10 = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 10]);
$t0 = microtime(true);
$v10 = password_verify('password123', $h10);
$tVerify10 = (microtime(true) - $t0) * 1000;

// 4. Simulasi waktu query & bootstrap session
$t0 = microtime(true);
$user = User::where('username', 'admin')->first();
$tQuery = (microtime(true) - $t0) * 1000;

echo '=== HASIL PENGUKURAN LOGIN TERTELITI ==='.PHP_EOL;
echo 'Bcrypt Verify (Cost 12): '.number_format($tVerify12, 2).' ms'.PHP_EOL;
echo 'Rehash ke Cost 10: '.number_format($tRehash10, 2).' ms'.PHP_EOL;
echo 'Bcrypt Verify (Cost 10 standar): '.number_format($tVerify10, 2).' ms'.PHP_EOL;
echo 'Database Query User: '.number_format($tQuery, 2).' ms'.PHP_EOL;
echo 'Total Login Pertama (Cost 12 + Rehash + Query): '.number_format($tVerify12 + $tRehash10 + $tQuery, 2).' ms'.PHP_EOL;
echo 'Total Login Rutin (Cost 10 + Query): '.number_format($tVerify10 + $tQuery, 2).' ms'.PHP_EOL;
