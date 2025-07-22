<?php
// app/Core/ErrorHandler.php

namespace App\Core;

use Exception;
use Throwable;
use ErrorException;

class ErrorHandler
{
    private $logger;
    private $isDebug;
    
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->isDebug = $_ENV['APP_DEBUG'] ?? 'false';
        $this->isDebug = $this->isDebug === 'true' || $this->isDebug === true;
    }
    
    /**
     * Handle PHP errors
     */
    public function handleError($severity, $message, $file, $line)
    {
        // Don't handle suppressed errors
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        // Convert error to exception
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    
    /**
     * Handle uncaught exceptions
     */
    public function handleException(Throwable $exception)
    {
        try {
            $this->logException($exception);
            $this->renderExceptionResponse($exception);
        } catch (Throwable $e) {
            // If logging fails, at least show something
            $this->renderFallbackError($e);
        }
    }
    
    /**
     * Handle fatal errors
     */
    public function handleShutdown()
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $exception = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            
            $this->handleException($exception);
        }
    }
    
    /**
     * Log exception details
     */
    private function logException(Throwable $exception)
    {
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $this->logger->error($exception->getMessage(), $context);
    }
    
    /**
     * Render exception response
     */
    private function renderExceptionResponse(Throwable $exception)
    {
        $statusCode = $this->getHttpStatusCode($exception);
        
        http_response_code($statusCode);
        
        if ($this->isAjaxRequest()) {
            $this->renderJsonError($exception, $statusCode);
        } else {
            $this->renderHtmlError($exception, $statusCode);
        }
    }
    
    /**
     * Get HTTP status code from exception
     */
    private function getHttpStatusCode(Throwable $exception)
    {
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }
        
        $code = $exception->getCode();
        
        // Map common exception codes to HTTP status codes
        switch ($code) {
            case 404:
                return 404;
            case 403:
                return 403;
            case 401:
                return 401;
            case 422:
                return 422;
            case 400:
                return 400;
            default:
                return 500;
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Render JSON error response
     */
    private function renderJsonError(Throwable $exception, $statusCode)
    {
        header('Content-Type: application/json');
        
        $error = [
            'error' => true,
            'message' => $exception->getMessage(),
            'status' => $statusCode
        ];
        
        if ($this->isDebug) {
            $error['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ];
        }
        
        echo json_encode($error, JSON_PRETTY_PRINT);
    }
    
    /**
     * Render HTML error response
     */
    private function renderHtmlError(Throwable $exception, $statusCode)
    {
        if ($this->isDebug) {
            $this->renderDebugError($exception, $statusCode);
        } else {
            $this->renderProductionError($exception, $statusCode);
        }
    }
    
    /**
     * Render debug error page
     */
    private function renderDebugError(Throwable $exception, $statusCode)
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error <?php echo $statusCode; ?> - <?php echo get_class($exception); ?></title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .error-container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .error-header { background: #dc3545; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                .error-header h1 { margin: 0; font-size: 24px; }
                .error-header p { margin: 5px 0 0 0; opacity: 0.9; }
                .error-body { padding: 20px; }
                .error-message { background: #f8f9fa; border-left: 4px solid #dc3545; padding: 15px; margin-bottom: 20px; }
                .error-details { margin-bottom: 20px; }
                .error-details dt { font-weight: bold; margin-top: 10px; }
                .error-details dd { margin: 5px 0 0 20px; font-family: monospace; background: #f8f9fa; padding: 5px; border-radius: 3px; }
                .stack-trace { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
                .stack-trace pre { margin: 0; font-size: 14px; line-height: 1.4; }
                .context { margin-top: 20px; }
                .context h3 { margin-bottom: 10px; color: #495057; }
                .context table { width: 100%; border-collapse: collapse; }
                .context th, .context td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
                .context th { background: #f8f9fa; font-weight: 600; }
                .context td { font-family: monospace; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-header">
                    <h1><?php echo get_class($exception); ?></h1>
                    <p>HTTP <?php echo $statusCode; ?> Error</p>
                </div>
                <div class="error-body">
                    <div class="error-message">
                        <strong><?php echo htmlspecialchars($exception->getMessage()); ?></strong>
                    </div>
                    
                    <div class="error-details">
                        <dl>
                            <dt>File:</dt>
                            <dd><?php echo htmlspecialchars($exception->getFile()); ?></dd>
                            <dt>Line:</dt>
                            <dd><?php echo $exception->getLine(); ?></dd>
                        </dl>
                    </div>
                    
                    <div class="stack-trace">
                        <h3>Stack Trace:</h3>
                        <pre><?php echo htmlspecialchars($exception->getTraceAsString()); ?></pre>
                    </div>
                    
                    <div class="context">
                        <h3>Request Information:</h3>
                        <table>
                            <tr><th>URL</th><td><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A'); ?></td></tr>
                            <tr><th>Method</th><td><?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'N/A'); ?></td></tr>
                            <tr><th>IP Address</th><td><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'N/A'); ?></td></tr>
                            <tr><th>User Agent</th><td><?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'); ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Render production error page
     */
    private function renderProductionError(Throwable $exception, $statusCode)
    {
        $errorMessages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Page Not Found',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error'
        ];
        
        $title = $errorMessages[$statusCode] ?? 'An Error Occurred';
        $message = $this->getProductionErrorMessage($statusCode);
        
        // Try to render custom error view if it exists
        $errorView = VIEW_PATH . "/errors/{$statusCode}.php";
        if (file_exists($errorView)) {
            extract(['title' => $title, 'message' => $message, 'statusCode' => $statusCode]);
            include $errorView;
            return;
        }
        
        // Fallback to simple error page
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo $title; ?></title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
                .error-page { text-align: center; max-width: 500px; padding: 40px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .error-code { font-size: 72px; font-weight: bold; color: #dc3545; margin: 0; }
                .error-title { font-size: 24px; color: #495057; margin: 20px 0; }
                .error-message { color: #6c757d; margin-bottom: 30px; line-height: 1.5; }
                .back-button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; transition: background-color 0.2s; }
                .back-button:hover { background: #0056b3; }
            </style>
        </head>
        <body>
            <div class="error-page">
                <h1 class="error-code"><?php echo $statusCode; ?></h1>
                <h2 class="error-title"><?php echo $title; ?></h2>
                <p class="error-message"><?php echo $message; ?></p>
                <a href="/" class="back-button">Go Home</a>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Get production error message
     */
    private function getProductionErrorMessage($statusCode)
    {
        $messages = [
            400 => 'The request could not be understood by the server.',
            401 => 'You need to log in to access this resource.',
            403 => 'You don\'t have permission to access this resource.',
            404 => 'The page you are looking for could not be found.',
            422 => 'The request was well-formed but contains invalid data.',
            500 => 'Something went wrong on our end. We\'re working to fix it.'
        ];
        
        return $messages[$statusCode] ?? 'An unexpected error occurred.';
    }
    
    /**
     * Render fallback error when everything else fails
     */
    private function renderFallbackError(Throwable $exception)
    {
        echo "Critical Error: " . $exception->getMessage();
    }
}