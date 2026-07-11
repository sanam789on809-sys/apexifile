<?php
/**
 * This file prevents direct access to the upload folder.
 * by: trainwreckjvbo on https://github.com/ignacionelson/CGT/pull/500
 */
header("Location: ../../index.php"); 
exit;