<?php
/**
 * Bsf Custom Fonts Admin Ui
 *
 * @since  1.0.0
 * @package Bsf_Custom_Fonts
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'Bsf_Custom_Fonts_Render' ) ) :

	/**
	 * Bsf_Custom_Fonts_Render
	 */
	class Bsf_Custom_Fonts_Render {

		/**
		 * Instance of Bsf_Custom_Fonts_Render
		 *
		 * @since  1.0.0
		 * @var (Object) Bsf_Custom_Fonts_Render
		 */
		private static $_instance = null;

		/**
		 * Member Varible
		 *
		 * @var string $font_css
		 */
		protected $font_css = '';

		/**
		 * Instance of Bsf_Custom_Fonts_Admin.
		 *
		 * @since  1.0.0
		 *
		 * @return object Class object.
		 */
		public static function get_instance() {
			if ( ! isset( self::$_instance ) ) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		/**
		 * Constructor.
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

			add_action( 'admin_notices', array( $this, 'theme_update_notice' ) );

			// Enqueue the custom fonts.
			add_action( 'astra_render_fonts', array( $this, 'render_fonts' ) );

			// Delete custom fonts action.
			add_action( 'delete_term', array( $this, 'delete_custom_fonts_fallback_astra' ), 10, 5 );

			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

			// add Custom Font list into Astra customizer.
			add_action( 'astra_customizer_font_list', array( $this, 'add_customizer_font_list' ) );

			// Beaver builder theme customizer, beaver buidler page builder.
			add_filter( 'fl_theme_system_fonts', array( $this, 'bb_custom_fonts' ) );
			add_filter( 'fl_builder_font_families_system', array( $this, 'bb_custom_fonts' ) );

			// Elementor page builder.
			add_action( 'elementor/controls/controls_registered', array( $this, 'elementor_custom_fonts' ), 10, 1 );

			// Add font files style.
			add_action( 'wp_enqueue_scripts', array( $this, 'add_style' ) );
		}


		/**
		 * Add Custom Font list to BB theme and BB Page Builder
		 *
		 * @since  1.0.4
		 * @param array $bb_fonts font families added by bb.
		 */
		function bb_custom_fonts( $bb_fonts ) {

			$fonts        = Bsf_Custom_Fonts_Taxonomy::get_fonts();
			$custom_fonts = array();
			if ( ! empty( $fonts ) ) :
				foreach ( $fonts as $font_family_name => $fonts_url ) :
					$custom_fonts[ $font_family_name ] = array(
						'fallback' => 'Verdana, Arial, sans-serif',
						'weights'  => array( '400' ),
					);
				endforeach;
			endif;

			return array_merge( $custom_fonts, $bb_fonts );
		}

		/**
		 * Add Custom Font list to Elementor Page Builder
		 *
		 * @since  1.0.4
		 * @param array $controls_registry font families added by elementor.
		 */
		function elementor_custom_fonts( $controls_registry ) {
			$fonts           = Bsf_Custom_Fonts_Taxonomy::get_fonts();
			$fonts_elementor = array( 'Use Any Fonts' => array() );
			if ( ! empty( $fonts ) ) :
				foreach ( $fonts as $font_family_name => $fonts_url ) :
					$fonts_elementor[ $font_family_name ] = 'system';
				endforeach;
			endif;

			$fonts     = $controls_registry->get_control( 'font' )->get_settings( 'options' );
			$new_fonts = array_merge( $fonts_elementor, $fonts );
			$controls_registry->get_control( 'font' )->set_settings( 'options', $new_fonts );
		}

		/**
		 * Enqueue Scripts
		 *
		 * @since 1.0.4
		 */
		public function add_style() {
			$fonts = Bsf_Custom_Fonts_Taxonomy::get_fonts();
			if ( ! empty( $fonts ) ) {
				foreach ( $fonts  as $load_font_name => $load_font ) {
					$this->render_font_css( $load_font_name );
				}
				wp_enqueue_style( 'bsf-custom-fonts-front', BSF_CUSTOM_FONTS_URI . 'assets/css/bsf-custom-fonts-front.css', array(), BSF_CUSTOM_FONTS_VER );
				wp_add_inline_style( 'bsf-custom-fonts-front', $this->font_css );
			}
		}

		/**
		 * Add Custom Font list into Astra customizer.
		 *
		 * @since  1.0.0
		 * @param string $value selected font family.
		 */
		public function add_customizer_font_list( $value ) {

			$fonts = Bsf_Custom_Fonts_Taxonomy::get_fonts();

				echo '<optgroup label="Custom">';

			foreach ( $fonts as $font => $links ) {
				echo '<option value="' . esc_attr( $font ) . '" ' . selected( $font, $value, false ) . '>' . esc_attr( $font ) . '</option>';
			}
		}

		/**
		 * Enqueue Admin Scripts
		 *
		 * @since 1.0.0
		 */
		public function enqueue_admin_scripts() {

			wp_enqueue_style( 'bsf-custom-fonts-css', BSF_CUSTOM_FONTS_URI . 'assets/css/bsf-custom-fonts.css', array(), BSF_CUSTOM_FONTS_VER );
			wp_enqueue_media();
			wp_enqueue_script( 'bsf-custom-fonts-js', BSF_CUSTOM_FONTS_URI . 'assets/js/bsf-custom-fonts.js', array(), BSF_CUSTOM_FONTS_VER );

		}

		/**
		 * Enqueue Render Fonts
		 *
		 * @since 1.0.0
		 * @param array $load_fonts astra fonts.
		 */
		public function render_fonts( $load_fonts ) {

			$fonts = Bsf_Custom_Fonts_Taxonomy::get_fonts();

			foreach ( $load_fonts  as $load_font_name => $load_font ) {
				if ( array_key_exists( $load_font_name, $fonts ) ) {
					unset( $load_fonts[ $load_font_name ] );
				}
			}
			return $load_fonts;
		}

		/**
		 * Create css for font-face
		 *
		 * @since 1.0.0
		 * @param array $font selected font from custom font list.
		 */
		private function render_font_css( $font ) {
			$fonts = Bsf_Custom_Fonts_Taxonomy::get_links_by_name( $font );

			foreach ( $fonts as $font => $links ) :
				$css  = '@font-face { font-family:' . esc_attr( $font ) . ';';
				$css .= 'src:';
				$arr  = array();
				if ( $links['font_woff_2'] ) {
					$arr[] = 'url(' . esc_url( $links['font_woff_2'] ) . ") format('woff2')";
				}
				if ( $links['font_woff'] ) {
					$arr[] = 'url(' . esc_url( $links['font_woff'] ) . ") format('woff')";
				}
				if ( $links['font_ttf'] ) {
					$arr[] = 'url(' . esc_url( $links['font_ttf'] ) . ") format('truetype')";
				}
				if ( $links['font_svg'] ) {
					$arr[] = 'url(' . esc_url( $links['font_svg'] ) . '#' . esc_attr( strtolower( str_replace( ' ', '_', $font ) ) ) . ") format('svg')";
				}
				$css .= join( ', ', $arr );
				$css .= ';}';
			endforeach;

			$this->font_css .= $css;
		}

		/**
		 * Set default 'inherit' if custom font is selected in customizer if this is deleted.
		 *
		 * @since 1.0.0
		 * @param int    $term Term ID.
		 * @param int    $tt_id Term taxonomy ID.
		 * @param string $taxonomy Taxonomy slug.
		 * @param mixed  $deleted_term deleted term.
		 * @param object $object_ids objects ids.
		 */
		function delete_custom_fonts_fallback_astra( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ) {

			if ( defined( 'ASTRA_THEME_SETTINGS' ) ) {
				// get astra options.
				$options = get_option( ASTRA_THEME_SETTINGS );
				foreach ( $options as $key => $value ) {
					if ( $value == $deleted_term->name ) {
						// set default inherit if custom font is deleted.
						$options[ $key ] = 'inherit';
					}
				}
				// update astra options.
				update_option( ASTRA_THEME_SETTINGS, $options );
			}
		}

		/**
		 * Theme update notice.
		 *
		 * @since 1.0.0
		 */
		function theme_update_notice() {
			if ( defined( 'ASTRA_THEME_VERSION' ) ) {
				if ( version_compare( ASTRA_THEME_VERSION, '1.0.16', '<' ) ) {
				?>
				<div class="notice notice-error is-dismissible">
					<p>
					<?php
					printf(
						/* translators: 1: Astra theme from wordpress.org*/
							__( 'Custom Fonts Plugin requires minimum 1.0.16 version of the Astra Theme.', 'custom-fonts' ),
						esc_url( 'https://downloads.wordpress.org/theme/astra.zip' )
					);
					?>
					</p>
				</div>
			<?php
				}
			}
		}

		/**
		 * Loads textdomain for the plugin.
		 *
		 * @since 1.0.0
		 */
		function load_textdomain() {
			load_plugin_textdomain( 'custom-fonts' );
		}
	}

	/**
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Bsf_Custom_Fonts_Render::get_instance();

endif;





