<?php
/**
 * Get Angular index content
 *
 * @param string $index_path
 * @return string
 */

function get_angular_index_content(string $index_path = 'mdcms'){
    $index_content = file_get_contents(FCPATH . "$index_path/index.html");

    
        
        // Replace asset paths
        $index_content = preg_replace(
            ['/(src|href)="\/(?!\/)/'],
            ['$1="' . base_url("$index_path/") . ''],
            $index_content
        );
        echo $index_content;
        
        // Replace base href with the current url
        
        $index_content = preg_replace(
            '/<base href="\/"\s*\/?>/i',
            '<base href="./">',
            $index_content
        );
        
        return $index_content;
}

