<?php
class RationalOptionPages {
	private $pages = array(
		// parent level
		array(
			'page_title'	=> 'Rational Options',
			'menu_title'	=> 'Rational Options',
			'capability'	=> 'manage_options',
			'menu_slug'		=> 'rational_options',
			'icon_url'		=> 'dashicons-editor-code',
			'position'		=> 61,
			'description'	=> '<p>Accepts an HTML description</p>',
			// sections & fields
			'sections'	=> array(
				array(
					'id'			=> 'sample_fields',
					'title'			=> 'Sample Fields',
					'description'	=> 'Some of the fields and settings supported by the class',
					'fields'		=> array(
						array(
							'id'	=> 'sample_text',
							'title'	=> 'Sample Text',
							'type'	=> 'text',
							'description' => 'Things like text, search, url, tel, email and password.',
							'value' => 'Default value',
						),
						array(
							'id'	=> 'sample_textarea',
							'title'	=> 'Sample Textarea',
							'type'	=> 'textarea',
							'value'	=> 'Defaults to a large, code-style block but can easily be changed with class, rows and cols.',
						),
						array(
							'id'	=> 'sample_checkbox',
							'title'	=> 'Sample Checkbox',
							'type'	=> 'checkbox',
							'description' => 'Checkboxes of course.',
						),
						array(
							'id'	=> 'sample_radio',
							'title'	=> 'Sample Radio',
							'type'	=> 'radio',
							'options' => array(
								'Radio options are similar to those for selects',
								'radio-two' => 'Sequential or associative arrays work',
							),
						),
						array(
							'id'	=> 'sample_select',
							'title'	=> 'Sample Select',
							'type'	=> 'select',
							'options' => array(
								'Radio options are similar to those for selects',
								'radio-two' => 'Sequential or associative arrays work',
							),
							'description' => 'See? Same as the radio input above.'
						),
						array(
							'id'	=> 'sample_file',
							'title'	=> 'Sample File',
							'type'	=> 'file',
							'description' => 'Included file uploads for fun.'
						),
					),
				),
			),
		),
/*
	//	A new options page
		array(
			'page_title'	=> 'Page Title',
			'menu_title'	=> 'Menu Title',
			'capability'	=> 'manage_options',
			'menu_slug'		=> 'menu_slug',
			'icon_url'		=> '',
			'position'		=> 99,
			'sections'		=> array(
	//			A new section
				array(
					'id'	=> 'section_id',
					'title'	=> 'Section Title',
					'fields'		=> array(
	//					A new field
						array(
							'id'	=> 'field_id',
							'title'	=> 'Field Title',
							'type'	=> 'text',
							'description' => 'Description.',
							'value' => 'Value',
						),
					),
				),
			),
		),
*/
	);
	private $options;
	private $has_file = false;

	/**
	 * Append the init and page building methods to their appropriate methods
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_pages' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		
		if ( $this->in_array_r( 'file', $this->pages ) ) {
			add_action( 'admin_print_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'admin_print_styles', array( $this, 'admin_styles' ) );
			$this->has_file = true;
		}
	}

	/**
	 * Intercept the class function calls to check for field and page callbacks
	 *
	 * @param string function Name of the function being requested
	 * @param array params Parameters being passed to the requested function
	 */
	public function __call( $function, $params ) {
		foreach ( $this->pages as $page ) {
			// See if any of the parent level items match the request for callback
			if ( $function === $page['menu_slug'] . '_callback' ) {
				$this->build_page( $page );
				
			// See if any of the parent level items match the request for sanitize
			} elseif ( $function === $page['menu_slug'] . '_sanitize' ) {
				return $this->sanitize( $page, $params[0] );

			// See if any of the parent level sections or fields match the request
			} elseif ( isset( $page['sections'] ) && count( $page['sections'] ) > 0 ) {
				foreach ( $page['sections'] as $section ) {
					if ( $function === $section['id'] . '_callback' ) {
						$this->build_section( $section );

					// See if any of the parent level fields match the request
					} elseif ( isset( $section['fields'] ) && count( $section['fields'] ) > 0 ) {
						foreach ( $section['fields'] as $field ) {
							if ( $function === $field['id'] . '_callback' ) {
								$this->build_field( $field, $page['menu_slug'] );

							} elseif ( isset( $page['subpages'] ) && count( $page['subpages'] ) > 0 ) {
								// this line seemed to duplicate subpages' content
								//foreach ( $page['subpages'] as $subpage ) {
									// See if any of the subpage level items match the request for callback
									if ( isset( $subpage ) && $function === $subpage['menu_slug'] . '_callback' ) {
										$this->build_page( $subpage );
									
									// See if any of the subpage level items match the request for sanitize
									} elseif ( isset( $subpage ) && $function === $subpage['menu_slug'] . '_sanitize' ) {
										return $this->sanitize( $subpage, $params[0] );

									// See if any of the subpage level sections or fields match the request
									} elseif ( isset( $subpage['sections'] ) && count( $subpage['sections'] ) > 0 ) {
										foreach ( $subpage['sections'] as $subpage_section ) {
											if ( $function === $subpage_section['id'] . '_callback' ) {
												$this->build_section( $subpage_section );

											// See if any of the subpage level fields match the request
											} elseif ( isset( $subpage_section['fields'] ) && count( $subpage_section['fields'] ) > 0 ) {
												foreach ( $subpage_section['fields'] as $subpage_field ) {
													if ( $function === $subpage_field['id'] . '_callback' ) {
														$this->build_field( $subpage_field, $subpage['menu_slug'] );
													}
												}
											}
										}
									}
								//}								
							}
						}
					}
				}
			}
			// There may not be any sections but we still need to check for subpages
			if ( isset( $page['subpages'] ) && count( $page['subpages'] ) > 0 ) {
				foreach ( $page['subpages'] as $subpage ) {
					// See if any of the subpage level items match the request for callback
					if ( $function === $subpage['menu_slug'] . '_callback' ) {
						$this->build_page( $subpage );
					
					// See if any of the subpage level items match the request for sanitize
					} elseif ( $function === $subpage['menu_slug'] . '_sanitize' ) {
						return $this->sanitize( $subpage, $params[0] );

					// See if any of the subpage level sections or fields match the request
					} elseif ( isset( $subpage['sections'] ) && count( $subpage['sections'] ) > 0 ) {
						foreach ( $subpage['sections'] as $subpage_section ) {
							if ( $function === $subpage_section['id'] . '_callback' ) {
								$this->build_section( $subpage_section );

							// See if any of the subpage level fields match the request
							} elseif ( isset( $subpage_section['fields'] ) && count( $subpage_section['fields'] ) > 0 ) {
								foreach ( $subpage_section['fields'] as $subpage_field ) {
									if ( $function === $subpage_field['id'] . '_callback' ) {
										$this->build_field( $subpage_field, $subpage['menu_slug'] );
									}
								}
							}
						}
					}
				}								
			}
		}
	}
	
	public function pages( $pages_array = false ) {
		if ( isset( $pages_array ) && count( $pages_array ) > 0 ) {
			$this->pages = $pages_array;
		}
	}
	
	/**
	 * Function to build the pages and subpages
	 *
	 * @param array page Page properties
	 */
	private function build_page( $page ) {
		if ( $this->has_file ) {
?>			<script language="JavaScript">
				jQuery( document ).ready( function() {
					var formfield, imgurl;
					
					jQuery( '.file-upload-button ').click( function() {
						formfield = jQuery( this ).prev( '.file-upload-text' );
						tb_show( '', 'media-upload.php?type=file&amp;TB_iframe=true' );
						return false;
					} );
					
					window.send_to_editor = function( html ) {
						imgurl = jQuery( 'img', html ).attr( 'src' );
						formfield.val( imgurl );
						tb_remove();
					};
				} );
			</script>
<?php	}
		
		// Options specific to this page
		$this->options[ $page['menu_slug'] ] = get_option( '_' . $page['menu_slug'] . '_options' ); ?>
		
		<div class="wrap">
			<h2><?php echo $page['page_title']; ?></h2>
<?php		if ( isset( $page['description'] ) && ! empty( $page['description'] ) ) { 
				echo $page['description'];
			}
			settings_errors();
			
			if ( isset( $page['sections'] ) && count( $page['sections'] ) > 0 ) {
?>				<form method="post" action="options.php">
<?php				settings_fields( $page['menu_slug'] . '_group' );	// option_group
					do_settings_sections( $page['menu_slug'] );			// page
					submit_button();
?>				</form>
<?php		}
?>		</div>
	<?php }

	/**
	 * Function to build sections
	 *
	 * @param array section Section properties
	 */
	private function build_section( $section ) {
		if ( isset( $section['description'] ) && !empty( $section['description'] ) ) {
			echo $section['description'];
		}
	}
	
	/**
	 * Function to build fields
	 *
	 * @param array field Field attributes
	 * @param string page Page slug for gathering values from DB
	 */
	private function build_field( $field, $page ) {
		switch ( $field['type'] ) {
			/**
			 * (types)		checkbox
			 * (required)	id, title, type
			 * (optional)	class, description, value, args( label_for, disabled, required )
			 */
			case 'checkbox':
				$field_class = ( isset( $field['class'] ) && !empty( $field['class'] ) ) ? $field['class'] : '';
				$field_value = ( isset( $field['value'] ) && !empty( $field['value'] ) ) ? $field['value'] : '1';
				$field_checked = ( isset( $this->options[ $page ][ $field['id'] ] ) && $this->options[ $page ][ $field['id'] ] === $field_value )  ? 'checked' : '';
				$field_disabled = ( isset( $field['args']['disabled'] ) && boolval( $field['args']['disabled'] ) === true ) ? 'disabled' : '';
				$field_required = ( isset( $field['args']['required'] ) && boolval( $field['args']['required'] ) === true ) ? 'required' : '';
				$field_description = ( isset( $field['description'] ) && !empty( $field['description'] ) ) ? $field['description'] : '';
				printf(
					'<label><input type="checkbox" class="%s" name="%s[%s]" id="%s" value="%s" %s %s %s> %s</label>',
					$field_class,
					'_' . $page . '_options',
					$field['id'],
					$field['id'],
					$field_value,
					$field_checked,
					$field_disabled,
					$field_required,
					$field_description
				);
				break;
			/**
			 * (types)		text, search, url, tel, email, password
			 * (required)	id, title, type
			 * (optional)	class, description, placeholder, pattern, value, size, maxlength,
			 * 				args( label_for, required, autocomplete, readonly, disabled ),
			 *				email( multiple )
			 */
			case 'text':
			case 'search':
			case 'url':
			case 'tel':
			case 'email':
			case 'password':
				$field_class = ( isset( $field['class'] ) && !empty( $field['class'] ) ) ? $field['class'] : 'regular-text';
				$field_value = ( isset( $this->options[ $page ][ $field['id'] ] ) ) ? $this->options[ $page ][ $field['id'] ] : ( ( isset( $field['value'] ) ) ? $field['value'] : '' );
				$field_pattern = ( isset( $field['pattern'] ) && !empty( $field['pattern'] ) ) ? 'pattern="' . $field['pattern'] . '"' : '';
				$field_placeholder = ( isset( $field['placeholder'] ) && !empty( $field['placeholder'] ) ) ? 'placeholder="' . $field['placeholder'] . '"' : '';
				$field_size = ( isset( $field['size'] ) && !empty( $field['size'] ) ) ? 'size="' . $field['size'] . '"' : '';
				$field_maxlength = ( isset( $field['maxlength'] ) && !empty( $field['maxlength'] ) ) ? 'maxlength="' . $field['maxlength'] . '"' : '';
				$field_required = ( isset( $field['args']['required'] ) && boolval( $field['args']['required'] ) === true ) ? 'required' : '';
				$field_autocomplete = ( isset( $field['args']['autocomplete'] ) && boolval( $field['args']['autocomplete'] ) === false ) ? 'autocomplete="off"' : '';
				$field_readonly = ( isset( $field['args']['readonly'] ) && boolval( $field['args']['readonly'] ) === true ) ? 'readonly' : '';
				$field_disabled = ( isset( $field['args']['disabled'] ) && boolval( $field['args']['disabled'] ) === true ) ? 'disabled' : '';
				$field_multiple = ( isset( $field['args']['multiple'] ) && $field['type'] === 'email' && boolval( $field['args']['multiple'] ) === true ) ? 'multiple' : '';
				$field_description = ( isset( $field['description'] ) && !empty( $field['description'] ) ) ? '<p class="description">' . $field['description'] . '</p>' : '';
				printf(
					'<label><input type="%s" class="%s" name="%s[%s]" id="%s" value="%s" %s %s %s %s %s %s %s %s %s>%s</label>',
					$field['type'],
					$field_class,
					'_' . $page . '_options',
					$field['id'],
					$field['id'],
					$field_value,
					$field_pattern,
					$field_placeholder,
					$field_size,
					$field_maxlength,
					$field_required,
					$field_autocomplete,
					$field_readonly,
					$field_disabled,
					$field_multiple,
					$field_description
				);
				break;
			/**
			 * (types)		date, datetime, datetime-local, month, time, week
			 * (required)	id, title, type
			 * (optional)	class, value, min, max, step, description,
			 * 				args( label_for, autocomplete, required, readonly, disabled )
			 */
			case 'date':
			case 'datetime':
			case 'datetime-local':
			case 'month':
			case 'time':
			case 'week':
				$field_class = ( isset( $field['class'] ) && !empty( $field['class'] ) ) ? $field['class'] : 'regular-text';
				$field_value = ( isset( $this->options[ $page ][ $field['id'] ] ) ) ? $this->options[ $page ][ $field['id'] ] : ( ( isset( $field['value'] ) ) ? $field['value'] : '' );
				$field_min = ( isset( $field['min'] ) && !empty( $field['min'] ) ) ? 'min="' . $field['min'] . '"' : '';
				$field_max = ( isset( $field['max'] ) && !empty( $field['max'] ) ) ? 'max="' . $field['max'] . '"' : '';
				$field_step = ( isset( $field['step'] ) && !empty( $field['step'] ) ) ? 'step="' . $field['step'] . '"' : '';
				$field_required = ( isset( $field['args']['required'] ) && boolval( $field['args']['required'] ) === true ) ? 'required' : '';
				$field_autocomplete = ( isset( $field['args']['autocomplete'] ) && boolval( $field['args']['autocomplete'] ) === false ) ? 'autocomplete="off"' : '';
				$field_readonly = ( isset( $field['args']['readonly'] ) && boolval( $field['args']['readonly'] ) === true ) ? 'readonly' : '';
				$field_disabled = ( isset( $field['args']['disabled'] ) && boolval( $field['args']['disabled'] ) === true ) ? 'disabled' : '';
				$field_description = ( isset( $field['description'] ) && !empty( $field['description'] ) ) ? '<p class="description">' . $field['description'] . '</p>' : '';
				printf(
					'<label><input type="%s" class="%s" name="%s[%s]" id="%s" value="%s" %s %s %s %s %s %s %s>%s</label>',
					$field['type'],
					$field_class,
					'_' . $page . '_options',
					$field['id'],
					$field['id'],
					$field_value,
					$field_min,
					$field_max,
					$field_step,
					$field_required,
					$field_autocomplete,
					$field_readonly,
					$field_disabled,
					$field_description
				);
				break;
			/**
			 * (types)		range
			 * (required)	id, title, type
			 * (optional)	description, class, value, min, max, step, args( label_for, disabled )
			 */
			case 'range':
				$field_class = ( isset( $field['class'] ) && !empty( $field['class'] ) ) ? $field['class'] : '';
				$field_value = ( isset( $this->options[ $page ][ $field['id'] ] ) ) ? $this->options[ $page ][ $field['id'] ] : ( ( isset( $field['value'] ) ) ? $field['value'] : '' );
				$field_min = ( isset( $field['min'] ) && !empty( $field['min'] ) ) ? 'min="' . $field['min'] . '"' : '';
				$field_max = ( isset( $field['max'] ) && !empty( $field['max'] ) ) ? 'max="' . $field['max'] . '"' : '';
				$field_step = ( isset( $field['step'] ) && !empty( $field['step'] ) ) ? 'step="' . $field['step'] . '"' : '';
				$field_disabled = ( isset( $field['args']['disabled'] ) && boolval( $field['args']['disabled'] ) === true ) ? 'disabled' : '';
				$field_description = ( isset( $field['description'] ) && !empty( $field['description'] ) ) ? '<p class="description">' . $field['description'] . '</p>' : '';
				printf(
					'<label><input type="range" class="%s" name="%s[%s]" id="%s" value="%s" %s %s %s %s>%s</label>',
					$field_class,
					'_' . $page . '_options',
					$field['id'],
					$field['id'],
					$field_value,
					$field_min,
					$field_max,
					$field_step,
					$field_disabled,
					$field_description
				);
				break;
			/**
			 * (types)		color
			 * (required)	id, title, type
			 * (optional)	description, class, value, args( label_for, disabled, autocomplete )
			 */
			 case 'color':
				$field_class = ( isset( $field['class'] ) && !empty( $field['class'] ) ) ? $field['class'] : '';
				$field_value = ( isset( $this->options[ $page ][ $field['id'] ] ) ) ? $this->options[ $page ][ $field['id'] ] : ( ( isset( $field['value'] ) ) ? $field['value'] : '' );
				$field_disabled = ( isset( $field['args']['disabled'] ) && boolval( $field['args']['disabled'] ) === true ) ? 'disabled' : '';
				$field_description = ( isset( $field['description'] ) && !empty( $field['description'] ) ) ? '<p class="description">' . $field['description'] . '</p>' : '';
				printf(
					'<label><input type="color" class="%s" name="%s[%s]" id="%s" value="%s" %s>%s</label>',
					$field_class,
					'_' . $page . '_options',
					$field['id'],
					$field['id'],
					$field_value,
					$field_disabled,
					$field_description
				);
			 	break;
			/**
			 * (types)		number
			 * (required)	id, title, type
			 * (optional)	value, min, max, step, args( label_for, readonly, disabled, required )
			 */
			case 'number':
				$field_class = ( isset( $field['class'] ) && !empty( $field['class'] ) ) ? $field['class'] : 'regular-text';
				$field_value = ( isset( $this->options[ $page ][ $field['id'] ] ) ) ? $this->options[ $page ][ $field['id'] ] : ( ( isset( $field['value'] ) ) ? $field['value'] : '' );
				$field_min = ( isset( $field['min'] ) && !empty( $field['min'] ) ) ? 'min="' . $field['min'] . '"' : '';
				$field_max = ( isset( $field['max'] ) && !empty( $field['max'] ) ) ? 'max="' . $field['max'] . '"' : '';
				$field_step = ( isset( $field['step'] ) && !empty( $field['step'] ) ) ? 'step="' . $field['step'] . '"' : '';
				$field_disabled = ( isset( $field['args']['disabled'] ) && boolval( $field['args']['disabled'] ) === true ) ? 'disabled' : '';
				$field_description = ( isset( $field['description'] ) && !empty( $field['description'] ) ) ? '<p class="description">' . $field['description'] . '</p>' : '';
				printf(
					'<label><input type="number" class="%s" name="%s[%s]" id="%s" value="%s" %s %s %s %s>%s</label>',
					$field_class,
					'_' . $page . '_options',
					$field['id'],
					$field['id'],
					$field_value,
					$field_min,
					$field_max,
					$field_step,
					$field_disabled,
					$field_description
				);
				break;
			/**
			 * (types)		file
			 * (required)	id, title, type
			 * (optional)	description, class, args( label_for, disabled, multiple, required )
			 */
			case 'file':
				$field_class = ( isset( $field['class'] ) && !empty( $field['class'] ) ) ? $field['class'] : 'text';
				$field_value = ( isset( $this->options[ $page ][ $field['id'] ] ) ) ? $this->options[ $page ][ $field['id'] ] : ( ( isset( $field['value'] ) ) ? $field['value'] : '' );
				$field_disabled = ( isset( $field['args']['disabled'] ) && boolval( $field['args']['disabled'] ) === true ) ? 'disabled' : '';
				$field_required = ( isset( $field['args']['required'] ) && boolval( $field['args']['required'] ) === true ) ? 'required' : '';
				$field_description = ( isset( $field['description'] ) && !empty( $field['description'] ) ) ? '<p class="description">' . $field['description'] . '</p>' : '';
				printf(
					'<label><input type="text" class="%s file-upload-text" name="%s[%s]" id="%s" value="%s" placeholder="None" %s %s> 
					<input class="button file-upload-button" type="button" value="Upload File" %s> %s</label>',
					// text
					$field_class,
					'_' . $page . '_options',
					$field['id'],
					$field['id'],
					$field_value,
					$field_disabled,
					$field_required,
					// button
					$field_disabled,
					$field_description
				);
				break;
			/**
			 * (types)		radio
			 * (required)	id, title, type, options
			 * (optional)	class, args( label_for, disabled )
			 */
			case 'radio':
				$field_class = ( isset( $field['class'] ) && !empty( $field['class'] ) ) ? $field['class'] : '';
				$field_value = ( isset( $this->options[ $page ][ $field['id'] ] ) ) ? $this->options[ $page ][ $field['id'] ] : ( ( isset( $field['value'] ) ) ? $field['value'] : '' );
				$field_disabled = ( isset( $field['args']['disabled'] ) && boolval( $field['args']['disabled'] ) === true ) ? 'disabled' : '';
				if ( isset( $field['options'] ) && count( $field['options'] ) > 0 ) {
					echo '<fieldset>';
					$i = 0;
					foreach ( $field['options'] as $option_key => $option_value ) {
						// if this is a sequential array I want to use the value as the key
						if ( is_int( $option_key ) ) {
							$option_key = $option_value;
						}
						$checked = ( $field_value === $option_key ) ? 'checked' : ( ( $i === 0 ) ? 'checked' : '' );
						printf(
							'<label><input type="radio" class="%s" name="%s[%s]" id="%s" value="%s" %s %s> %s</label>',
							$field_class,
							'_' . $page . '_options',
							$field['id'],
							$field['id'],
							$option_key,
							$checked,
							$field_disabled,
							$option_value
						);
						if ( $i < count( $field['options'] ) - 1 ) {
							echo '<br>';
						}
						$i++;
					}
					echo '</fieldset>';
				} else {
					echo 'Attribute <code>options</code> required for type <code>radio</code>.';
				}
				break;
			/**
			 * (types)		select
			 * (required)	id, title, type, options
			 * (optional)	class, args( label_for, disabled )
			 */
			case 'select':
				if ( isset( $field['options'] ) && count( $field['options'] ) > 0 ) {
					$field_class = ( isset( $field['class'] ) && !empty( $field['class'] ) ) ? $field['class'] : '';
					$field_disabled = ( isset( $field['args']['disabled'] ) && boolval( $field['args']['disabled'] ) === true ) ? 'disabled' : '';
					$field_value = ( isset( $this->options[ $page ][ $field['id'] ] ) ) ? $this->options[ $page ][ $field['id'] ] : ( ( isset( $field['value'] ) ) ? $field['value'] : '' );
					$field_description = ( isset( $field['description'] ) && !empty( $field['description'] ) ) ? '<p class="description">' . $field['description'] . '</p>' : '';
					printf(
						'<select class="%s" name="%s[%s]" id="%s" %s>',
						$field_class,
						'_' . $page . '_options',
						$field['id'],
						$field['id'],
						$field_disabled
					);
					foreach ( $field['options'] as $option_value => $option_text ) {
						$option_value_att = ( is_int( $option_value ) ) ? false : 'value="' . $option_value . '"';
						$selected = ( !empty( $option_value_att ) && $option_value === $field_value ) ? 'selected' : ( ( $option_text === $field_value ) ? 'selected' : '' );
						printf(
							'<option %s %s>%s</option>',
							$option_value_att,
							$selected,
							$option_text
						);
					}
					echo '</select>' . $field_description;
				} else {
					echo 'Attribute <code>options</code> required for type <code>radio</code>.';
				}
				break;
			/**
			 * (types)		textarea
			 * (required)	id, title, type
			 * (optional)	value, description, class, placeholder, rows, columns,
			 * 				args( label_for, readonly, disabled, required, autocomplete, html )
			 */
			case 'textarea':
				$field_class = ( isset( $field['class'] ) && !empty( $field['class'] ) ) ? $field['class'] : 'large-text code';
				$field_placeholder = ( isset( $field['placeholder'] ) && !empty( $field['placeholder'] ) ) ? 'placeholder="' . $field['placeholder'] . '"' : '';
				$field_rows = ( isset( $field['rows'] ) && !empty( $field['rows'] ) ) ? 'rows="' . $field['rows'] . '"' : 'rows="10"';
				$field_cols = ( isset( $field['cols'] ) && !empty( $field['cols'] ) ) ? 'cols="' . $field['cols'] . '"' : '';

				$field_readonly = ( isset( $field['args']['readonly'] ) && boolval( $field['args']['readonly'] ) === true ) ? 'readonly' : '';
				$field_disabled = ( isset( $field['args']['disabled'] ) && boolval( $field['args']['disabled'] ) === true ) ? 'disabled' : '';
				$field_required = ( isset( $field['args']['required'] ) && boolval( $field['args']['required'] ) === true ) ? 'required' : '';
				$field_autocomplete = ( isset( $field['args']['autocomplete'] ) && boolval( $field['args']['autocomplete'] ) === false ) ? 'autocomplete="off"' : '';
				$field_value = ( isset( $this->options[ $page ][ $field['id'] ] ) ) ? $this->options[ $page ][ $field['id'] ] : ( ( isset( $field['value'] ) ) ? $field['value'] : '' );
				printf(
					'<textarea class="%s" name="%s[%s]" id="%s" %s %s %s %s %s %s %s>%s</textarea>',
					$field_class,
					'_' . $page . '_options',
					$field['id'],
					$field['id'],
					$field_placeholder,
					$field_rows,
					$field_cols,
					$field_readonly,
					$field_disabled,
					$field_required,
					$field_autocomplete,
					$field_value
				);
				break;
		}
	}

	/**
	 * Goes through the pages array and creates the top level pages and any
	 * subpages within it creating top level and sub menu items
	 */
	public function add_pages() {
		foreach ( $this->pages as $page ) {
			// parent level page(s)
			add_menu_page(
				$page['page_title'],
				$page['menu_title'],
				$page['capability'],
				$page['menu_slug'],
				array( $this, $page['menu_slug'] . '_callback' ),
				( isset( $page['icon_url'] ) ) ? $page['icon_url'] : null,
				( isset( $page['position'] ) ) ? $page['position'] : null
			);
			if ( isset( $page['subpages'] ) && count( $page['subpages'] ) > 0 ) {
				foreach ( $page['subpages'] as $subpage ) {
					add_submenu_page(
						$page['menu_slug'],
						$subpage['page_title'],
						$subpage['menu_title'],
						$subpage['capability'],
						$subpage['menu_slug'],
						array( $this, $subpage['menu_slug'] . '_callback' )
					);
				}
			}
		}
	}
	
	/**
	 * Register each page and subpage's settings groups and pass off sections
	 * to be created
	 */
	public function page_init() {
		foreach ( $this->pages as $page ) {
			// Go through each of the parent pages, check to see if they have
			// fields and, if they do, register some settings for them.
			if ( isset( $page['sections'] ) && count( $page['sections'] ) > 0 ) {
				register_setting(
					$page['menu_slug'] . '_group',						// option_group
					'_' . $page['menu_slug'] . '_options',				// option_name
					array( $this, $page['menu_slug'] . '_sanitize' )	// sanitize_callback
				);
				$this->create_sections( $page['menu_slug'], $page['sections'] );
			}
			if ( isset( $page['subpages'] ) && count( $page['subpages'] ) > 0 ) {
				// If the page has subpages, loop through them as well registering settings if needed
				foreach ( $page['subpages'] as $subpage ) {
					if ( isset( $subpage['sections'] ) && count( $subpage['sections'] ) > 0 ) {
						register_setting(
							$subpage['menu_slug'] . '_group',					// option_group
							'_' . $subpage['menu_slug'] . '_options',			// option_name
							array( $this, $subpage['menu_slug'] . '_sanitize' )	// sanitize_callback
						);
						$this->create_sections( $subpage['menu_slug'], $subpage['sections'] );
					}
				}
			}
		} 
	}
	
	/**
	 * Generate sections and fields
	 *
	 * @param string slug The option page's slug
	 * @param array sections An array of sections and their fields
	 */
	private function create_sections( $slug, $sections ) {
		foreach ( $sections as $section ) {
			add_settings_section(
				$section['id'],					// id
				$section['title'],				// title
				array( $this, $section['id'] . '_callback' ),	// callback
				$slug							// page
			);
			if ( isset( $section['fields'] ) && count( $section['fields'] ) > 0 ) {
				foreach ( $section['fields'] as $field ) {
					add_settings_field(
						$field['id'],				// id
						$field['title'],			// title
						array( $this, $field['id'] . '_callback' ),	// callback
						$slug,						// page
						$section['id'],				// section
						// args (if provided)
						( isset( $field['args'] ) && !empty( $field['args'] ) ) ? $field['args'] : ''
					);
				}
			}
		}
	}
	
	/**
	 * Sanitization function for the input callbacks
	 *
	 * @param array page Page attributes, sections and fields
	 * @param array input Post data from the submission to be sanitized
	 */
	private function sanitize( $page, $input ) {
		$sanitary_values = array();		
		if ( isset( $page['sections'] ) && count( $page['sections'] ) > 0 ) {
			foreach ( $page['sections'] as $section ) {
				foreach ( $section['fields'] as $field ) {
					if ( isset( $input[ $field['id'] ] ) ) {
						switch ( $field['type'] ) {
							case 'text':
							case 'search':
							case 'tel':
							case 'password':
							case 'number':
							case 'textarea':
								if ( !isset( $field['args']['html'] ) || boolval( $field['args']['html'] !== true ) ) {
									$sanitary_values[ $field['id'] ] = sanitize_text_field( $input[ $field['id'] ] );
								} else {
									$sanitary_values[ $field['id'] ] = $input[ $field['id'] ];
								}
								break;
							case 'url':
								$sanitary_values[ $field['id'] ] = esc_url( $input[ $field['id'] ] );
								break;
							case 'email':
								$sanitary_values[ $field['id'] ] = sanitize_email( $input[ $field['id'] ] );
								break;
							default:
								$sanitary_values[ $field['id'] ] = $input[ $field['id'] ];
								break;
						}
					} else if ( !isset( $input[ $field['id'] ] ) && $field['type'] === 'checkbox' ) {
						$sanitary_values[ $field['id'] ] = false;
					}
				}
			}
		}
		return $sanitary_values;
	}
	
	/**
	 * Recursive in_array()
	 *
	 * @param string needle Text to search for
	 * @param array haystack Array to scan (can be multidimensional)
	 * @param boolean strict Strict mode
	 *
	 * @return boolean True/false on whether or not the needle is found
	 */
	private function in_array_r($needle, $haystack, $strict = false) {
		foreach ($haystack as $item) {
			if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Scripts needed for WordPress' media library
	 */
	public function admin_scripts() {
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_script( 'jquery' );
	}
	
	/**
	 * Style needed for WordPress' media library
	 */
	public function admin_styles() {
		wp_enqueue_style( 'thickbox' );
	}
}
