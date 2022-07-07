<?php
/*
Plugin Name: Smeedijzer Permalinks
Description: Voegt opties toe aan de permalinks instellingenpagina voor het herschreven van de basis voor permalinks.
Version: 1.0.0
Author: Smeedijzer
*/

class Smeedijzer__Permalink__Extend {

    public const config = [
        'search_base'              => [
            'default_value' => 'zoeken',
            'field_title' => 'Zoekbasis',
        ],
        'author_base'              => [
            'default_value' => 'auteur',
            'field_title' => 'Auteurbasis',
        ],
        'comments_base'            => [
            'default_value' => 'reacties',
            'field_title' => 'Reactiesbasis',
        ],
        'comments_pagination_base' => [
            'default_value' => 'reactie-pagina',
            'field_title' => 'Reactiepagineringbasis',
        ],
        'pagination_base'          => [
            'default_value' => 'pagina',
            'field_title' => 'Pagineringbasis',
        ],
        'feed_base'                => [
            'default_value' => 'feed',
            'field_title' => 'Feedbasis',
        ],
    ];

    private $alias;

    public function __construct() {
        add_action( 'init', [ $this, 'load_plugin_settings' ] );
        add_action( 'load-options-permalink.php', [ $this, 'handle_plugin_settings' ] );
        add_action( 'search_rewrite_rules', [ $this, 'rewrite_empty_search' ] );
        add_action( 'template_redirect', [ $this, 'redirect_to_permalink' ] );
    }

    private function get_alias( $config_key, $default_value ) {
        if ( ! isset( $this->alias[ $config_key ] ) ) {
            $this->alias[ $config_key ] = get_option( $config_key, $default_value );
        }

        return $this->alias[ $config_key ];
    }

    public function load_plugin_settings() {
        global $wp_rewrite;

        foreach ( self::config as $key => $data ) {
            $base       = $this->get_alias( $key, $data['default_value'] );
            $wp_rewrite->{$key} = remove_accents( mb_strtolower( $base ) );
        }
    }

    public function handle_plugin_settings() {
        foreach ( self::config as $key => $data ) {
            if ( ! empty( $_POST[ $key ] ) ) {
                update_option( $key, sanitize_text_field( $_POST[ $key ] ), false );
            }

            add_settings_field(
                $key,
                $data['field_title'], // [ $this, 'display_plugin_settings' ],
                function () use ( $key, $data ) {
                    $this->display_plugin_settings( $key, $data );
                },
                'permalink', 'optional', []
            );
        }
    }

    public function display_plugin_settings($key, $data ) {
       echo sprintf( '<input type="text" value="%s" name="%s" class="regular-text">', esc_attr( $this->get_alias( $key, $data['default_value']) ), $key );
    }

    // Fix for empty search queries redirecting
    public function rewrite_empty_search( $rewrite ) {
        global $wp_rewrite;
        $rules   = array(
            $wp_rewrite->search_base . '/?$' => 'index.php?s=',
        );
        $rewrite = $rewrite + $rules;

        return $rewrite;
    }

    public function redirect_to_permalink() {
        global $wp_rewrite;

        if ( ! isset( $wp_rewrite ) || ! is_object( $wp_rewrite ) || ! $wp_rewrite->using_permalinks() ) {
            return false;
        }

        if ( ! is_search() || strpos( $_SERVER['REQUEST_URI'], "/{$wp_rewrite->search_base}" ) !== false ) {
            return false;
        }

        $permalink = sprintf( '%s/%s/%s', get_home_url(), $wp_rewrite->search_base, urlencode( get_query_var( 's' ) ) );
        wp_redirect( $permalink, 301 );
        exit();
    }
}

new Smeedijzer__Permalink__Extend();

?>
