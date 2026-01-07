<?php
// Force switch to Indonesian
setcookie('app_locale', 'id', time() + (365 * 24 * 60 * 60), '/');
header('Location: /');
exit;
?>
