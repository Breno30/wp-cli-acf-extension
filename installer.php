<?php

if (!defined('WP_CLI') || !class_exists('WP_CLI_Command') ) {
    echo "WP_CLI is not defined!";
    return;
}


class ACF_Extension_WP_CLI extends WP_CLI_Command
{
    public function import($args, $assoc_args)
    {
        if (empty($args)) {
            WP_CLI::error("Please inform the layout path, like so:\n wp acf import path-to-layout");
        }

        $path = $args[0];
        $fullPath = realpath(ABSPATH . $path);

        if (!is_readable($fullPath)) {
            WP_CLI::error(sprintf('Import file missing or not readable: %s', $fullPath));
        }

        $json = file_get_contents($fullPath);
        $json = json_decode($json, true);

        if (!$json || !is_array($json)) {
            return acf_add_admin_notice(__('Import file empty', 'acf'), 'warning');
        }

        $layoutKey = $json['key'];
        $layoutName = $json['label'];

        // Find ACF group
        $groupList = acf_get_field_groups();
        $fieldGroup = $groupList[0];

        // Find ACF field
        $fieldGroup = acf_get_field_group($fieldGroup['key']);
        $fieldGroup['fields'] = acf_get_fields($fieldGroup);
        $fieldGroup = acf_prepare_field_group_for_export($fieldGroup);
        $post = acf_get_field_group_post($fieldGroup['key']);
        $fieldGroup['ID'] = $post->ID;

        // Set layout data
        $fieldGroup['fields'][0]['layouts'][$layoutKey] = $json;

        acf_import_field_group($fieldGroup);

        WP_CLI::success("Layout '$layoutName' added successfully!");
    }
}

WP_CLI::add_command('acf', 'ACF_Extension_WP_CLI');
