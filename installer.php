<?php

if (!defined('WP_CLI') || !class_exists('WP_CLI_Command') ) {
    echo "WP_CLI is not defined!";
    return;
}

WP_CLI::add_command('acf import-layout', function ($args, $assoc_args) {
    WP_CLI::success("Hi there!");
});
