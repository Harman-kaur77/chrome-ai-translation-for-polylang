<?php

class CATFP_Register_Backend_Assets
{

    /**
     * Singleton instance of CATFP_Register_Backend_Assets.
     *
     * @var CATFP_Register_Backend_Assets
     */
    private static $instance;

    /**
     * Get the singleton instance of CATFP_Register_Backend_Assets.
     *
     * @return CATFP_Register_Backend_Assets
     */
    public static function get_instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor for CATFP_Register_Backend_Assets.
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_gutenberg_translate_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_classic_translate_assets'));
        add_action('enqueue_block_assets', array($this, 'block_inline_translation_assets'));
        add_action('admin_enqueue_scripts', array($this, 'classic_inline_translation_assets'));
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_elementor_translate_assets'));
        add_action('admin_enqueue_scripts', array($this, 'catfp_enqueue_admin_assets'));
    }

    public function catfp_enqueue_admin_assets(){
        if(!is_admin()){
            return;
        }

        global $polylang;
        
		if(!$polylang || !property_exists($polylang, 'model') || !function_exists('get_current_screen')){
            return;
		}
        
		$current_screen = get_current_screen();
        
        if(class_exists('CATFP_Helper') && CATFP_Helper::is_translated_post_type($current_screen)){
            wp_enqueue_script('catfp-views-link-admin', CATFP_URL . 'assets/js/catfp-admin-views-link.js', array('jquery'), CATFP_V, true);
        }
    }

    /**
     * Register block translator assets.
     */
    public function block_inline_translation_assets()
    {

        if (defined('POLYLANG_VERSION')) {
            $this->enqueue_inline_translation_assets('block');
        }
    }

    /**
     * Register backend assets.
     */
    public function enqueue_gutenberg_translate_assets()
    {
        $current_screen = get_current_screen();
        if (
            isset($_GET['from_post'], $_GET['new_lang'], $_GET['_wpnonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'new-post-translation')
        ) {
            if (method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor()) {
                $from_post_id = isset($_GET['from_post']) ? absint($_GET['from_post']) : 0;
                
                global $post;
                
                if (null === $post || 0 === $from_post_id) {
                    return;
                }
                
                $lang           = isset($_GET['new_lang']) ? sanitize_key($_GET['new_lang']) : '';

                $editor = '';
                if ('builder' === get_post_meta($from_post_id, '_elementor_edit_mode', true) && defined('ELEMENTOR_VERSION')) {
                    $source_lang_name = pll_get_post_language($from_post_id, 'slug');
                    $this->enqueue_elementor_confirm_box_assets($from_post_id, $lang, $source_lang_name, 'gutenberg');
                    $editor = 'Elementor';
                }
                if ('on' === get_post_meta($from_post_id, '_et_pb_use_builder', true) && defined('ET_CORE')) {
                    $editor = 'Divi';
                }

                if (in_array($editor, array('Elementor', 'Divi'), true)) {
                    return;
                }

                $languages = PLL()->model->get_languages_list();

                $lang_object = array();
                foreach ($languages as $lang_obj) {
                    $lang_object[$lang_obj->slug] = $lang_obj->name;
                }

                $post_translate = PLL()->model->is_translated_post_type($post->post_type);
                
                $post_type      = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';

                if ($post_translate && $lang && $post_type) {
                    $data = array(
                        'action_fetch'       => 'catfp_fetch_post_content',
                        'action_block_rules' => 'catfp_block_parsing_rules',
                        'parent_post_id'     => $from_post_id,
                    );

                    $this->enqueue_automatic_translate_assets(pll_get_post_language($from_post_id, 'slug'), $lang, 'gutenberg', $data);
                }
            }
        }
    }

    
    public function enqueue_classic_translate_assets()
    {
        global $post;
        $current_screen = get_current_screen();
        $post_translate_status = isset($post) ? get_post_meta($post->ID, '_catfp_translate_status', true) : '';
        $post_parent_post_id = isset($post) ? get_post_meta($post->ID, '_catfp_parent_post_id', true) : '';

        if(isset($current_screen) && isset($current_screen->id) && $current_screen->id === 'edit-page'){
            return;
        }

        if (
            isset($_GET['from_post'], $_GET['new_lang'], $_GET['_wpnonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'new-post-translation')) {
            $current_screen = get_current_screen();

            if (method_exists($current_screen, 'is_block_editor') && !$current_screen->is_block_editor()) {
                $from_post_id = isset($_GET['from_post']) ? absint($_GET['from_post']) : 0;
                $from_post_id = !empty($post_parent_post_id) ? $post_parent_post_id : $from_post_id;

                if (null === $post || 0 === $from_post_id) {
                    return;
                }

                $lang           = isset($_GET['new_lang']) ? sanitize_key($_GET['new_lang']) : '';

                if(!empty($post_translate_status) && $post_translate_status === 'pending') {
                    $lang = pll_get_post_language($post->ID, 'slug');
                }

                $editor = '';
                if ('builder' === get_post_meta($from_post_id, '_elementor_edit_mode', true) && defined('ELEMENTOR_VERSION')) {
                    $source_lang_name = pll_get_post_language($from_post_id, 'slug');
                    $this->enqueue_elementor_confirm_box_assets($from_post_id, $lang, $source_lang_name, 'classic');
                    $editor = 'Elementor';
                }
                if ('on' === get_post_meta($from_post_id, '_et_pb_use_builder', true) && defined('ET_CORE')) {
                    $editor = 'Divi';
                }

                if (in_array($editor, array('Elementor', 'Divi'), true)) {
                    return;
                }

                $languages = PLL()->model->get_languages_list();

                $lang_object = array();
                foreach ($languages as $lang_obj) {
                    $lang_object[$lang_obj->slug] = $lang_obj->name;
                }

                $post_translate = PLL()->model->is_translated_post_type($post->post_type);
               

                if ($post_translate && $lang && !empty($lang)) {

                    $data = array(
                        'action_fetch'       => 'catfp_fetch_post_content',
                        'parent_post_id'     => $from_post_id,
                        'action_update_status' => 'catfp_update_classic_translate_status',
                        'classic_status_key' => wp_create_nonce('catfp_classic_translate_nonce'),
                    );

                    $parent_page_content = get_the_content(null, false, $from_post_id);
                    $block_comment_tag = preg_match('/<!--[\s\S]*?-->/s', $parent_page_content) && strpos($parent_page_content, '<!--') < strpos($parent_page_content, '-->');
                    
                    if($block_comment_tag){
                        $data['blockCommentTag']="true";       
                    }

                    $this->enqueue_automatic_translate_assets(pll_get_post_language($from_post_id, 'slug'), $lang, 'classic', $data);
                }
            }
        }
    }
    
    public function classic_inline_translation_assets()
    {
        $current_screen = get_current_screen();

        if (method_exists($current_screen, 'is_block_editor') && !$current_screen->is_block_editor()) {
            $this->enqueue_inline_translation_assets('classic');
        }
    }

    public function enqueue_elementor_translate_assets()
    {

        $this->elementor_inline_translation_assets();

        $page_translated = get_post_meta(get_the_ID(), '_catfp_elementor_translated', true);
        $parent_post_language_slug = get_post_meta(get_the_ID(), '_catfp_parent_post_language_slug', true);

        if ((!empty($page_translated) && $page_translated === 'true') || empty($parent_post_language_slug)) {
            return;
        }

        $post_language_slug = pll_get_post_language(get_the_ID(), 'slug');
        $current_post_id = get_the_ID(); // Get the current post ID

        if(!class_exists('\Elementor\Plugin') || !property_exists('\Elementor\Plugin', 'instance') ){
            return;
        }

        $elementor_data = \Elementor\Plugin::$instance->documents->get( $current_post_id )->get_elements_data();


        if ($parent_post_language_slug === $post_language_slug) {
            return;
        }

        $parent_post_id=PLL()->model->post->get_translation($current_post_id, $parent_post_language_slug);

        $meta_fields=get_post_meta($current_post_id);

        $data = array(
            'update_elementor_data' => 'catfp_update_elementor_data',
            'elementorData' => $elementor_data,
            'metaFields' => $meta_fields,
            'parent_post_id' => $parent_post_id,
            'parent_post_title' => get_the_title($parent_post_id),
        );

        wp_enqueue_style('catfp-elementor-translate', CATFP_URL . 'assets/css/catfp-elementor-translate.min.css', array(), CATFP_V);
        $this->enqueue_automatic_translate_assets($parent_post_language_slug, $post_language_slug, 'elementor', $data);
    }

    public function enqueue_automatic_translate_assets($source_lang, $target_lang, $editor_type, $extra_data = array())
    {
        wp_register_style('catfp-automatic-translate-custom', CATFP_URL . 'assets/css/catfp-custom.min.css', array(), CATFP_V);

        $editor_script_asset = include CATFP_DIR_PATH . 'assets/automatic-translate/index.asset.php';
        wp_register_script('catfp-automatic-translate', CATFP_URL . 'assets/automatic-translate/index.js', $editor_script_asset['dependencies'], $editor_script_asset['version'], true);

        $post_type = get_post_type();

        $languages = PLL()->model->get_languages_list();
        $lang_object = array();
        foreach ($languages as $lang) {
            $lang_object[$lang->slug] = array('name' => $lang->name, 'flag' => $lang->flag_url, 'locale' => $lang->locale);
        }
        
        wp_enqueue_style('catfp-automatic-translate-custom');
        
        wp_enqueue_script('catfp-automatic-translate');
        wp_set_script_translations('catfp-automatic-translate', 'chrome-ai-translation-for-polylang', CATFP_DIR_PATH . 'languages');


        $post_id = get_the_ID();

        if (!isset(PLL()->options['sync']) || (isset(PLL()->options['sync']) && !in_array('post_meta', PLL()->options['sync']))) {
            $extra_data['postMetaSync'] = 'false';
        } else {
            $extra_data['postMetaSync'] = 'true';
        }

        $data = array_merge(array(
            'ajax_url'           => admin_url('admin-ajax.php'),
            'ajax_nonce'         => wp_create_nonce('catfp_translate_nonce'),
            'catfp_url'           => esc_url(CATFP_URL),
            'admin_url'          => admin_url(),
            'update_translate_data' => 'catfp_update_translate_data',
            'source_lang'        => $source_lang,
            'target_lang'        => $target_lang,
            'languageObject'     => $lang_object,
            'post_type'          => $post_type,
            'editor_type'        => $editor_type,
            'current_post_id'    => $post_id,
        ), $extra_data);

        if(!isset(PLL()->options['sync']) || (isset(PLL()->options['sync']) && !in_array('post_meta', PLL()->options['sync']))){
            $data['postMetaSync'] = 'false';
        }else{
            $data['postMetaSync'] = 'true';
        }

        wp_localize_script(
            'catfp-automatic-translate',
            'catfp_global_object',
            $data
        );
    }

    /**
     * Enqueue the elementor widget translator script.
     */
    public function elementor_inline_translation_assets()
    {
        if (defined('POLYLANG_VERSION')) {
            $this->enqueue_inline_translation_assets(
                'elementor', 
                array(
                    'backbone-marionette',
                    'elementor-common',
                    'elementor-web-cli',
                    'elementor-editor-modules',
                )
            );
        }
    }

    public function enqueue_elementor_confirm_box_assets($parent_post_id, $target_lang_name, $source_lang_name, $editor_type='gutenberg')
    {
        $post_id = get_the_ID();

        $source_lang_name=PLL()->model->get_language($source_lang_name);
        $target_lang_name=PLL()->model->get_language($target_lang_name);

        wp_enqueue_script('catfp-elementor-confirm-box', CATFP_URL . 'assets/js/catfp-elementor-translate-confirm-box.js', array('jquery', 'wp-i18n'), CATFP_V, true);

        wp_localize_script('catfp-elementor-confirm-box', 'catfpElementorConfirmBoxData',
            array('postId' => $post_id, 'parentPostId' => $parent_post_id, 'sourceLangSlug' => $source_lang_name->slug, 'targetLangSlug' => $target_lang_name->slug, 'sourceLangName' => $source_lang_name->name, 'targetLangName' => $target_lang_name->name, 'editorType' => $editor_type)
        );

        wp_enqueue_style('catfp-elementor-confirm-box', CATFP_URL . 'assets/css/catfp-elementor-translate-confirm-box.css', array(), CATFP_V);
    }

    private function enqueue_inline_translation_assets( $type = 'block', $extra_dependencies = array() ) {

		global $post;

		if(!isset($post) || !isset($post->ID)){
			return;
		}

		if (defined('POLYLANG_VERSION')) {
            if (function_exists('pll_current_language')) {
                $current_language = pll_current_language();
                $current_language_name = pll_current_language('name');
            } else {
                $current_language = '';
                $current_language_name = '';
            }

            $editor_script_asset = require_once CATFP_DIR_PATH . 'assets/'.sanitize_file_name( $type ).'-inline-translation/index.asset.php';
            $core_modal_script_asset = include CATFP_DIR_PATH . 'assets/inline-translate-modal/index.asset.php';

            if(!is_array($editor_script_asset)) {
                $editor_script_asset = array(
                    'dependencies' => array(),
                    'version' => CATFP_V,
                );
            }

            if(!is_array($core_modal_script_asset)) {
                $core_modal_script_asset = array(
                    'dependencies' => array(),
                    'version' => CATFP_V,
                );
            }

            wp_register_script( 'catfp-inline-translate-modal', CATFP_URL . 'assets/inline-translate-modal/index.js' , array_merge( $core_modal_script_asset['dependencies'] ), $core_modal_script_asset['version'], true );
    
            $extra_dependencies[] = 'catfp-inline-translate-modal';

            wp_register_script(
                'catfp-'.sanitize_file_name( $type ).'-inline-translation',
                CATFP_URL . 'assets/'.sanitize_file_name( $type ).'-inline-translation/index.js',
                array_merge(
                    $editor_script_asset['dependencies'], $extra_dependencies
                ),
                $editor_script_asset['version'],
                true
            );

            wp_enqueue_script( 'catfp-inline-translate-modal' );

            wp_enqueue_script('catfp-' . sanitize_file_name( $type ) . '-inline-translation');

            if ($current_language && $current_language !== '') {
                wp_localize_script(
                    'catfp-inline-translate-modal',
                    'catfpInlineTranslation',
                    array(
                        'pageLanguage' => $current_language,
                        'pageLanguageName' => $current_language_name,
                    )
                );
            }
        }
	}
}
