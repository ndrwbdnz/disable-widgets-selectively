<?php
/**
 * Plugin Name: Disable Widgets Selectively
 * Plugin URI: https://github.com/ndrwbdnz/disable-widgets-selectively
 * Description: Disable widgets based on selection in plugin settings 
 * Version: 1.0
 * Author: Andrzej Bednorz
 * Author URI: https://github.com/ndrwbdnz/
 * License: GPL2
 */
 
/*  Copyright 2021 Andrzej Bednorz  (email : abednorz@gmail.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


namespace disable_widgets_selectively;


// Settings ---------------------------------------------------------------------------------------------------------------
//https://github.com/WPUserManager/wp-optionskit


//load library to manage settings
require __DIR__ . '/vendor/autoload.php';
$settings_panel  = new \TDP\OptionsKit( 'dws' );
$settings_panel->set_page_title( __( 'Disable Widgets Selectively' ) );

add_filter( 'dws_menu', __NAMESPACE__ . '\setup_menu' );
add_filter( 'dws_settings_tabs', __NAMESPACE__ . '\register_settings_tabs' );
add_filter( 'dws_registered_settings', __NAMESPACE__ . '\register_settings' );


function setup_menu( $menu ) {
    // These defaults can be customized
    // $menu['menu_title'] = 'Settings Panel';
    // $menu['capability'] = 'manage_options';
    
    $menu['parent'] = 'themes.php';
    $menu['page_title'] = __( 'Disable Widgets Selectively' );
    $menu['menu_title'] = $menu['page_title'];

    return $menu;
}

function register_settings_tabs( $tabs ) {
    return array(
        'general' => __( 'General' )
    );
}

function register_settings( $settings ) {
    $settings = array(
        'general' => array(
            array(
                'id'   => 'enable_mode',
                'name' => __( 'Enable (check) or disable (uncheck) widgets selected below' ),
                'type' => 'checkbox',
            ),
            array(
                'id'   => 'widgets_selected',
                'name' => __( 'Choose widgets to be disabled (or enabled, depending on the setting above)' ),
                'type' => 'multiselect',
                'multiple' => true,
                'options' => namespace\get_widget_names(),
            ),
        ),
    );

    return $settings;
}

function get_widget_names(){

    global $wp_widget_factory;

    $widgets = $wp_widget_factory->widgets;
    $widget_names_array = array();

    foreach($widgets as $widget){
        $wclass = get_class($widget);
        $wname = $widget->name;
        array_push($widget_names_array, array('value' => $wclass,'label' => $wname));
    }

    return $widget_names_array;
    
    // array(array(
    //     'value' => '1',
    //     'label' => 'test 1',
    // ), array(
    //     'value' => '2',
    //     'label' => 'test 2',
    // ));
}

//--Main Function -----------------------------------------------------------------------------------------------------------------------

add_action('widgets_init', __NAMESPACE__ . '\disable_selected_widgets', 99);
function disable_selected_widgets() {
    $page = $_SERVER['REQUEST_URI'];
    if ($page != "/wp-admin/themes.php?page=dws-settings"){
        $option_table = get_option('dws_settings');
        $option_table = is_array($option_table)? $option_table : array();
        $widgets_selected = array_key_exists('widgets_selected', $option_table)? $option_table['widgets_selected'] : array();
        $enable_mode = array_key_exists('enable_mode', $option_table)? $option_table['enable_mode'] : 'false';
    
        if ($enable_mode){      //if we are enabling selected widgets, then flip te widgets selected table
            global $wp_widget_factory;
            $widgets = array_keys($wp_widget_factory->widgets);
            $widgets_selected = array_diff($widgets, $widgets_selected);
        }

        foreach($widgets_selected as $widget){
            unregister_widget($widget);    
        }

    }

}
