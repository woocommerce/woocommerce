<?php

/**
 * Plugin Name: QIT Query Monitor Summary
 * Description: Summarizes the data collected by QM across multiple pages.
 * Version: 0.1
 * Author: MrJnrman
 */

 if ( class_exists( 'QM_Collectors' ) ) {
    require_once __DIR__ . '/qm-summary.php';
    require_once __DIR__ . '/api/result-summary.php';

    QIT\QM_Summary::init();
}