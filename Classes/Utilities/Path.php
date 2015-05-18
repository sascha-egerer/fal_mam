<?php
namespace Crossmedia\FalMam\Utilities;

class Path {
    public static function join() {
        $parts = func_get_args();
        foreach ($parts as $key => $part) {
            $parts[$key] = trim($part, '/');
        }
        return str_replace('//', '/', implode('/', $parts));
    }
}
?>