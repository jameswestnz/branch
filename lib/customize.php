<?php
/**
 * Contains methods for customizing the theme customization screen.
 * 
 * @link http://codex.wordpress.org/Theme_Customization_API
 * @since branch 1.0
 */
class BranchCustomize {
	public function __construct($skin) {
		// make skin available to future calls
		$this->skin = $skin;
		
		// Setup the Theme Customizer settings and controls...
		add_action( 'customize_register' , array( $this , 'register' ) );
		
		// Enqueue live preview javascript in Theme Customizer admin screen
		add_action( 'customize_preview_init' , array( $this , 'live_preview' ) );
	}

	/**
	 * This hooks into 'customize_register' (available as of WP 3.4) and allows
	 * you to add new sections and controls to the Theme Customize screen.
	 * 
	 * Note: To enable instant preview, we have to actually write a bit of custom
	 * javascript. See live_preview() for more.
	 *  
	 * @see add_action('customize_register',$func)
	 * @param \WP_Customize_Manager $wp_customize
	 * @link http://ottopress.com/2012/how-to-leverage-the-theme-customizer-in-your-own-themes/
	 * @since branch 1.0
	 */
	public function register( $wp_customize ) {
		// let's make the blogname auto update - requires JS and relevant classes in the skin
		$wp_customize->get_setting( 'blogname' )->transport = 'postMessage';
		
		// now get skin fields
		$wp_customize->add_panel( 'branch', 
			array(
				'title' => __( 'Skin Options', 'branch' ),
				'priority' => 2,
				'capability' => 'edit_theme_options',
			) 
		);
		
		// skin selector
		$wp_customize->add_section( 'branch_skins', 
			array(
				'title'			=> __( 'Select Skin', 'branch' ),
				'priority'		=> 0,
				'capability'	=> 'edit_theme_options',
				'description' => __('Changing skins may require a "Save & Publish" to enable all features.', 'branch'), //Descriptive tooltip
				'panel' => 'branch'
			) 
		);
		
		$wp_customize->add_setting( 'skin',
			array(
				'default'		=> 'default',
				'type'			=> 'theme_mod',
				'capability'	=> 'edit_theme_options',
				'transport'		=> 'refresh',//postMessage
			) 
		);	  
		
		$options = array();
		foreach($this->skin->skins() as $skin) {
			$options[$skin['name']] = $skin['name'];
		}
		$wp_customize->add_control(
		    'skin',
		    array(
		        'type' => 'select',
		        'section' => 'branch_skins',
		        'choices' => $options,
		    )
		);
		
		// need to load in all skin controls so that all are accessible if the user changes skins
		// would need to prefix all sections with the skin name to allow for showing/hiding on the fly
		
		$skin_customize = $this->skin->config()['customize'];
		
		if(isset($skin_customize['sections']) && !empty($skin_customize['sections'])) {
			foreach($skin_customize['sections'] as $key => $section) {
				$wp_customize->add_section( $section['id'], 
					array(
						'title' => __( $section['title'], 'branch' ),
						'priority' => $key+1,
						'capability' => 'edit_theme_options',
						'description' => __($section['description'], 'branch'), //Descriptive tooltip
						'panel' => 'branch'
					) 
				);
				
				if(isset($section['fields']) && !empty($section['fields'])) {
					foreach($section['fields'] as $field) {
						$wp_customize->add_setting( $field['id'],
							array(
								'default' => @$field['default'],
								'type' => 'theme_mod',
								'capability' => 'edit_theme_options',
								'transport' => 'refresh',
							) 
						);
						
						switch($field['type']) {
							case 'text':
								$wp_customize->add_control(
									$field['id'],
									array(
										'label'      => __( $field['label'], 'branch' ),
										'section'    => $section['id'],
									)
								);
							break;
							
							case 'color':
								$wp_customize->add_control(
									new WP_Customize_Color_Control(
										$wp_customize,
										$field['id'],
										array(
											'label'      => __( $field['label'], 'branch' ),
											'section'    => $section['id']
										)
									)
								);
							break;
							
							case 'image':
								$wp_customize->add_control(
									new WP_Customize_Image_Control(
										$wp_customize,
										$field['id'],
										array(
											'label'      => __( $field['label'], 'branch' ),
											'section'    => $section['id'],
										)
									)
								);
							break;
						}
					}
				}
			}
		}
	}
   
	/**
	 * This outputs the javascript needed to automate the live settings preview.
	 * Also keep in mind that this function isn't necessary unless your settings 
	 * are using 'transport'=>'postMessage' instead of the default 'transport'
	 * => 'refresh'
	 * 
	 * Used by hook: 'customize_preview_init'
	 * 
	 * @see add_action('customize_preview_init',$func)
	 * @since branch 1.0
	 */
	public function live_preview() {
		wp_enqueue_script( 
			'branch-themecustomizer', // Give the script a unique ID
			get_template_directory_uri() . '/assets/js/theme-customizer.js', // Define the path to the JS file
			array(
				'jquery',
				'customize-preview'
			), // Define dependencies
			'', // Define a version (optional) 
			true // Specify whether to put in footer (leave this true)
		);
	}
}