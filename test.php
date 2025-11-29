<?php
require 'vendor/autoload.php';

if (class_exists('Dotenv\Dotenv')) {
    echo "phpdotenv is installed!";
} else {
    echo "phpdotenv is NOT installed!";
}
