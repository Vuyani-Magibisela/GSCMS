<?php
// Temporary debug file to understand the routing issue

echo "<h1>Debug Routing Information</h1>";

echo "<h2>Server Variables:</h2>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "<br>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "<h2>Parsed URI: " . $uri . "</h2>";

$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
echo "<h2>Script Path: " . $scriptPath . "</h2>";

// Test the pattern matching
if (preg_match('#^(/[^/]+/public)(.*)$#', $uri, $matches)) {
    echo "<h2>Pattern Match Found:</h2>";
    echo "Full match: " . $matches[0] . "<br>";
    echo "Subdir path: " . $matches[1] . "<br>";
    echo "Remaining path: " . $matches[2] . "<br>";
} else {
    echo "<h2>No Pattern Match</h2>";
}

if ($scriptPath !== '/' && strpos($uri, $scriptPath) === 0) {
    echo "<h2>Script Path Stripping Would Apply</h2>";
    $newUri = substr($uri, strlen($scriptPath));
    echo "New URI would be: " . $newUri . "<br>";
} else {
    echo "<h2>No Script Path Stripping</h2>";
}

// Test what the final URI would be
if (preg_match('#^(/[^/]+/public)(.*)$#', $uri, $matches)) {
    $finalUri = empty($matches[2]) ? '/' : $matches[2];
} elseif ($scriptPath !== '/' && strpos($uri, $scriptPath) === 0) {
    $finalUri = substr($uri, strlen($scriptPath));
} else {
    $finalUri = $uri;
}

if (empty($finalUri) || $finalUri[0] !== '/') {
    $finalUri = '/' . $finalUri;
}

echo "<h2>Final URI: " . $finalUri . "</h2>";
?>