<?php
	
GFForms::include_feed_addon_framework();

class GFTrello extends GFFeedAddOn {
	
	protected $_version = GF_TRELLO_VERSION;
	protected $_min_gravityforms_version = '1.9.14.26';
	protected $_slug = 'gravityformstrello';
	protected $_path = 'gravityformstrello/trello.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms Trello Add-On';
	protected $_short_title = 'Trello';
	protected $_enable_rg_autoupgrade = true;
	protected $api = null;
	protected $trello_app_key = 'dfab0c4a0f18ceda69247a94b8dfa48f';
	protected $trello_app_secret = 'd56e0a36b36f386b73ca23aceab2cb1ab526ef426430565316a4ef9ef47e2743';
	private static $_instance = null;

	/* Permissions */
	protected $_capabilities_settings_page = 'gravityforms_trello';
	protected $_capabilities_form_settings = 'gravityforms_trello';
	protected $_capabilities_uninstall = 'gravityforms_trello_uninstall';

	/* Members plugin integration */
	protected $_capabilities = array( 'gravityforms_trello', 'gravityforms_trello_uninstall' );

	/**
	 * Get instance of this class.
	 * 
	 * @access public
	 * @static
	 * @return $_instance
	 */
	public static function get_instance() {
		
		if ( self::$_instance == null ) {
			self::$_instance = new self;
		}

		return self::$_instance;
		
	}

	/**
	 * Plugin starting point. Adds PayPal delayed payment support.
	 *
	 * @access public
	 * @return void
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Create Trello card only when payment is received.', 'gravityformstrello' )
			)
		);

	}

	/**
	 * Enqueue admin scripts.
	 * 
	 * @access public
	 * @return array $scripts
	 */
	public function scripts() {
		
		$scripts = array(
			array(
				'handle'  => 'trello_client',
				'deps'    => array( 'jquery' ),
				'src'     => '//api.trello.com/1/client.js?key=' . $this->trello_app_key,
			),
			array(
				'handle'  => 'gform_trello_admin',
				'deps'    => array( 'jquery', 'trello_client' ),
				'src'     => $this->get_base_url() . '/js/admin.js',
				'version' => $this->_version,
				'enqueue' => array(
					array(
						'admin_page' => array( 'plugin_settings' )
					),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
		
	}

	/**
	 * Setup plugin settings fields.
	 * 
	 * @access public
	 * @return array
	 */
	public function plugin_settings_fields() {
		
		return array(
			array(
				'title'       => '',
				'description' => '',
				'fields'      => array(
					array(
						'name'  => 'authToken',
						'type'  => 'hidden',
					),
					array(
						'name'  => '',
						'label' => esc_html__( 'Authorize with Trello', 'gravityformstrello' ),
						'type'  => 'auth_token_button',
					),
				)
			),
		);
		
	}

	/**
	 * Create Generate Auth Token settings field.
	 * 
	 * @access public
	 * @param array $field
	 * @param bool $echo (default: true)
	 * @return string $html
	 */
	public function settings_auth_token_button( $field, $echo = true ) {
		
		/* Get auth token. */
		$auth_token = $this->get_plugin_setting( 'authToken' );

		if ( rgblank( $auth_token ) ) {
			
			$html = sprintf(
				'<a href="#" class="button" id="gform_trello_auth_button">%1$s</a>',
				esc_html__( 'Click here to generate an authentication token.', 'gravityformstrello' )
			);
			
		} else {
			
			$html  = esc_html__( 'Trello has been authenticated with your account.', 'gravityformstrello' );
			$html .= "&nbsp;&nbsp;<i class=\"fa icon-check fa-check gf_valid\"></i><br /><br />";
			$html .= sprintf(
				' <a href="#" class="button" id="gform_trello_deauth_button">%1$s</a>',
				esc_html__( 'De-Authorize Trello', 'gravityformstrello' )
			);
			
		}
		
		if ( $echo ) {
			echo $html;
		}

		return $html;
		
	}

	/**
	 * Setup fields for feed settings.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_settings_fields() {
		
		/* Build base fields array. */
		$base_fields = array(
			'title'  => '',
			'fields' => array(
				array(
					'name'           => 'feedName',
					'label'          => esc_html__( 'Feed Name', 'gravityformstrello' ),
					'type'           => 'text',
					'required'       => true,
					'class'          => 'medium',
					'default_value'  => $this->get_default_feed_name(),
					'tooltip'        => '<h6>'. esc_html__( 'Name', 'gravityformstrello' ) .'</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformstrello' )
				),
				array(
					'name'           => 'board',
					'label'          => esc_html__( 'Trello Board', 'gravityformstrello' ),
					'type'           => 'select',
					'required'       => true,
					'choices'        => $this->boards_for_feed_setting(),
					'onchange'       => "jQuery('select[name=\"_gaddon_setting_list\"]').val('');jQuery(this).parents('form').submit();",
				),
				array(
					'name'           => 'list',
					'label'          => esc_html__( 'Trello List', 'gravityformstrello' ),
					'type'           => 'select',
					'required'       => true,
					'choices'        => $this->lists_for_feed_setting(),
					'dependency'     => 'board',
					'onchange'       => "jQuery(this).parents('form').submit();",
				),
			)
		);
		
		/*
		Card Settings:
		Due Date (discuss with Carl how to present)	
		*/
		/* Build card settings fields array. */
		$card_fields = array(
			'title'      => esc_html__( 'Card Settings', 'gravityformstrello' ),
			'dependency' => 'list',
			'fields'     => array(
				array(
					'name'          => 'cardName',
					'type'          => 'text',
					'required'      => true,
					'class'         => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'         => esc_html__( 'Name', 'gravityformstrello' ),
					'default_value' => 'New submission from {form_title}',
				),
				array(
					'name'          => 'cardDescription',
					'type'          => 'textarea',
					'required'      => false,
					'class'         => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'         => esc_html__( 'Description', 'gravityformstrello' ),
				),
				array(
					'name'          => 'cardDueDate',
					'type'          => 'select_custom',
					'label'         => esc_html__( 'Due Date', 'gravityformstrello' ),
					'after_input'   => ' ' . esc_html__( 'days after today', 'gravityformstrello' ),
					'choices'       => $this->date_fields_for_feed_setting()
				),
				array(
					'name'          => 'cardLabels',
					'type'          => 'checkbox',
					'label'         => __( 'Labels', 'gravityformstrello' ),
					'choices'       => $this->labels_for_feed_setting()
				),
				array(
					'name'          => 'cardMembers',
					'type'          => 'checkbox',
					'label'         => esc_html__( 'Members', 'gravityformstrello' ),
					'choices'       => $this->members_for_feed_setting()
				),
			)
		);

		$upload_fields = $this->upload_fields_for_feed_setting();

		if ( ! empty ( $upload_fields ) ) {

			$card_fields['fields'][] = array(
				'name'    => 'cardAttachments',
				'type'    => 'checkbox',
				'label'   => esc_html__( 'Attachments', 'gravityformstrello' ),
				'choices' => $upload_fields
			);

		}

		/* Build conditional logic fields array. */
		$conditional_fields = array(
			'title'      => esc_html__( 'Feed Conditional Logic', 'gravityformstrello' ),
			'dependency' => 'list',
			'fields'     => array(
				array(
					'name'           => 'feedCondition',
					'type'           => 'feed_condition',
					'label'          => esc_html__( 'Conditional Logic', 'gravityformstrello' ),
					'checkbox_label' => esc_html__( 'Enable', 'gravityformstrello' ),
					'instructions'   => esc_html__( 'Export to Trello if', 'gravityformstrello' ),
					'tooltip'        => '<h6>' . esc_html__( 'Conditional Logic', 'gravityformstrello' ) . '</h6>' . esc_html__( 'When conditional logic is enabled, form submissions will only be exported to Trello when the condition is met. When disabled, all form submissions will be posted.', 'gravityformstrello' )
				),
				
			)
		);
		
		return array( $base_fields, $card_fields, $conditional_fields );
		
	}
	
	/**
	 * Prepare Trello boards for feed setting.
	 * 
	 * @access public
	 * @return array $choices
	 */
	public function boards_for_feed_setting() {
		
		/* Build choices array. */
		$choices = array(
			array(
				'label' => esc_html__( 'Select a Trello Board', 'gravityformstrello' ),
				'value' => ''
			)
		);
		
		/* If we're unable to initialize the API, return the choices array. */
		if ( ! $this->initialize_api() ) {
			return $choices;
		}
		
		/* Get the Trello boards. */
		$boards = $this->api->members->get( 'my/boards' );
		$this->log_debug( __METHOD__ . '(): Boards: ' . print_r( $boards, true ) );
		
		/* Add the Trello boards to the choices array. */
		if ( ! empty( $boards ) ) {
			
			foreach ( $boards as $board ) {
				
				$choices[] = array(
					'label' => $board->name,
					'value' => $board->id
				);
				
			}
			
		}
		
		return $choices;
		
	}

	/**
	 * Prepare Trello lists for feed setting.
	 * 
	 * @access public
	 * @return array $choices
	 */
	public function lists_for_feed_setting() {
		
		/* Build choices array. */
		$choices = array(
			array(
				'label' => esc_html__( 'Select a Trello List', 'gravityformstrello' ),
				'value' => ''
			)
		);
		
		/* If we're unable to initialize the API, return the choices array. */
		if ( ! $this->initialize_api() ) {
			return $choices;
		}
		
		/* Get feed settings. */
		$settings = $this->is_postback() ? $this->get_posted_settings() : $this->get_feed( $this->get_current_feed_id() );
		$settings = isset( $settings['meta'] ) ? $settings['meta'] : $settings;

		/* Get the Trello lists. */
		$lists = $this->api->boards->get( $settings['board'] . '/lists' );
		
		$this->log_debug( __METHOD__ . '(): Lists for board #' . $settings['board'] . ': ' . print_r( $lists, true ) );
	
		/* Add the Trello lists to the choices array. */
		if ( ! empty( $lists ) ) {
			
			foreach ( $lists as $list ) {
				
				$choices[] = array(
					'label' => $list->name,
					'value' => $list->id
				);
				
			}
			
		}
		
		return $choices;
		
	}

	/**
	 * Prepare Trello labels for feed setting.
	 * 
	 * @access public
	 * @return array $choices
	 */
	public function labels_for_feed_setting() {
		
		/* Build choices array. */
		$choices = array();
		
		/* If we're unable to initialize the API, return the choices array. */
		if ( ! $this->initialize_api() ) {
			return $choices;
		}
		
		/* Get feed settings. */
		$settings = $this->is_postback() ? $this->get_posted_settings() : $this->get_feed( $this->get_current_feed_id() );
		$settings = isset( $settings['meta'] ) ? $settings['meta'] : $settings;

		/* Get the Trello labels. */
		$labels = $this->api->boards->get( $settings['board'] . '/labels' );
		
		$this->log_debug( __METHOD__ . '(): Labels for board #' . $settings['board'] . ': ' . print_r( $labels, true ) );
		
		/* Add the Trello labels to the choices array. */
		if ( ! empty( $labels ) ) {
			
			foreach ( $labels as $label ) {

				$choices[] = array(
					'label' => rgblank( $label->name ) ? ucwords( $label->color ) : $label->name,
					'name'  => 'cardLabels[' . $label->color . ']'
				);
				
			}
			
		}
		
		return $choices;
		
	}

	/**
	 * Prepare Trello members for feed setting.
	 * 
	 * @access public
	 * @return array $choices
	 */
	public function members_for_feed_setting() {
		
		/* Build choices array. */
		$choices = array();
		
		/* If we're unable to initialize the API, return the choices array. */
		if ( ! $this->initialize_api() ) {
			return $choices;
		}
		
		/* Get feed settings. */
		$settings = $this->is_postback() ? $this->get_posted_settings() : $this->get_feed( $this->get_current_feed_id() );
		$settings = isset( $settings['meta'] ) ? $settings['meta'] : $settings;

		/* Get the Trello members. */
		$members = $this->api->boards->get( $settings['board'] . '/members' );
		
		$this->log_debug( __METHOD__ . '(): Members for board #' . $settings['board'] . ': ' . print_r( $members, true ) );

		/* Add the Trello members to the choices array. */
		if ( ! empty( $members ) ) {
			
			foreach ( $members as $member ) {

				$choices[] = array(
					'label' => $member->fullName,
					'name'  => 'cardMembers[' . $member->id . ']'
				);
				
			}
			
		}
		
		return $choices;
		
	}

	/**
	 * Prepare date fields for feed setting.
	 * 
	 * @access public
	 * @return array $choices
	 */
	public function date_fields_for_feed_setting() {

		/* Setup choices array. */
		$choices = array();
		
		/* Get the form. */
		$form = GFAPI::get_form( rgget( 'id' ) );

		/* Get date fields for the form. */
		$date_fields = GFCommon::get_fields_by_type( $form, array( 'date' ) );

		if ( ! empty ( $date_fields ) ) {

			foreach ( $date_fields as $field ) {

				$choices[] = array(
					'label' => $field->label,
					'value' => $field->id,
				);

			}

		}

		return $choices;

	}

	/**
	 * Prepare file upload fields for feed setting.
	 * 
	 * @access public
	 * @return array $choices
	 */
	public function upload_fields_for_feed_setting() {

		/* Setup choices array. */
		$choices = array();

		/* Get the form. */
		$form = GFAPI::get_form( rgget( 'id' ) );

		/* Get file fields for the form. */
		$file_fields = GFCommon::get_fields_by_type( $form, array( 'fileupload', 'dropbox' ) );

		if ( ! empty ( $file_fields ) ) {

			foreach ( $file_fields as $field ) {

				$choices[] = array(
					'name'          => 'cardAttachments[' . $field->id . ']',
					'label'         => $field->label,
					'default_value' => 0,
				);

			}

		}

		return $choices;

	}

	/**
	 * Set feed creation control.
	 * 
	 * @access public
	 * @return bool
	 */
	public function can_create_feed() {
		
		return $this->initialize_api();
		
	}

	/**
	 * Enable feed duplication.
	 * 
	 * @access public
	 * @param $feed_id
	 * @return bool
	 */
	public function can_duplicate_feed( $feed_id ) {
		
		return true;
		
	}

	/**
	 * Setup columns for feed list table.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_list_columns() {
		
		return array(
			'feedName' => esc_html__( 'Name', 'gravityformstrello' ),
			'board'    => esc_html__( 'Trello Board', 'gravityformstrello' ),
			'list'     => esc_html__( 'Trello List', 'gravityformstrello' )
		);
		
	}
	
	/**
	 * Get Trello board name for feed list table.
	 * 
	 * @access public
	 * @param array $feed
	 * @return string
	 */
	public function get_column_value_board( $feed ) {
		
		/* If we're unable to initialize the API, return the board ID. */
		if ( ! $this->initialize_api() ) {
			return $feed['meta']['board'];
		}
		
		try {
			
			/* Get the Trello board. */
			$board = $this->api->boards->get( $feed['meta']['board'] );
			
			return isset( $board->name ) ? $board->name : $feed['meta']['board'];
			
		} catch ( Exception $e ) {
			
			$this->log_error( __METHOD__ . '(): Unable to get Trello board; ' . $e->getMessage() );
			
			return $feed['meta']['board'];
			
		}
		
	}
	
	/**
	 * Get Trello list name for feed list table.
	 * 
	 * @access public
	 * @param array $feed
	 * @return string
	 */
	public function get_column_value_list( $feed ) {
		
		/* If we're unable to initialize the API, return the list ID. */
		if ( ! $this->initialize_api() ) {
			return $feed['meta']['list'];
		}
		
		try {
			
			/* Get the Trello list. */
			$list = $this->api->lists->get( $feed['meta']['list'] );
			
			return isset( $list->name ) ? $list->name : $feed['meta']['list'];
			
		} catch ( Exception $e ) {
			
			$this->log_error( __METHOD__ . '(): Unable to get Trello list; ' . $e->getMessage() );
			
			return $feed['meta']['list'];
			
		}
		
	}

	/**
	 * Process feed.
	 * 
	 * @access public
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return void
	 */
	public function process_feed( $feed, $entry, $form ) {

		/* If API instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
			$this->add_feed_error( esc_html__( 'Card was not created because API could not be initialized.', 'gravityformstrello' ), $feed, $entry, $form );
			return;
		}
		
		/* Prepare card object. */
		$card = array(
			'name'  => GFCommon::replace_variables( $feed['meta']['cardName'], $form, $entry, false, true, false, 'text' ),
			'desc'  => GFCommon::replace_variables( $feed['meta']['cardDescription'], $form, $entry, false, true, false, 'text' ),
		);
		
		/* Add labels to card. */
		if ( rgars( $feed, 'meta/cardLabels' ) ) {
			
			foreach ( rgars( $feed, 'meta/cardLabels' ) as $label_id => $enabled ) {
				if ( $enabled == 1 ) {
					$card['labels'][] = $label_id;
				}
			}
			
			if ( rgar( $card, 'labels' ) ) {
				$card['labels'] = implode( ',', $card['labels'] );
			}
			
		}

		/* Add members to card. */
		if ( rgars( $feed, 'meta/cardMembers' ) ) {
			
			foreach ( rgars( $feed, 'meta/cardMembers' ) as $member_id => $enabled ) {
				if ( $enabled == 1 ) {
					$card['idMembers'][] = $member_id;
				}
			}
			
			if ( rgar( $card, 'idMembers' ) ) {
				$card['idMembers'] = implode( ',', $card['idMembers'] );
			}
			
		}

		/* Add date to card. */
		if ( rgars( $feed, 'meta/cardDueDate' ) == 'gf_custom' && rgars( $feed, 'meta/cardDueDate_custom' ) ) {
			$card['due'] = date( 'Y-m-d\TH:i:s', strtotime( 'midnight +' . $feed['meta']['cardDueDate_custom'] . ' days' ) ) . $this->get_timezone_for_due_date();
		} else if ( rgars( $feed, 'meta/cardDueDate' ) && rgars( $feed, 'meta/cardDueDate' ) != 'gf_custom' ) {
			$date = $this->get_field_value( $form, $entry, $feed['meta']['cardDueDate'] );
			if ( $date ) {
				$card['due'] = date( 'Y-m-d\TH:i:s', strtotime( $date ) ) . $this->get_timezone_for_due_date();
			}
		}

		/* Filter card. */
		$card = gf_apply_filters( 'gform_trello_card', array( $form['id'] ), $card, $feed, $entry, $form );
		$this->log_debug( __METHOD__ . '(): Card to be created => ' . print_r( $card, 1 ) );

		/* Check for card name. */
		if ( rgblank( $card['name'] ) ) {
			$this->add_feed_error( esc_html__( 'Card could not be created because no name was provided.', 'gravityformstrello' ), $feed, $entry, $form );
			return;			
		}
		
		/* Create card. */
		$card = $this->api->lists->post( $feed['meta']['list'] . '/cards', $card );
		
		if ( is_object( $card ) ) {		
			$this->log_debug( __METHOD__ . '(): Card #' . $card->id . ' created.' );
		} else {
			$this->add_feed_error( esc_html__( 'Card could not be created.', 'gravityformstrello' ), $feed, $entry, $form );
			return;
		}
		
		/* Add attachments. */
		if ( rgars( $feed, 'meta/cardAttachments' ) ) {
			
			$files = array();
			
			foreach ( rgars( $feed, 'meta/cardAttachments' ) as $field_id => $enabled ) {
				
				if ( $enabled != 1 ) {
					continue;
				}
				
				$field_value = $this->get_field_value( $form, $entry, $field_id );
				$field_value = array_map( 'trim', explode( ',', $field_value ) );
				
				foreach ( $field_value as $file ) {
					
					if ( rgblank( $file ) ) {
						continue;
					}
					
					try {
						
						$add_attachment = $this->api->cards->post( $card->id . '/attachments', array( 'url' => $file ) );
						
					} catch ( Exception $e ) {
						
						$this->add_feed_error( sprintf( esc_html__( 'Card could not add attachment to card. %s', 'gravityformstrello' ), $e->getMessage() ), $feed, $entry, $form );
						
					}
					
				}
				
			}
			
		}
		
	}

	/**
	 * Initializes Trello API if credentials are valid.
	 * 
	 * @access public
	 * @return bool
	 */
	public function initialize_api() {

		if ( ! is_null( $this->api ) ) {
			return true;
		}
		
		/* Load the Trello API library. */
		if ( ! class_exists( '\Trello\Trello' ) ) {
			require_once 'includes/Trello/Trello.php';
		}

		/* Get the plugin settings */
		$settings = $this->get_plugin_settings();
		
		/* If the authentication token empty, return null. */
		if ( rgblank( $settings['authToken'] ) ) {
			return null;
		}
			
		$this->log_debug( __METHOD__ . "(): Validating API info." );
		
		$trello = new \Trello\Trello( $this->trello_app_key, null, $settings['authToken'] );
				
		try {
			
			/* Run API test. */
			$boards = $trello->members->get( 'my/boards' );
						
		} catch ( Exception $e ) {
			
			/* Log that test failed. */
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $e->getMessage() );			

			return false;
			
		}
		
		if ( $boards === false ) {
			
			/* Log that test failed. */
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $e->getMessage() );			

			return false;			
			
		}
		
		/* Log that test passed. */
		$this->log_debug( __METHOD__ . '(): API credentials are valid.' );
		
		/* Assign Campfire object to the class. */
		$this->api = $trello;
		
		return true;
		
	}
	
	/**
	 * Get timezone offset for card due date.
	 * 
	 * @access public
	 * @return string Timezone offset in ISO 8601 format  
	 */
	public function get_timezone_for_due_date() {
		
		/* Get GMT offset. */
		$gmt_offset = get_option( 'gmt_offset', 0 );
		
		/* Split offset by half hour. */
		$gmt_offset = explode( '.', $gmt_offset );
		
		/* Modify minute offset. */
		if ( isset( $gmt_offset[1] ) ) {
			
			switch ( $gmt_offset[1] ) {
				case '25':
					$gmt_offset[1] = '15';
					break;
				case '5':
					$gmt_offset[1] = '30';
					break;
				case '75':
					$gmt_offset[1] = '45';
					break;					
			}
			
		} else {
			
			$gmt_offset[1] = '00';
			
		}
		
		/* Get positive/negative offset. */
		if ( ! is_numeric( substr( $gmt_offset[0], 0, 1 ) ) ) {
			
			$offset        = substr( $gmt_offset[0], 0, 1 );
			$gmt_offset[0] = substr( $gmt_offset[0], -1 );
			
		} else {
			
			$offset = '+';
			
		}
		
		/* Add leading zero to hour offset. */
		$gmt_offset[0] = sprintf( '%02d', $gmt_offset[0] );
		
		/* Put it all together. */
		$gmt_offset = $offset . implode( ':', $gmt_offset );
		
		return $gmt_offset;
		
	}

}