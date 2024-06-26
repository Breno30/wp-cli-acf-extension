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
        $fullPath = realpath(getcwd() .'/'. $path);

        if (!is_readable($fullPath)) {
            WP_CLI::error(sprintf('Import file missing or not readable: %s', $fullPath));
        }

        $json = file_get_contents($fullPath);
        $json = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            WP_CLI::error(sprintf('Given file is or not a valid JSON: %s', $fullPath));
        }

        if (!$json || !is_array($json)) {
            WP_CLI::error(sprintf('Given file is empty: %s', $fullPath));
        }

        // Verify key format
        $jsonKey = $json['key'];
        $jsonKeyParts = explode('_', $jsonKey);

        if (count($jsonKeyParts) < 2) {
            WP_CLI::error(sprintf('Given file has defective key: %s', $fullPath));
        }

        $jsonType = $jsonKeyParts[0];

        switch ($jsonType) {
            case 'layout':
                $this->importLayout($json);
                break;
            default:
                WP_CLI::error(sprintf('Given file has not a layout as content: %s', $fullPath));
                break;
        }

    }

    protected function importLayout(array $json)
    {      
        $layoutKey = $json['key'];
        $layoutName = $json['label'];

        $fieldGroup = $this->inputGroup();

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

    public function export($args, $assoc_args)
    {
        // Find ACF group
        $fieldGroup = $this->inputGroup();

        // Find ACF field
        $fieldGroup = acf_get_field_group($fieldGroup['key']);
        $fieldGroup['fields'] = acf_get_fields($fieldGroup);
        $fieldGroup = acf_prepare_field_group_for_export($fieldGroup);

        // Inform layouts to user
        $layouts = $fieldGroup['fields'][0]['layouts'];
        WP_CLI\Utils\format_items('table', $layouts, array('label', 'name'));
        $layoutName = $this->ask(' Inform the name of your layout:');

        // Get layout by name
        $layouts = array_filter($layouts, function ($item) use ($layoutName) {
            return $item['name'] == $layoutName;
        });

        if (empty($layouts)) {
            WP_CLI::error("Layout named '$layoutName' not found");
        }

        reset($layouts);
        $layout = $layouts;
        $layoutKey = array_keys($layout)[0];
        $layout = $layout[$layoutKey];

        // Write layout json to file
        $content = json_encode($layout, JSON_PRETTY_PRINT);
        $fileName = "layout-$layoutName.json";
        $fp = fopen(getcwd() . "/$fileName", "wb");
        fwrite($fp, $content);
        fclose($fp);

        WP_CLI::success("Layout saved successfully as $fileName");
    }

    protected function inputGroup()
    {
        // List ACF group
        $groupList = acf_get_field_groups();

        if (count($groupList) <= 1) {
            return $groupList[0];
        }

        // Add index
        foreach ($groupList as $key => $group) {
            $groupList[$key]['index'] = $key;
        }

        WP_CLI\Utils\format_items('table', $groupList, ['index', 'title']);
        $groupIndex = $this->ask(' Inform the index of your group:');

        return $groupList[$groupIndex];
    }

    protected function ask($question)
    {
        // Adding space to question and showing it.
        fwrite(STDOUT, $question . '  ');

        return strtolower(trim(fgets(STDIN)));
    }
}

WP_CLI::add_command('acf', 'ACF_Extension_WP_CLI');
