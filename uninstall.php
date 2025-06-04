<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}
remove_role('zxtec_colaborador');
flush_rewrite_rules();
