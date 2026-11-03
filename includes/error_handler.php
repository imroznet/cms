<?php
class ErrorHandler {
    private $displayErrors = false;
    private $logErrors = true;
    private $errorLogFile;
    
    public function __construct($displayErrors = false, $logErrors = true) {
        $this->displayErrors = $displayErrors;
        $this->logErrors = $logErrors;
        $this->errorLogFile = dirname(__DIR__) . '/logs/error.log';
        
        // Create logs directory if it doesn't exist
        $logsDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        
        // Set error and exception handlers
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleFatalError']);
    }
    
    public function handleError($errno, $errstr, $errfile, $errline) {
        $error = [
            'type' => $this->getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'time' => date('Y-m-d H:i:s')
        ];
        
        if ($this->logErrors) {
            $this->logError($error);
        }
        
        if ($this->displayErrors) {
            $this->displayError($error);
        }
        
        // Don't execute PHP's internal error handler
        return true;
    }
    
    public function handleException($exception) {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'time' => date('Y-m-d H:i:s')
        ];
        
        if ($this->logErrors) {
            $this->logError($error);
        }
        
        if ($this->displayErrors) {
            $this->displayError($error);
        } else {
            // Show user-friendly error page
            include PAGES_PATH . '/500.php';
        }
        
        exit(1);
    }
    
    public function handleFatalError() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
    
    private function getErrorType($errno) {
        switch ($errno) {
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
            default:
                return 'UNKNOWN';
        }
    }
    
    private function logError($error) {
        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            $error['time'],
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );
        
        file_put_contents($this->errorLogFile, $logMessage, FILE_APPEND);
    }
    
    private function displayError($error) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 20px; margin: 10px 0; border-radius: 5px;">';
        echo '<h2>Error</h2>';
        echo '<p><strong>Type:</strong> ' . htmlspecialchars($error['type']) . '</p>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($error['message']) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($error['file']) . '</p>';
        echo '<p><strong>Line:</strong> ' . htmlspecialchars($error['line']) . '</p>';
        echo '</div>';
    }
}