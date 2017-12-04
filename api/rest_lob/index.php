<?php

# Access CodevTT classes
require('../../path.inc.php');

# Access composer libraries
require_once( __DIR__ . '/../../vendor/autoload.php' );

$t_restcore_dir = __DIR__ . '/restcore/';

require_once( $t_restcore_dir . 'ApiEnabledMiddleware.php' );
require_once( $t_restcore_dir . 'AuthMiddleware.php' );
require_once( $t_restcore_dir . 'CacheMiddleware.php' );
require_once( $t_restcore_dir . 'OfflineMiddleware.php' );
require_once( $t_restcore_dir . 'VersionMiddleware.php' );

# Show SLIM detailed errors
$t_config = array();
$t_config['settings'] = array( 'displayErrorDetails' => true );
$t_container = new \Slim\Container( $t_config );

$g_app = new \Slim\App( $t_container );

# Add middleware - executed in reverse order of appearing here.
$g_app->add( new ApiEnabledMiddleware() );
$g_app->add( new AuthMiddleware() );
$g_app->add( new VersionMiddleware() );
$g_app->add( new OfflineMiddleware() );
$g_app->add( new CacheMiddleware() );

# Add CodevTT REST routes
require_once( $t_restcore_dir . 'test_rest.php' );


# run Slim
$g_app->run();

