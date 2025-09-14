<?php
echo '<pre>';
$output = shell_exec('git pull');
echo htmlspecialchars($output);
echo '</pre>';
