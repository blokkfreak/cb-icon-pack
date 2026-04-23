<?php
/**
 * Plugin Name: Creativebowl Icon-Pack
 * Description: Custom SVG Icon Pack for Elementor Pro (Line + Solid variants)
 * Version: 2.0.0
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Requires Plugins: elementor-pro
 * Author: Creativebowl
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CB_ICON_PACK_VERSION', '2.0.0' );
define( 'CB_ICON_PACK_PATH', plugin_dir_path( __FILE__ ) );
define( 'CB_ICON_PACK_URL', plugin_dir_url( __FILE__ ) );

/* =====================================================================
   AUTO-UPDATER (Plugin Update Checker via GitHub)
   Token wird in wp-config.php als Konstante CB_ICON_PACK_GITHUB_TOKEN
   definiert -- nie direkt hier im Code hinterlegen.
   ===================================================================== */
if ( file_exists( CB_ICON_PACK_PATH . 'plugin-update-checker/load-v5p5.php' ) ) {
	require_once CB_ICON_PACK_PATH . 'plugin-update-checker/load-v5p5.php';

	$cb_updater = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/blokkfreak/cb-icon-pack/',
		__FILE__,
		'cb-icon-pack'
	);

	// Read-only GitHub Token fuer private Repo
	$cb_updater->setAuthentication( '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' );

	// GitHub Releases als Update-Quelle (nicht den main-Branch)
	$cb_updater->setBranch( 'main' );
	$cb_updater->getVcsApi()->enableReleaseAssets();
}

/**
 * Main plugin class.
 *
 * Registers two custom SVG icon libraries ("CB Icons - Line" and
 * "CB Icons - Solid") with Elementor Pro's icon manager and renders selected
 * icons as inline SVG on the frontend so they inherit color through CSS
 * currentColor / Elementor's color picker.
 */
final class CB_Icon_Pack {

	/** @var self|null Singleton instance. */
	private static $instance = null;

	/**
	 * Variant configuration.
	 *
	 * Each entry defines a separate tab in Elementor's icon picker. All
	 * variants share the same base CSS class (`cb-icon`) and inline-SVG
	 * frontend rendering — only the mask-image URLs and the JSON index
	 * differ.
	 *
	 * @var array<string,array{label:string,labelIcon:string}>
	 */
	private const VARIANTS = [
		'line'  => [
			'label'     => 'CB Line Icons',
			'labelIcon' => 'cb-icon cb-icon-line-vegetable-carrot',
		],
		'solid' => [
			'label'     => 'CB Solid Icons',
			'labelIcon' => 'cb-icon cb-icon-solid-vegetable-carrot',
		],
	];

	/** @var array<string,array<string,string>> Cached icon-slug → relative-path per variant. */
	private $icon_paths = [];

	/**
	 * Get or create the singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire up all hooks.
	 */
	private function __construct() {
		add_filter( 'elementor/icons_manager/additional_tabs', [ $this, 'register_icon_tabs' ] );
		add_filter( 'elementor/icons_manager/render_icon', [ $this, 'render_svg_icon' ], 10, 4 );
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'enqueue_frontend_css' ] );
	}

	/* ==================================================================
	   1. REGISTER THE ICON TABS (Line + Solid)
	   ================================================================== */

	/**
	 * Register both "CB Icons - Line" and "CB Icons - Solid" tabs.
	 *
	 * Cache-busting strategy: the `ver` parameter is built from the plugin
	 * version + the mtime of the CSS file, so whenever the build script
	 * regenerates assets, browsers pick up the new file automatically
	 * instead of serving a stale cached copy.
	 *
	 * @param array $tabs Existing icon library tabs.
	 * @return array Modified tabs array.
	 */
	public function register_icon_tabs( array $tabs ): array {
		foreach ( self::VARIANTS as $variant => $meta ) {
			$css_file  = CB_ICON_PACK_PATH . "assets/icons-{$variant}.css";
			$json_file = CB_ICON_PACK_PATH . "assets/icons-{$variant}.json";

			$css_ver  = file_exists( $css_file )  ? filemtime( $css_file )  : CB_ICON_PACK_VERSION;
			$json_ver = file_exists( $json_file ) ? filemtime( $json_file ) : CB_ICON_PACK_VERSION;

			$tab_name = "cb-icons-{$variant}";

			$tabs[ $tab_name ] = [
				'name'               => $tab_name,
				'label'              => esc_html( $meta['label'] ),
				'url'                => CB_ICON_PACK_URL . "assets/icons-{$variant}.css",
				'enqueue'            => [],
				'prefix'             => "cb-icon-{$variant}-",
				'displayPrefix'      => 'cb-icon',
				'labelIcon'          => $meta['labelIcon'],
				'ver'                => CB_ICON_PACK_VERSION . '.' . $css_ver,
				'fetchJson'          => CB_ICON_PACK_URL . "assets/icons-{$variant}.json?ver={$json_ver}",
				'native_svg_support' => true,
			];
		}

		return $tabs;
	}

	/* ==================================================================
	   2. FRONTEND CSS
	   ================================================================== */

	/**
	 * Inject minimal CSS for inline SVG icons on the frontend.
	 *
	 * The mask-image rules from the per-variant CSS files are only needed
	 * in the editor picker. On the frontend we render actual <svg> elements,
	 * so we only need basic sizing and fill rules.
	 */
	public function enqueue_frontend_css(): void {
		$css = '
			.cb-icon-svg {
				display: inline-block;
				width: 1em;
				height: 1em;
				fill: currentColor;
			}
		';
		wp_add_inline_style( 'elementor-frontend', $css );
	}

	/* ==================================================================
	   3. RENDER INLINE SVG
	   ================================================================== */

	/**
	 * Render CB Icons as inline SVG instead of the default <i> tag.
	 *
	 * Handles both variants: dispatches to the correct paths map based on
	 * the `library` field ('cb-icons-line' or 'cb-icons-solid').
	 *
	 * @param bool|string $render_markup False if no handler has claimed the icon yet.
	 * @param array       $icon          { 'value' => 'cb-icon-{variant}-{slug}', 'library' => 'cb-icons-{variant}' }
	 * @param array       $attributes    HTML attributes from the widget (aria-label, etc.).
	 * @param string      $tag           Fallback HTML tag (usually 'i').
	 * @return bool|string True if we rendered the icon, original value otherwise.
	 */
	public function render_svg_icon( $render_markup, array $icon, array $attributes, string $tag ) {
		if ( empty( $icon['value'] ) || empty( $icon['library'] ) ) {
			return $render_markup;
		}

		// Library must be one of our registered variant tabs.
		if ( ! preg_match( '/^cb-icons-(line|solid)$/', $icon['library'], $m ) ) {
			return $render_markup;
		}
		$variant = $m[1];

		// Value can be "cb-icon-line-arrows-back" or "cb-icon cb-icon-line-arrows-back".
		$icon_class = $icon['value'];
		if ( str_contains( $icon_class, ' ' ) ) {
			$parts      = explode( ' ', $icon_class );
			$icon_class = end( $parts );
		}

		$svg_path = $this->resolve_svg_path( $variant, $icon_class );
		if ( ! $svg_path || ! file_exists( $svg_path ) ) {
			return $render_markup;
		}

		$svg = file_get_contents( $svg_path );
		if ( empty( $svg ) ) {
			return $render_markup;
		}

		// Strip XML declaration (not needed for inline SVG in HTML5).
		$svg = preg_replace( '/<\?xml[^>]*\?>\s*/', '', $svg );

		// Merge Elementor's attributes into the <svg> tag.
		$attr_str = '';
		foreach ( $attributes as $key => $value ) {
			$attr_str .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}

		$svg = preg_replace(
			'/<svg\b/',
			'<svg' . $attr_str . ' class="cb-icon-svg" role="img" aria-hidden="true"',
			$svg,
			1
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG file.
		echo $svg;

		return true;
	}

	/* ==================================================================
	   4. HELPER – RESOLVE ICON SLUG → FILE PATH
	   ================================================================== */

	/**
	 * Map an icon CSS class to its absolute filesystem path.
	 *
	 * Reads the per-variant "paths" map from icons-{variant}.json (cached
	 * in memory after first call) and prepends the plugin directory.
	 *
	 * @param string $variant    'line' or 'solid'.
	 * @param string $icon_class e.g. "cb-icon-line-arrows-3d-turn".
	 * @return string|null Absolute path to the SVG file, or null.
	 */
	private function resolve_svg_path( string $variant, string $icon_class ): ?string {
		if ( ! isset( $this->icon_paths[ $variant ] ) ) {
			$json_file = CB_ICON_PACK_PATH . "assets/icons-{$variant}.json";

			if ( ! file_exists( $json_file ) ) {
				$this->icon_paths[ $variant ] = [];
				return null;
			}

			$data                         = json_decode( file_get_contents( $json_file ), true );
			$this->icon_paths[ $variant ] = $data['paths'] ?? [];
		}

		return isset( $this->icon_paths[ $variant ][ $icon_class ] )
			? CB_ICON_PACK_PATH . $this->icon_paths[ $variant ][ $icon_class ]
			: null;
	}

	/* ==================================================================
	   SINGLETON GUARDS
	   ================================================================== */

	private function __clone() {}

	public function __wakeup() {
		throw new \RuntimeException( 'Cannot unserialize a singleton.' );
	}
}

/* =====================================================================
   BOOTSTRAP
   ===================================================================== */

add_action( 'plugins_loaded', static function (): void {
	// Only initialize if Elementor is active.
	if ( did_action( 'elementor/loaded' ) ) {
		CB_Icon_Pack::instance();
	}
} );
