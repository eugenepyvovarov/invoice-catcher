<?php
if (! function_exists('human_filesize')) {
    function human_filesize($size, $precision = 2) {
        $units = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
        $step = 1024;
        $i = 0;
        while (($size / $step) > 0.9) {
            $size = $size / $step;
            $i++;
        }
        return round($size, $precision).$units[$i];
    }
}
