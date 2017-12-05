<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A webservice interface to Mantis Bug Tracker
 *
 * @package MantisBT
 * @copyright Copyright MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

#require_api( 'authentication_api.php' );
#require_api( 'user_api.php' );


# C:\xampp\htdocs\prjmngt\mantisbt-2.9.0\core\constant_inc.php
# HTTP Status Codes
define( 'HTTP_STATUS_SUCCESS', 200 );
define( 'HTTP_STATUS_CREATED', 201 );
define( 'HTTP_STATUS_NO_CONTENT', 204 );
define( 'HTTP_STATUS_NOT_MODIFIED', 304 );
define( 'HTTP_STATUS_BAD_REQUEST', 400 );
define( 'HTTP_STATUS_UNAUTHORIZED', 401 );
define( 'HTTP_STATUS_FORBIDDEN', 403 );
define( 'HTTP_STATUS_NOT_FOUND', 404 );
define( 'HTTP_STATUS_CONFLICT', 409 );
define( 'HTTP_STATUS_PRECONDITION_FAILED', 412 );
define( 'HTTP_STATUS_INTERNAL_SERVER_ERROR', 500 );
define( 'HTTP_STATUS_UNAVAILABLE', 503 );

# HTTP HEADERS
define( 'HEADER_AUTHORIZATION', 'Authorization' );
define( 'HEADER_LOGIN_METHOD', 'X-Mantis-LoginMethod' );
define( 'HEADER_USERNAME', 'X-Mantis-Username' );
define( 'HEADER_VERSION', 'X-Mantis-Version' );
define( 'HEADER_IF_MATCH', 'If-Match' );
define( 'HEADER_IF_NONE_MATCH', 'If-None-Match' );
define( 'HEADER_ETAG', 'ETag' );

# LOGIN METHODS
define( 'LOGIN_METHOD_COOKIE', 'cookie' );
define( 'LOGIN_METHOD_API_TOKEN', 'api-token' );


/**
 * A middleware class that handles authentication and authorization to access APIs.
 */
class AuthMiddleware {
    
   private static $logger;

   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }    
    
	public function __invoke( \Slim\Http\Request $request, \Slim\Http\Response $response, callable $next ) {
		$t_authorization_header = $request->getHeaderLine( HEADER_AUTHORIZATION );

		if( empty( $t_authorization_header ) ) {
			# Since authorization header is empty, check if user is authenticated by checking the cookie
			# This mode is used when Web UI javascript calls into the API.
			#if( Tools::isConnectedUser() ) {
                            # TODO check PHP session 
         #                   $t_login_method = LOGIN_METHOD_COOKIE;
         #                   self::$logger->error("RestAPI: logged in with PHP session !");
			#} else {
            self::$logger->error("RestAPI: API token required");
            return $response->withStatus( HTTP_STATUS_UNAUTHORIZED, 'API token required' );
			#}
		} else {
			# TODO: add an index on the token hash for the method below
			#$t_user_id = api_token_get_user( $t_authorization_header );
			#if( $t_user_id === false ) {
			#	return $response->withStatus( HTTP_STATUS_FORBIDDEN, 'API token not found' );
			#}

			# use api token
			#$t_login_method = LOGIN_METHOD_API_TOKEN;
			#$t_password = $t_authorization_header;
			#$t_username = user_get_username( $t_user_id );
		}

		#if( mci_check_login( $t_username, $t_password ) === false ) {
		#	return $response->withStatus( HTTP_STATUS_FORBIDDEN, 'Access denied' );
		#}

		# Now that user is logged in, check if they have the right access level to access the REST API.
		# Don't treat web UI calls with cookies as API calls that need to be disabled for certain access levels.
		#if( $t_login_method != LOGIN_METHOD_COOKIE && !mci_has_readonly_access() ) {
		#	return $response->withStatus( HTTP_STATUS_FORBIDDEN, 'Higher access level required for API access' );
		#}

		$t_force_enable = $t_login_method == LOGIN_METHOD_COOKIE;
		return $next( $request->withAttribute( ATTRIBUTE_FORCE_API_ENABLED, $t_force_enable ), $response )->
			withHeader( HEADER_USERNAME, $t_username )->
			withHeader( HEADER_LOGIN_METHOD, $t_login_method );
	}
}
AuthMiddleware::staticInit();