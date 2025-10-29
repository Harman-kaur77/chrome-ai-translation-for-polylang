<?php
/*
Plugin Name: Chrome AI Translation For Polylang
Version: 1.0.0
Description: Chrome AI Translation For Polylang simplifies your translation process by automatically translating all pages/posts content from one language to another by using Chrome Translator API (https://developer.chrome.com/docs/ai/translator-api).
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: chrome-ai-translation-for-polylang
Requires Plugins: polylang
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'CATFP_V' ) ) {
	define( 'CATFP_V', '1.0.0' );
}
if ( ! defined( 'CATFP_DIR_PATH' ) ) {
	define( 'CATFP_DIR_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'CATFP_URL' ) ) {
	define( 'CATFP_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'CATFP_FILE' ) ) {
	define( 'CATFP_FILE', __FILE__ );
}

if ( ! class_exists( 'Chrome_AI_Translation_For_Polylang' ) ) {
	final class Chrome_AI_Translation_For_Polylang {

		/**
		 * Plugin instance.
		 *
		 * @var Chrome_AI_Translation_For_Polylang
		 * @access private
		 */
		private static $instance = null;

		/**
		 * Get plugin instance.
		 *
		 * @return Chrome_AI_Translation_For_Polylang
		 * @static
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
		/**
		 * Constructor
		 */
		private function __construct() {
			$this->catfp_load_files();
			add_action( 'plugins_loaded', array( $this, 'catfp_init' ) );
			register_activation_hook( CATFP_FILE, array( $this, 'catfp_activate' ) );
			register_deactivation_hook( CATFP_FILE, array( $this, 'catfp_deactivate' ) );
			add_action('init', array($this, 'load_plugin_textdomain'));
			add_action('current_screen', array($this, 'catfp_append_view_languages_link'));
			add_action( 'media_buttons', array( $this, 'catfp_classic_editor_button' ) );
		}
		
		/*
		|------------------------------------------------------------------------
		|  Get user info
		|------------------------------------------------------------------------
		*/

		
		public function catfp_append_view_languages_link($current_screen) {
			if(is_admin()) {

				global $polylang;
        
				if(!$polylang || !property_exists($polylang, 'model')){
					return;
				}

				$translated_post_types = $polylang->model->get_translated_post_types();
				$translated_post_types = array_keys($translated_post_types);

				if(!in_array($current_screen->post_type, $translated_post_types)){
					return;
				}

				add_filter( "views_{$current_screen->id}", array($this, 'list_table_views_filter') );
			}
		}

		public function list_table_views_filter($views) {
			if(!function_exists('PLL') || !function_exists('pll_count_posts') || !function_exists('get_current_screen') || !property_exists(PLL(), 'model') || !function_exists('pll_current_language')){
				return $views;
			}

			$pll_languages =  PLL()->model->get_languages_list();
			$current_screen=get_current_screen();
			$index=0;
			$total_languages=count($pll_languages);
			$pll_active_languages=pll_current_language();
			
			$post_type=isset($current_screen->post_type) ? $current_screen->post_type : '';
			$post_status=(isset($_GET['post_status']) && 'trash' === sanitize_text_field(wp_unslash($_GET['post_status']))) ? 'trash' : 'publish';
			$all_translated_post_count=0;
			$list_html='';
			if(count($pll_languages) > 1){
				echo "<div class='catfp_subsubsub' style='display:none; clear:both;'>
					<ul class='subsubsub catfp_subsubsub_list'>";
					foreach($pll_languages as $lang){
	
						$flag=isset($lang->flag) ? $lang->flag : '';
						$language_slug=isset($lang->slug) ? $lang->slug : '';
						$current_class=$pll_active_languages && $pll_active_languages == $language_slug ? 'current' : '';
						$translated_post_count=pll_count_posts($language_slug, array('post_type'=>$post_type, 'post_status'=>$post_status));

						if('publish' === $post_status){
							$draft_post_count=pll_count_posts($language_slug, array('post_type'=>$post_type, 'post_status'=>'draft'));
							$translated_post_count+=$draft_post_count;

							$pending_post_count=pll_count_posts($language_slug, array('post_type'=>$post_type, 'post_status'=>'pending'));
							$translated_post_count+=$pending_post_count;
						}

						$all_translated_post_count+=$translated_post_count;
						$list_html.="<li class='catfp_pll_lang_".esc_attr($language_slug)."'><a href='edit.php?post_type=".esc_attr($post_type)."&lang=".esc_attr($language_slug)."' class='".esc_attr($current_class)."'>".esc_html( wp_kses( $lang->name, array() ) )." <span class='count'>(".esc_html($translated_post_count).")</span></a>".($index < $total_languages-1 ? ' |&nbsp;' : '')."</li>";
						$index++;
					}

					echo "<li class='catfp_pll_lang_all'><a href='edit.php?post_type=".esc_attr($post_type)."&lang=all"."' class=''>All Languages<span class='count'>(".esc_html($all_translated_post_count).")</span></a> |&nbsp;</li>";

					$allowed = [
						'ul'   => [ 'class' => true ],
						'ol'   => [ 'class' => true ],
						'li'   => [ 'class' => true ],
						'a'    => [ 'href' => true, 'title' => true, 'target' => true, 'rel' => true ],
						'span' => [ 'class' => true, 'aria-hidden' => true ],
						'strong' => [],
						'em'     => [],
					];
					
				echo wp_kses( (string) $list_html, $allowed );
				echo "</ul>
				</div>";
			}

			return $views;
		}

		public function catfp_load_files() {
			require_once CATFP_DIR_PATH . '/helper/class-catfp-helper.php';
			require_once CATFP_DIR_PATH . 'includes/class-catfp-register-backend-assets.php';
			require_once CATFP_DIR_PATH . 'includes/elementor-translate/class-catfp-elementor-translate.php';
		}
		/**
		 * Initialize the Automatic Translation for Polylang plugin.
		 *
		 * @return void
		 */
		function catfp_init() {
			// Check Polylang plugin is installed and active
			global $polylang;
			$catfp_polylang = $polylang;
			if ( isset( $catfp_polylang ) && is_admin() ) {

				require_once CATFP_DIR_PATH . '/helper/class-catfp-ajax-handler.php';
				if ( class_exists( 'CATFP_Ajax_Handler' ) ) {
					CATFP_Ajax_Handler::get_instance();
				}

				add_action( 'add_meta_boxes', array( $this, 'catfp_shortcode_metabox' ) );

				if(class_exists('CATFP_Bulk_Translation')) {
					CATFP_Bulk_Translation::get_instance();
				}

				$this->catfp_register_backend_assets();

				$this->catfp_initialize_elementor_translation();
			} 
		}

		/**
		 * Load plugin textdomain.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'chrome-ai-translation-for-polylang', false, basename( dirname( __FILE__ ) ) . '/languages/' );
		}


		

		/**
		 * Register backend assets for Automatic Translation for Polylang plugin.
		 *
		 * @return void
		 */
		function catfp_register_backend_assets() {
			if(class_exists('CATFP_Register_Backend_Assets')) {
				CATFP_Register_Backend_Assets::get_instance();
			}
		}

		/**
		 * Initialize Elementor Translation.
		 *
		 * @return void
		 */
		function catfp_initialize_elementor_translation() {
			if(class_exists('CATFP_Elementor_Translate')) {
				CATFP_Elementor_Translate::get_instance();
			}
		}

		/**
		 * Register and display the automatic translation metabox.
		 */
		function catfp_shortcode_metabox() {
			if ( isset( $_GET['from_post'], $_GET['new_lang'], $_GET['_wpnonce'] ) &&
				 wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'new-post-translation' ) ) {
				$post_id = isset( $_GET['from_post'] ) ? absint( $_GET['from_post'] ) : 0;

				if ( 0 === $post_id ) {
					return;
				}

				$editor = '';
				if ( 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
					$editor = 'Elementor';
				}
				if ( 'on' === get_post_meta( $post_id, '_et_pb_use_builder', true ) ) {
					$editor = 'Divi';
				}

				$current_screen = get_current_screen();
				if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() && ! in_array( $editor, array( 'Elementor', 'Divi' ), true ) ) {
					if ( 'post-new.php' === $GLOBALS['pagenow'] && isset( $_GET['from_post'], $_GET['new_lang'] ) ) {
						global $post;

						if ( ! ( $post instanceof WP_Post ) ) {
							return;
						}

						if ( ! function_exists( 'PLL' ) || ! PLL()->model->is_translated_post_type( $post->post_type ) ) {
							return;
						}
						add_meta_box( 'catfp-meta-box', __( 'Automatic Translate', 'chrome-ai-translation-for-polylang' ), array( $this, 'catfp_shortcode_text' ), null, 'side', 'high' );
					}
				}
			}
		}

		function catfp_classic_editor_button() {
			global $polylang;
			global $post;

			if(!isset($post) || !isset($post->ID)){
				return;
			}

			$catfp_polylang = $polylang;
			$post_translate_status = get_post_meta($post->ID, '_catfp_translate_status', true);
			$post_parent_post_id = get_post_meta($post->ID, '_catfp_parent_post_id', true);

			if ( isset( $catfp_polylang ) && is_admin() ) {
				if ( (isset( $_GET['from_post'], $_GET['new_lang'], $_GET['_wpnonce'] ) &&
				 wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'new-post-translation' ))) {

				$post_id = isset( $_GET['from_post'] ) ? absint( $_GET['from_post'] ) : 0;
				$post_id = !empty($post_parent_post_id) ? $post_parent_post_id : $post_id;

				if ( 0 === $post_id ) {
					return;
				}
				
				$editor = '';
				if ( 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true ) && defined('ELEMENTOR_VERSION') ) {
					$editor = 'Elementor';
				}
				if ( 'on' === get_post_meta( $post_id, '_et_pb_use_builder', true ) && defined('ET_CORE') ) {
					$editor = 'Divi';
				}

				$current_screen = get_current_screen();
				if ( method_exists( $current_screen, 'is_block_editor' ) && !$current_screen->is_block_editor() && ! in_array( $editor, array( 'Elementor', 'Divi' ), true ) ) {
					if ( ('post-new.php' === $GLOBALS['pagenow'] && isset( $_GET['from_post'], $_GET['new_lang'] )) || (!empty($post_translate_status) && $post_translate_status === 'pending' && !empty($post_parent_post_id)) ) {

						if ( ! ( $post instanceof WP_Post ) ) {
							return;
						}

						if ( ! function_exists( 'PLL' ) || ! PLL()->model->is_translated_post_type( $post->post_type ) ) {
							return;
						}

						if(empty($post_translate_status) && empty($post_parent_post_id)) {
							update_post_meta($post->ID, '_catfp_translate_status', 'pending');
							update_post_meta($post->ID, '_catfp_parent_post_id', $post_id);
						}
						
						echo '<button class="button button-primary" id="catfp-classic-editor-translate-button">' . esc_html__( 'Translate Page', 'chrome-ai-translation-for-polylang' ) . '</button>';
					}
				}
			}
			}
		}

		/**
		 * Display the automatic translation metabox button.
		 */
		function catfp_shortcode_text() {
			if ( isset( $_GET['_wpnonce'] ) &&
				 wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'new-post-translation' ) ) {
				$target_language = '';
				$source_language = pll_get_post_language(absint( $_GET['from_post'] ), 'name');
				if ( function_exists( 'PLL' ) ) {
					$target_code = isset( $_GET['new_lang'] ) ? sanitize_key( $_GET['new_lang'] ) : '';
					$languages   = PLL()->model->get_languages_list();
					foreach ( $languages as $lang ) {
						if ( $lang->slug === $target_code ) {
							$target_language = $lang->name;
						}
					}
				}
				?>
				<input type="button" class="button button-primary" name="catfp_meta_box_translate" id="catfp-translate-button" value="<?php echo esc_attr__( 'Translate Page', 'chrome-ai-translation-for-polylang' ); ?>" readonly/><br><br>
				<p style="margin-bottom: .5rem;"><?php echo esc_html( sprintf( __( 'Translate or duplicate content from %s to %s', 'chrome-ai-translation-for-polylang' ), $source_language, $target_language ) ); ?></p>
				<?php
			}
		}

		/*
		|----------------------------------------------------------------------------
		| Run when activate plugin.
		|----------------------------------------------------------------------------
		*/
		public static function catfp_activate() {
			update_option( 'catfp-v', CATFP_V );
			update_option( 'catfp-type', 'FREE' );
			update_option( 'catfp-installDate', gmdate( 'Y-m-d h:i:s' ) );

			
		}

		/*
		|----------------------------------------------------------------------------
		| Run when de-activate plugin.
		|----------------------------------------------------------------------------
		*/
		public static function catfp_deactivate() {
			
		}

	}

}

function Chrome_AI_Translation_For_Polylang() {
	return Chrome_AI_Translation_For_Polylang::get_instance();
}

$Chrome_AI_Translation_For_Polylang = Chrome_AI_Translation_For_Polylang();
