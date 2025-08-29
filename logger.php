<?php
/**
 * Common Logger Functions
 * Include this file in any PHP file that needs logging functionality
 */

// Configuration - you can modify these as needed
define('LOG_FILE', 'logs/app.log');
define('LOG_DATE_FORMAT', 'Y-m-d H:i:s');

/**
 * Main logging function
 * @param string $message - The message to log
 * @param string $level - Log level (INFO, DEBUG, WARNING, ERROR, CRITICAL)
 * @param string $logFile - Optional custom log file (uses default if not provided)
 */
function writeLog($message, $level = 'INFO', $logFile = null) {
    // Use default log file if none specified
    if ($logFile === null) {
        $logFile = LOG_FILE;
    }
    
    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir) && $logDir !== '.') {
        mkdir($logDir, 0755, true);
    }
    
    // Format the log message
    $timestamp = date(LOG_DATE_FORMAT);
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Write to log file
    error_log($logMessage, 3, $logFile);
}

/**
 * Convenience functions for different log levels
 */
function logInfo($message, $logFile = null) {
    writeLog($message, 'INFO', $logFile);
}

function logDebug($message, $logFile = null) {
    writeLog($message, 'DEBUG', $logFile);
}

function logWarning($message, $logFile = null) {
    writeLog($message, 'WARNING', $logFile);
}

function logError($message, $logFile = null) {
    writeLog($message, 'ERROR', $logFile);
}

function logCritical($message, $logFile = null) {
    writeLog($message, 'CRITICAL', $logFile);
}

?>