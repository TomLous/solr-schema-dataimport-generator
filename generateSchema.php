<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2015 Tom Lous
 * @package     package
 * Datetime:     15/06/15 22:31
 */
$config = json_decode((file_get_contents("config/config.json")), true);

print_r($config);
print_r(json_last_error());
print_r(json_last_error_msg());