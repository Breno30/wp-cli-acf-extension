<?php

if (!defined('WP_CLI')) {
    return;
}

WP_CLI::add_command('acf import-layout', function ($args, $assoc_args) {
    WP_CLI::success("Hi there!");
});
