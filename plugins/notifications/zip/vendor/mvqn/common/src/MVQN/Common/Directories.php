<?php
declare(strict_types=1);

namespace MVQN\Common;

final class Directories
{
    public static function rmdir(string $dir, bool $recursive = false)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object) && $recursive)
                        self::rmdir($dir . "/" . $object, $recursive);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            rmdir($dir);
        }
    }


}