<?php
echo $_SERVER['HTTP_USER_AGENT'];
AdminMail('FRA schedule Mail Agent', $_SERVER['HTTP_USER_AGENT']);
?>
