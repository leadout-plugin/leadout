<?php

if (class_exists('LI_AWeberAPI')) {
    trigger_error("Duplicate: Another LI_AWeberAPI client library is already in scope.", E_USER_WARNING);
}
else {
    require_once('li_aweber.php');
}
