<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';

requirePost('../index.php?page=index');
requireValidCsrf('../index.php?page=index');
logoutUser();
requestSuccess([], '../index.php?page=index');
