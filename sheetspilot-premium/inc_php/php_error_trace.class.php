<?php
/**
 * Site-wide PHP warning/notice/deprecation backtraces (Pro).
 *
 * Enable with ?showtrace=true or ?showtrace=1 while logged in with SheetsPilot capability.
 * Traces are printed inline and written to the PHP error log.
 *
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'SHEETSPILOT_INC' ) ) {
	die( 'restricted access' );
}

class SheetsPilot_PhpErrorTrace {

	/** @var callable|null */
	private static $previous_handler = null;

	/** @var bool */
	private static $active = false;

	/**
	 * Hook after globals/user are available.
	 *
	 * @return void
	 */
	public static function bootstrap() {
		add_action( 'init', array( __CLASS__, 'maybeEnableFromRequest' ), 5 );
	}

	/**
	 * Turn on the site-wide error-trace handler when showtrace is requested.
	 *
	 * @return void
	 */
	public static function maybeEnableFromRequest() {
		if ( self::$active ) {
			return;
		}

		if ( ! current_user_can( SheetsPilotGlobals::$capability ) ) {
			return;
		}

		$showtrace = SheetsPilotFunctions::getGetVar( 'showtrace', '', SheetsPilotFunctions::SANITIZE_TEXT_FIELD );
		if ( false === $showtrace || '' === $showtrace ) {
			$showtrace = SheetsPilotFunctions::getPostGetVariable( 'showtrace', '', SheetsPilotFunctions::SANITIZE_TEXT_FIELD );
		}

		if ( false === $showtrace ) {
			return;
		}

		if ( ! self::isEnabledValue( $showtrace ) && ! SheetsPilotGlobals::$showTrace ) {
			return;
		}

		SheetsPilotGlobals::$showTrace = true;
		self::register();
	}

	/**
	 * Register the PHP error handler.
	 *
	 * @return void
	 */
	public static function register() {
		if ( self::$active ) {
			return;
		}

		self::$active           = true;
		self::$previous_handler = set_error_handler( array( __CLASS__, 'handlePhpError' ) );
	}

	/**
	 * @param int    $errno   Error level.
	 * @param string $errstr  Message.
	 * @param string $errfile File.
	 * @param int    $errline Line.
	 * @return bool
	 */
	public static function handlePhpError( $errno, $errstr, $errfile, $errline ) {
		if ( ! self::$active ) {
			return self::callPreviousHandler( $errno, $errstr, $errfile, $errline );
		}

		$tracked = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED;
		if ( ! ( $errno & $tracked ) ) {
			return self::callPreviousHandler( $errno, $errstr, $errfile, $errline );
		}

		// showtrace is explicit: capture even when WP_DEBUG / error_reporting hides deprecations.
		$label = self::getErrorLabel( $errno );
		$line  = $label . ': ' . $errstr . ' in ' . $errfile . ' on line ' . $errline;
		$trace = self::formatBacktrace( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) );

		error_log( "[SheetsPilot showtrace] {$line}\n{$trace}" );

		$doing_ajax = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();
		$wants_json = $doing_ajax || ( defined( 'REST_REQUEST' ) && REST_REQUEST );

		if ( ! $wants_json ) {
			echo '<div class="sheetspilot-php-warning" style="margin:12px;padding:12px;border:1px solid #d63638;background:#fcf0f1;color:#1d2327;font:13px/1.45 Consolas,Menlo,monospace;white-space:pre-wrap;">';
			echo esc_html( $line ) . "\n";
			echo '<pre class="sheetspilot-php-error-trace" style="margin:8px 0 0;white-space:pre-wrap;">' . esc_html( $trace ) . '</pre>';
			echo '</div>';
		}

		return true;
	}

	/**
	 * @param mixed $value Raw showtrace value.
	 * @return bool
	 */
	private static function isEnabledValue( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return in_array( strtolower( (string) $value ), array( 'true', '1' ), true );
	}

	/**
	 * @param int $errno Error level.
	 * @return string
	 */
	private static function getErrorLabel( $errno ) {
		switch ( $errno ) {
			case E_WARNING:
			case E_USER_WARNING:
				return 'Warning';
			case E_NOTICE:
			case E_USER_NOTICE:
				return 'Notice';
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				return 'Deprecated';
			default:
				return 'PHP';
		}
	}

	/**
	 * @param array<int, array<string, mixed>> $backtrace debug_backtrace() frames.
	 * @return string
	 */
	private static function formatBacktrace( $backtrace ) {
		$lines = array();
		$skip  = true;

		foreach ( $backtrace as $frame ) {
			if ( $skip ) {
				$function = isset( $frame['function'] ) ? (string) $frame['function'] : '';
				if ( in_array( $function, array( 'handlePhpError', 'formatBacktrace' ), true ) ) {
					continue;
				}
				$skip = false;
			}

			$file = isset( $frame['file'] ) ? self::relativePath( (string) $frame['file'] ) : '';
			$line = isset( $frame['line'] ) ? (int) $frame['line'] : 0;
			$func = isset( $frame['function'] ) ? (string) $frame['function'] : '';

			if ( isset( $frame['class'] ) ) {
				$func = (string) $frame['class'] . ( isset( $frame['type'] ) ? (string) $frame['type'] : '::' ) . $func;
			}

			$location = $file !== '' ? $file . ( $line > 0 ? ':' . $line : '' ) : '(internal)';
			$lines[]  = $location . ' ' . $func . '()';

			if ( count( $lines ) >= 30 ) {
				break;
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * @param string $path Absolute path.
	 * @return string
	 */
	private static function relativePath( $path ) {
		if ( defined( 'ABSPATH' ) && strpos( $path, ABSPATH ) === 0 ) {
			return substr( $path, strlen( ABSPATH ) );
		}

		return $path;
	}

	/**
	 * @param int    $errno   Error level.
	 * @param string $errstr  Message.
	 * @param string $errfile File.
	 * @param int    $errline Line.
	 * @return bool
	 */
	private static function callPreviousHandler( $errno, $errstr, $errfile, $errline ) {
		if ( is_callable( self::$previous_handler ) ) {
			return (bool) call_user_func( self::$previous_handler, $errno, $errstr, $errfile, $errline );
		}

		return false;
	}
}

SheetsPilot_PhpErrorTrace::bootstrap();
