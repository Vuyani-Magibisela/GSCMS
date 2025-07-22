<?php 
// Helper functions for views
function baseUrl($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $scriptPath = $scriptPath === '/' ? '' : $scriptPath;
    
    $baseUrl = $protocol . $host . $scriptPath;
    
    return $path ? rtrim($baseUrl, '/') . '/' . ltrim($path, '/') : $baseUrl;
}

function isActivePage($pageName) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    
    if ($scriptPath !== '/' && strpos($uri, $scriptPath) === 0) {
        $uri = substr($uri, strlen($scriptPath));
    }
    
    $uri = trim($uri, '/');
    $currentPage = explode('/', $uri)[0] ?: 'home';
    
    return $currentPage === $pageName;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'GDE SciBOTICS Competition 2025 | Future Innovators'; ?></title>
    
    <?php if (isActivePage('home') || isActivePage('')): ?>
    <link rel="stylesheet" href="<?php echo baseUrl('css/home_style.css'); ?>">
    <?php endif; ?>

</head>

<body>
<!-- Navigation -->
<nav id="navbar">
    <div class="nav-container">
        <a href="<?php echo baseUrl(); ?>" class="logo">SciBOTICS 2025</a>
        <ul class="nav-links">
            <li><a href="<?php echo baseUrl(); ?>" class="<?php echo isActivePage('home') || isActivePage('') ? 'active' : ''; ?>">Home</a></li>
            <li><a href="<?php echo baseUrl('about'); ?>" class="<?php echo isActivePage('about') ? 'active' : ''; ?>">About</a></li>
            <li><a href="<?php echo baseUrl('categories'); ?>" class="<?php echo isActivePage('categories') ? 'active' : ''; ?>">Categories</a></li>
            <li><a href="<?php echo baseUrl('phases'); ?>" class="<?php echo isActivePage('phases') ? 'active' : ''; ?>">Phases</a></li>
            <li><a href="<?php echo baseUrl('register'); ?>" class="<?php echo isActivePage('register') ? 'active' : ''; ?>">Register</a></li>
        </ul>
        <a href="<?php echo baseUrl('register'); ?>" class="cta-button">Join Competition</a>
    </div>
</nav>