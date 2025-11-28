<?php
// debug.php - Temporary debug helper. REMOVE THIS FILE after use.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "DEBUG MODE - remove this file when finished\n";
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Current working dir: " . getcwd() . "\n";

function check_file($path) {
	echo "- $path: ";
	if (file_exists($path)) {
		echo "exists";
		echo fileperms($path) ? " (perms: " . substr(sprintf('%o', fileperms($path)), -4) . ")" : "";
		echo "\n";
	} else {
		echo "MISSING\n";
	}
}

echo "\nFilesystem checks:\n";
check_file('index.php');
check_file('config');
check_file('config/database.php');
check_file('models');
check_file('models/Product.php');

echo "\nPHP extensions:\n";
echo "- pdo: " . (extension_loaded('pdo') ? 'loaded' : 'missing') . "\n";
echo "- pdo_mysql: " . (extension_loaded('pdo_mysql') ? 'loaded' : 'missing') . "\n";

echo "\nAttempt to include config/database.php and create DB connection:\n";
try {
	if (file_exists('config/database.php')) {
		include_once 'config/database.php';
		if (class_exists('Database')) {
			$dbObj = new Database();
			$conn = $dbObj->getConnection();
			if ($conn) {
				echo "- DB connection: OK\n";
			} else {
				echo "- DB connection: FAILED (no connection returned)\n";
			}
		} else {
			echo "- Database class not found after include\n";
		}
	} else {
		echo "- config/database.php not present, cannot test DB\n";
	}
} catch (Exception $e) {
	echo "- Exception while connecting: " . $e->getMessage() . "\n";
} catch (Error $err) {
	echo "- PHP Error while connecting: " . $err->getMessage() . "\n";
}

echo "\nphpinfo() summary (trimmed):\n";
// Print only core useful info
ob_start();
phpinfo();
$p = ob_get_clean();
// extract PDO and pdo_mysql sections if possible
if (strpos($p, 'PDO') !== false) {
	echo "(phpinfo contains PDO info)\n";
}
if (strpos($p, 'pdo_mysql') !== false) {
	echo "(phpinfo contains pdo_mysql info)\n";
}

echo "\nEnd of debug output.\n";

?>
