<?php
require_once __DIR__ . '/includes/bootstrap.php';
logout();
redirect(app_base_url() . '/login.php');
