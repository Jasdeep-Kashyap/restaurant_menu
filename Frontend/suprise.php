<?php
// Page visibility guard
$_ps_file = __DIR__ . '/../includes/page_settings.json';
$_ps = file_exists($_ps_file) ? (json_decode(file_get_contents($_ps_file), true) ?? []) : [];
if (isset($_ps['surprise']) && !$_ps['surprise']) {
    http_response_code(404);
    $error_page = __DIR__ . '/../errors/404.php';
    if (file_exists($error_page)) require $error_page;
    else echo '<h1>404 Not Found</h1>';
    exit;
}
?>
<html>

<body>
    <img src="../Image_Content/suprise.png">
</body>

</html>
