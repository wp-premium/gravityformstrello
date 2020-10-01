<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms Trello Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2017, Rocketgenius
 */
class GFTrello extends GFFeedAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Trello Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from trello.php
	 */
	protected $_version = GF_TRELLO_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '2.2.3';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformstrello';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformstrello/trello.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms Trello Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Trello';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_trello';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_trello';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_trello_uninstall';

	/**
	 * Defines the capabilities needed for the Trello Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On.
	 */
	protected $_capabilities = array( 'gravityforms_trello', 'gravityforms_trello_uninstall' );

	/**
	 * Contains an instance of the Trello API library, if available.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    object $api If available, contains an instance of the Trello API library.
	 */
	protected $api = null;

	/**
	 * Trello app key.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    object $trello_app_key Trello app key.
	 */
	protected $trello_app_key = 'dfab0c4a0f18ceda69247a94b8dfa48f';

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 * @static
	 *
	 * @return GFTrello
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
	 * @since  1.0.2
	 * @access public
	 */
	public function init() {

		parent::init();

		add_filter( 'gform_settings_header_buttons', array( $this, 'filter_gform_settings_header_buttons' ), 99 );

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Create Trello card only when payment is received.', 'gravityformstrello' )
			)
		);

	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function scripts() {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$scripts = array(
			array(
				'handle'  => 'trello_client',
				'deps'    => array( 'jquery' ),
				'src'     => '//api.trello.com/1/client.js?key=' . $this->trello_app_key,
			),
			array(
				'handle'  => 'gform_trello_admin',
				'deps'    => array( 'jquery', 'trello_client' ),
				'src'     => $this->get_base_url() . "/js/admin{$min}.js",
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
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 1.3
	 *
	 * @return string
	 */
	public function get_menu_icon() {

		return file_get_contents( $this->get_base_path() . '/images/menu-icon.svg' );

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Setup plugin settings fields.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'fields' => array(
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
	 * Hide submit button on plugin settings page.
	 *
	 * @since 1.3
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function filter_gform_settings_header_buttons( $html = '' ) {

		// If this is not the plugin settings page, return.
		if ( ! $this->is_plugin_settings( $this->get_slug() ) ) {
			return $html;
		}

		// Hide button.
		$html = str_replace( '<button', '<button style="opacity:0;"', $html );

		return $html;

	}

	/**
	 * Create Generate Auth Token settings field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $field Field settings.
	 * @param bool  $echo  Display field. Defaults to true.
	 *
	 * @uses GFAddOn::get_plugin_setting()
	 *
	 * @return string
	 */
	public function settings_auth_token_button( $field, $echo = true ) {

		// Get auth token.
		$auth_token = $this->get_plugin_setting( 'authToken' );

		// If auth token does not exist, add generate button.
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





	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Setup fields for feed settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::add_field_after()
	 * @uses GFFeedAddOn::get_default_feed_name()
	 * @uses GFTrello::get_boards_for_feed_setting()
	 * @uses GFTrello::get_date_fields_for_feed_setting()
	 * @uses GFTrello::get_labels_for_feed_setting()
	 * @uses GFTrello::get_lists_for_feed_setting()
	 * @uses GFTrello::get_members_for_feed_setting()
	 * @uses GFTrello::get_upload_fields_for_feed_setting()
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		// Build feed settings sections.
		$sections = array(
			array(
				'fields' => array(
					array(
						'name'           => 'feedName',
						'label'          => esc_html__( 'Feed Name', 'gravityformstrello' ),
						'type'           => 'text',
						'required'       => true,
						'class'          => 'medium',
						'default_value'  => $this->get_default_feed_name(),
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'gravityformstrello' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformstrello' )
						),
					),
					array(
						'name'           => 'board',
						'label'          => esc_html__( 'Trello Board', 'gravityformstrello' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->get_boards_for_feed_setting(),
						'onchange'       => "jQuery( 'select[name=\"_gaddon_setting_list\"]' ).val( '' ); jQuery( this ).parents( 'form' ).submit();",
						'no_choices'     => esc_html__( 'You must have at least one Trello board in your account.', 'gravityformstrello' ),
					),
					array(
						'name'           => 'list',
						'label'          => esc_html__( 'Trello List', 'gravityformstrello' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->get_lists_for_feed_setting(),
						'dependency'     => 'board',
						'onchange'       => "jQuery( this ).parents( 'form' ).submit();",
						'no_choices'     => esc_html__( 'You must select a Trello board containing lists.', 'gravityformstrello' ),
					),
				),
			),
			array(
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
						'choices'       => $this->get_date_fields_for_feed_setting(),
						'input_type'    => 'number'
					),
					array(
						'name'          => 'cardLabels',
						'type'          => 'checkbox',
						'label'         => __( 'Labels', 'gravityformstrello' ),
						'choices'       => $this->get_labels_for_feed_setting()
					),
					array(
						'name'          => 'cardMembers',
						'type'          => 'checkbox',
						'label'         => esc_html__( 'Members', 'gravityformstrello' ),
						'choices'       => $this->get_members_for_feed_setting()
					),
				),
			),
			array(
				'title'      => esc_html__( 'Feed Conditional Logic', 'gravityformstrello' ),
				'dependency' => 'list',
				'fields'     => array(
					array(
						'name'           => 'feedCondition',
						'type'           => 'feed_condition',
						'label'          => esc_html__( 'Conditional Logic', 'gravityformstrello' ),
						'checkbox_label' => esc_html__( 'Enable', 'gravityformstrello' ),
						'instructions'   => esc_html__( 'Export to Trello if', 'gravityformstrello' ),
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Conditional Logic', 'gravityformstrello' ),
							esc_html__( 'When conditional logic is enabled, form submissions will only be exported to Trello when the condition is met. When disabled, all form submissions will be posted.', 'gravityformstrello' )
						),
					),
				),
			),
		);

		// Get upload fields.
		$upload_fields = $this->get_upload_fields_for_feed_setting();

		// If upload fields were found, add settings field.
		if ( ! empty( $upload_fields ) ) {

			// Prepare settings field.
			$attachment_field = array(
				'name'    => 'cardAttachments',
				'type'    => 'checkbox',
				'label'   => esc_html__( 'Attachments', 'gravityformstrello' ),
				'choices' => $upload_fields
			);

			// Add field.
			$sections = $this->add_field_after( 'cardMembers', $attachment_field, $sections );

		}

		return $sections;

	}

	/**
	 * Prepare Trello boards for feed setting.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFTrello::initialize_error()
	 *
	 * @return array $choices
	 */
	public function get_boards_for_feed_setting() {

		// Initialize choices array.
		$choices = array();

		// If we are unable to initialize the API, return the choices array.
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to get boards because API is not initialized.' );
			return $choices;
		}

		try {

			// Get the Trello boards.
			$boards = $this->api->members->get( 'my/boards' );

			// Log returned boards.
			$this->log_debug( __METHOD__ . '(): Boards: ' . print_r( $boards, true ) );

		} catch ( \Exception $e ) {

			// Log that we could not retreive the boards.
			$this->log_error( __METHOD__ . '(): Unable to retrieve boards; ' . $e->getMessage() );

			return $choices;

		}

		// If no boards were found, return.
		if ( empty( $boards ) ) {
			return $choices;
		}

		// Add initial choice.
		$choices[] = array(
			'label' => esc_html__( 'Select a Board', 'gravityformstrello' ),
			'value' => '',
		);

		// Loop through boards.
		foreach ( $boards as $board ) {

			// Add board as choice.
			$choices[] = array(
				'label' => esc_html( $board->name ),
				'value' => esc_attr( $board->id ),
			);

		}

		return $choices;

	}

	/**
	 * Prepare Trello lists for feed setting.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFTrello::initialize_error()
	 *
	 * @return array $choices
	 */
	public function get_lists_for_feed_setting() {

		// Initialize choices array.
		$choices = array();

		// If we are unable to initialize the API, return the choices array.
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to get lists because API is not initialized.' );
			return $choices;
		}

		// Get current board.
		$board = $this->get_setting( 'board' );

		try {

			// Get the Trello lists.
			$lists = $this->api->boards->get( $board . '/lists' );

			// Log returned lists.
			$this->log_debug( __METHOD__ . '(): Lists for board #' . $board . ': ' . print_r( $lists, true ) );

		} catch ( \Exception $e ) {

			// Log that we could not retreive the lists.
			$this->log_error( __METHOD__ . '(): Unable to retrieve lists; ' . $e->getMessage() );

			return $choices;

		}

		// If no lists were found, return.
		if ( empty( $lists ) ) {
			return $choices;
		}

		// Add initial choice.
		$choices[] = array(
			'label' => esc_html__( 'Select a List', 'gravityformstrello' ),
			'value' => '',
		);

		// Loop through lists.
		foreach ( $lists as $list ) {

			// Add list as choice.
			$choices[] = array(
				'label' => esc_html( $list->name ),
				'value' => esc_attr( $list->id ),
			);

		}

		return $choices;

	}

	/**
	 * Prepare Trello labels for feed setting.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFTrello::initialize_error()
	 *
	 * @return array $choices
	 */
	public function get_labels_for_feed_setting() {

		// Initialize choices array.
		$choices = array();

		// If we are unable to initialize the API, return the choices array.
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to get labels because API is not initialized.' );
			return $choices;
		}

		// Get current board.
		$board = $this->get_setting( 'board' );

		try {

			// Get the Trello labels.
			$labels = $this->api->boards->get( $board . '/labels' );

			// Log returned labels.
			$this->log_debug( __METHOD__ . '(): Labels for board #' . $board . ': ' . print_r( $labels, true ) );

		} catch ( \Exception $e ) {

			// Log that we could not retreive the labels.
			$this->log_error( __METHOD__ . '(): Unable to retrieve labels; ' . $e->getMessage() );

			return $choices;

		}

		// If no labels were found, return.
		if ( empty( $labels ) ) {
			return $choices;
		}

		// Loop through labels.
		foreach ( $labels as $label ) {

			// Add label as choice.
			$choices[] = array(
				'label' => rgblank( $label->name ) ? ucwords( $label->color ) : $label->name,
				'name'  => 'cardLabels[' . $label->id . ']',
			);

		}

		return $choices;

	}

	/**
	 * Prepare Trello members for feed setting.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFTrello::initialize_error()
	 *
	 * @return array $choices
	 */
	public function get_members_for_feed_setting() {

		// Initialize choices array.
		$choices = array();

		// If we are unable to initialize the API, return the choices array.
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to get members because API is not initialized.' );
			return $choices;
		}

		// Get current board.
		$board = $this->get_setting( 'board' );

		try {

			// Get the Trello members.
			$members = $this->api->boards->get( $board . '/members' );

			// Log returned members.
			$this->log_debug( __METHOD__ . '(): Members for board #' . $board . ': ' . print_r( $members, true ) );

		} catch ( \Exception $e ) {

			// Log that we could not retreive the members.
			$this->log_error( __METHOD__ . '(): Unable to retrieve members; ' . $e->getMessage() );

			return $choices;

		}

		// If no members were found, return.
		if ( empty( $members ) ) {
			return $choices;
		}

		// Loop through members.
		foreach ( $members as $member ) {

			// Add member as choice.
			$choices[] = array(
				'label' => esc_html( $member->fullName ),
				'name'  => 'cardMembers[' . $member->id . ']',
			);

		}

		return $choices;

	}

	/**
	 * Prepare date fields for feed setting.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_current_form()
	 * @uses GFCommon::get_fields_by_type()
	 *
	 * @return array $choices
	 */
	public function get_date_fields_for_feed_setting() {

		// Initialize choices array.
		$choices = array();

		// Get form.
		$form = $this->get_current_form();

		// Get date fields for form.
		$date_fields = GFCommon::get_fields_by_type( $form, array( 'date' ) );

		// If no date fields were found, return.
		if ( empty( $date_fields ) ) {
			return $choices;
		}

		// Loop through date fields.
		foreach ( $date_fields as $field ) {

			// Add field as choice.
			$choices[] = array(
				'label' => $field->label,
				'value' => $field->id,
			);

		}

		return $choices;

	}

	/**
	 * Prepare file upload fields for feed setting.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_current_form()
	 * @uses GFCommon::get_fields_by_type()
	 *
	 * @return array $choices
	 */
	public function get_upload_fields_for_feed_setting() {

		// Initialize choices array.
		$choices = array();

		// Get form.
		$form = $this->get_current_form();

		// Get file fields for form.
		$file_fields = GFCommon::get_fields_by_type( $form, array( 'fileupload', 'dropbox' ) );

		// If no file fields were found, return.
		if ( empty( $file_fields ) ) {
			return $choices;
		}

		// Loop through file fields.
		foreach ( $file_fields as $field ) {

			// Add field as choice.
			$choices[] = array(
				'name'          => 'cardAttachments[' . $field->id . ']',
				'label'         => $field->label,
				'default_value' => 0,
			);

		}

		return $choices;

	}

	/**
	 * Set feed creation control.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFTrello::initialize_api()
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->initialize_api();

	}

	/**
	 * Enable feed duplication.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param int $feed_id Feed ID requesting duplication ability.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $feed_id ) {

		return true;

	}





	// # FEED LIST -----------------------------------------------------------------------------------------------------

	/**
	 * Setup columns for feed list table.
	 *
	 * @since  1.0
	 * @access public
	 *
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
	 * @since  1.0
	 * @access public
	 *
	 * @param array $feed Feed object.
	 *
	 * @return string
	 */
	public function get_column_value_board( $feed ) {

		// If API is not initialized, return the board ID.
		if ( ! $this->initialize_api() ) {
			return esc_html( $feed['meta']['board'] );
		}

		try {

			// Get the Trello board.
			$board = $this->api->boards->get( $feed['meta']['board'] );

			return isset( $board->name ) ? esc_html( $board->name ) : esc_html( $feed['meta']['board'] );

		} catch ( Exception $e ) {

			// Log that board could not be retrieved.
			$this->log_error( __METHOD__ . '(): Unable to get Trello board; ' . $e->getMessage() );

			return esc_html( $feed['meta']['board'] );

		}

	}

	/**
	 * Get Trello list name for feed list table.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $feed Feed object.
	 *
	 * @return string
	 */
	public function get_column_value_list( $feed ) {

		// If API is not initialized, return the board ID.
		if ( ! $this->initialize_api() ) {
			return esc_html( $feed['meta']['list'] );
		}

		try {

			// Get the Trello list.
			$list = $this->api->lists->get( $feed['meta']['list'] );

			return isset( $list->name ) ? esc_html( $list->name ) : esc_html( $feed['meta']['list'] );

		} catch ( Exception $e ) {

			$this->log_error( __METHOD__ . '(): Unable to get Trello list; ' . $e->getMessage() );

			// Log that list could not be retrieved.
			return esc_html( $feed['meta']['list'] );

		}

	}





	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Process feed.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $feed  The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form  The form object currently being processed.
	 *
	 * @uses GFAddOn::get_field_value()
	 * @uses GFCommon::replace_variables()
	 * @uses GFFeedAddOn::add_feed_error()
	 * @uses GFTrello::get_timezone_for_due_date()
	 * @uses GFTrello::initialize_api()
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If API instance is not initialized, exit.
		if ( ! $this->initialize_api() ) {
			$this->add_feed_error( esc_html__( 'Card was not created because API could not be initialized.', 'gravityformstrello' ), $feed, $entry, $form );
			return $entry;
		}

		// Prepare card object.
		$card = array(
			'name'  => GFCommon::replace_variables( $feed['meta']['cardName'], $form, $entry, false, true, false, 'text' ),
			'desc'  => GFCommon::replace_variables( $feed['meta']['cardDescription'], $form, $entry, false, true, false, 'text' ),
		);

		// Add members to card.
		if ( rgars( $feed, 'meta/cardMembers' ) ) {

			// Loop through card members.
			foreach ( $feed['meta']['cardMembers'] as $member_id => $enabled ) {

				// If card member is not enabled, skip it.
				if ( '1' !== $enabled ) {
					continue;
				}

				// Add member to card.
				$card['idMembers'][] = $member_id;

			}

			// Convert members to string.
			if ( rgar( $card, 'idMembers' ) ) {
				$card['idMembers'] = implode( ',', $card['idMembers'] );
			}

		}

		// Add date to card.
		if ( rgars( $feed, 'meta/cardDueDate' ) ) {

			// If a custom date string is set, use it.
			if ( 'gf_custom' === $feed['meta']['cardDueDate'] && rgars( $feed, 'meta/cardDueDate_custom' ) ) {

				$card['due'] = date( 'Y-m-d\TH:i:s', strtotime( 'midnight +' . $feed['meta']['cardDueDate_custom'] . ' days' ) ) . $this->get_timezone_for_due_date();

			} else if ( 'gf_custom' !== $feed['meta']['cardDueDate'] ) {

				// Get date field value.
				$date = $this->get_field_value( $form, $entry, $feed['meta']['cardDueDate'] );

				// If date field value was found, add it.
				if ( $date ) {
					$card['due'] = date( 'Y-m-d\TH:i:s', strtotime( $date ) ) . $this->get_timezone_for_due_date();
				}

			}

		}

		/**
		 * Change the card properties before sending the data to Trello.
		 *
		 * @param array $card  The card properties.
		 * @param array $feed  The feed currently being processed.
		 * @param array $entry The entry currently being processed.
		 * @param array $form  The form currently being processed.
		 */
		$card = gf_apply_filters( array( 'gform_trello_card', $form['id'] ), $card, $feed, $entry, $form );

		// Log the card to be created.
		$this->log_debug( __METHOD__ . '(): Card to be created => ' . print_r( $card, 1 ) );

		// If no card name is set, exit.
		if ( rgblank( $card['name'] ) ) {
			$this->add_feed_error( esc_html__( 'Card could not be created because no name was provided.', 'gravityformstrello' ), $feed, $entry, $form );
			return $entry;
		}

		try {

			// Create card.
			$card = $this->api->lists->post( $feed['meta']['list'] . '/cards', $card );

			// If card was successfully created, log card ID.
			if ( is_object( $card ) ) {

				$this->log_debug( __METHOD__ . '(): Card #' . $card->id . ' created.' );

			} else {

				// Log that card could not be created.
				$this->add_feed_error( esc_html__( 'Card could not be created.', 'gravityformstrello' ), $feed, $entry, $form );

				return $entry;

			}

		} catch ( \Exception $e ) {

			// Log that card could not be created.
			$this->add_feed_error( esc_html__( 'Card could not be created.', 'gravityformstrello' ) . ' ' . $e->getMessage() , $feed, $entry, $form );

			return $entry;

		}

		// Add labels to card.
		if ( rgars( $feed, 'meta/cardLabels' ) ) {

			// Loop through card labels.
			foreach ( $feed['meta']['cardLabels'] as $label_id => $enabled ) {

				// If card label is not enabled, skip it.
				if ( '1' !== $enabled ) {
					continue;
				}

				try {

					// Add label to card.
					$this->api->cards->post( $card->id . '/idLabels', array( 'value' => $label_id ) );

				} catch ( \Exception $e ) {

					// Log that label could not be added to card.
					$this->add_feed_error( esc_html__( 'Label could not be added to card.', 'gravityformstrello' ) . ' ' . $e->getMessage() , $feed, $entry, $form );

				}

			}

		}

		// If no attachment fields are selected, exit.
		if ( ! rgars( $feed, 'meta/cardAttachments' ) ) {
			return $entry;
		}

		// Loop through attachement fields.
		foreach ( $feed['meta']['cardAttachments'] as $field_id => $enabled ) {

			// If attachement field is not enabled, skip it.
			if ( '1' !== $enabled ) {
				continue;
			}

			// Get uploaded files for field.
			$files = $this->get_field_value( $form, $entry, $field_id );
			$files = array_map( 'trim', explode( ',', $files ) );
			$files = array_filter( $files );

			// If no files were uploaded for this field, skip it.
			if ( empty( $files ) ) {
				continue;
			}

			// Loop through files.
			foreach ( $files as $file ) {

				try {

					// Add file to card.
					$this->api->cards->post( $card->id . '/attachments', array( 'url' => $file ) );

				} catch ( \Exception $e ) {

					// Log that file could not be attached to card.
					$this->add_feed_error( sprintf( esc_html__( 'File "%s" could not be attached to card. %s', 'gravityformstrello' ), basename( $file ), $e->getMessage() ), $feed, $entry, $form );

				}

			}

		}

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Initializes Trello API if credentials are valid.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return bool|null
	 */
	public function initialize_api() {

		// If API is already initialized, return.
		if ( ! is_null( $this->api ) ) {
			return true;
		}

		// Load the Trello API library.
		if ( ! class_exists( '\Trello\Trello' ) ) {
			require_once 'includes/Trello/Trello.php';
		}

		// Get the plugin settings
		$settings = $this->get_plugin_settings();

		// If the authentication token empty, return null.
		if ( ! rgar( $settings, 'authToken' ) ) {
			return null;
		}

		// Log that we are going to validate API credentials.
		$this->log_debug( __METHOD__ . "(): Validating API info." );

		try {

			// Initialize a new Trello API object.
			$trello = new \Trello\Trello( $this->trello_app_key, null, $settings['authToken'] );

			// Run API test.
			$boards = $trello->members->get( 'my/boards' );

		} catch ( Exception $e ) {

			// Log that test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $e->getMessage() );

			return false;

		}

		// If no boards were returned, log that test failed.
		if ( $boards === false ) {

			// Log that test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid.' );

			return false;

		}

		// Log that test passed.
		$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

		// Assign Trello object to the class.
		$this->api = $trello;

		return true;

	}

	/**
	 * Get timezone offset for card due date.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string Timezone offset in ISO 8601 format.
	 */
	public function get_timezone_for_due_date() {

		// Get GMT offset.
		$gmt_offset = get_option( 'gmt_offset', 0 );

		// Split offset by half hour.
		$gmt_offset = explode( '.', $gmt_offset );

		// Modify minute offset.
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

		// Get positive/negative offset.
		if ( ! is_numeric( substr( $gmt_offset[0], 0, 1 ) ) ) {

			$offset        = substr( $gmt_offset[0], 0, 1 );
			$gmt_offset[0] = substr( $gmt_offset[0], -1 );

		} else {

			$offset = '+';

		}

		// Add leading zero to hour offset.
		$gmt_offset[0] = sprintf( '%02d', $gmt_offset[0] );

		// Put it all together.
		$gmt_offset = $offset . implode( ':', $gmt_offset );

		return $gmt_offset;

	}





	// # UPGRADES ------------------------------------------------------------------------------------------------------

	/**
	 * Run required routines when upgrading from previous versions of Add-On.
	 *
	 * @since  1.2.1
	 * @access public
	 *
	 * @param string $previous_version Previous version number.
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFFeedAddOn::get_feeds()
	 * @uses GFFeedAddOn::update_feed_meta()
	 * @uses GFTrello::initialize_api()
	 */
	public function upgrade( $previous_version ) {

		// Determine if previous version is before label change.
		$previous_is_pre_label = ! empty( $previous_version ) && version_compare( $previous_version, '1.2.1', '<' );

		// If previous version is before label change, update existing feeds.
		if ( $previous_is_pre_label ) {

			// If API is not initialized, return.
			if ( ! $this->initialize_api() ) {
				return;
			}

			// Get feeds.
			$feeds = $this->get_feeds();

			// If no feeds are found, exit.
			if ( empty( $feeds ) ) {
				return;
			}

			// Loop through feeds.
			foreach ( $feeds as $feed ) {

				// Get existing labels.
				$existing_labels = rgars( $feed, 'meta/cardLabels' );

				// If no labels are assigned, skip.
				if ( empty( $existing_labels ) ) {
					continue;
				}

				try {

					// Get the Trello labels.
					$labels = $this->api->boards->get( $feed['meta']['board'] . '/labels' );

					// Log returned labels.
					$this->log_debug( __METHOD__ . '(): Labels for board #' . $board . ': ' . print_r( $labels, true ) );

				} catch ( \Exception $e ) {

					// Log that we could not retreive the labels.
					$this->log_error( __METHOD__ . '(): Unable to retrieve labels; ' . $e->getMessage() );

					continue;

				}

				// Initialize new labels array.
				$new_labels = array();

				// Loop through existing labels array.
				foreach ( $existing_labels as $label_color => $enabled ) {

					// If this label is not enabled, skip it.
					if ( '1' !== $enabled ) {
						continue;
					}

					// Loop through labels.
					foreach ( $labels as $label ) {

						// If the label colors don't match, skip.
						if ( $label->color !== $label_color ) {
							continue;
						}

						// Add to new labels array.
						$new_labels[ $label->id ] = $enabled;

						break;

					}

				}

				// Add new labels to feed meta.
				$feed['meta']['cardLabels'] = $new_labels;

				// Update feed.
				$this->update_feed_meta( $feed['id'], $feed['meta'] );

			}

		}

	}

}
