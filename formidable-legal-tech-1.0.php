<?php
/*
Plugin Name: Formidable Forms - Legal Tech Extension
Plugin URI: www.legal-tech.bg
Description: Create Contacts and Matters from your Form
Version: 1.0
Author: Cloud Tech Solutions Ltd.
Author URI: www.legal-tech.bg
*/

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// Make LT to Menu
function lt_menu() {
    add_menu_page(
        'Legal Tech - Formidable Extension', // Page title
        'Legal Tech', // Menu title
        'manage_options', // Capability
        'lt', // Menu slug
        'my_formidable_extension_settings_page', // Function to display the settings page
        'dashicons-admin-generic', // Icon URL
        6 // Position
    );
}
add_action('admin_menu', 'lt_menu');

// LT Admin page
function my_formidable_extension_settings_page() {
	    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        // Add an admin notice for the updated settings
        add_settings_error('my_formidable_extension_messages', 'my_formidable_extension_message', 'Your preferences have been saved!', 'updated');
    }

    // Display any admin notices
    settings_errors('my_formidable_extension_messages');

    // The rest of your settings page code goes here
    // Check if Formidable Forms is active
    if (!class_exists('FrmForm')) {
        echo 'Formidable Forms is not active.';
        return;
    }

    // Check if there's a valid API Key

    $selected_api_key = get_option('my_formidable_api_key');
    $show_fields = false;

    if (empty($selected_api_key)){
        $show_fields = false;
    } else {
        $api_key_request = json_decode( file_get_contents('https://services.legal-tech.bg/wordpress?api_key=' . $selected_api_key),1);
        if (!isset($api_key_request['status'] )) {
            $show_fields = true;
            $forms = FrmForm::get_published_forms();
            $selected_form_id = get_option('my_formidable_selected_form_id');
            $selected_name_field = get_option('my_formidable_selected_name_field');
            $selected_email_field = get_option('my_formidable_selected_email_field');
            $selected_phone_field = get_option('my_formidable_selected_phone_field');
            $selected_owners_field = get_option('my_formidable_selected_owners_field');
            $is_active = get_option('my_formidable_extension_active');
            // Fetch fields for the selected form
            $fields = array();
            if (!empty($selected_form_id)) {
                $fields = FrmField::get_all_for_form($selected_form_id);
            }
        }
    }



    ?>
    <div class="wrap">
        <h2>My Formidable Extension Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('lt-settings-group'); ?>
            <?php do_settings_sections('lt-settings-group'); ?>
            <table class="form-table">
                <!-- Active/Not Active Checkbox -->
                <tr valign="top">
                    <th scope="row">Activate Extension:</th>
                    <td>
                        <input type="checkbox" name="my_formidable_extension_active" value="1" <?php checked(1, $is_active, true); ?>/>
                        <label for="my_formidable_extension_active">Check to activate</label>
                    </td>
                </tr>
                <!-- API Key Field with Test Button -->
                <tr valign="top">
                    <th scope="row">API Key:</th>
                    <td>
                        <input type="text" name="my_formidable_api_key" value="<?php echo esc_attr($selected_api_key); ?>" />
                    </td>
                    <?php
                    if (!$show_fields) { echo '<p style="color: red;">Please provide a valid API Key.</p>';}
                                        ?>
                </tr>
                <?php if ($show_fields): ?>
                <!-- Form Selection -->
                <tr valign="top">
                    <th scope="row">Select Form:</th>
                    <td>
                        <select name="my_formidable_selected_form_id" onchange="this.form.submit.click()">
                            <option value="">Select a Form</option>
                            <?php foreach ($forms as $form) {
                                echo '<option value="' . esc_attr($form->id) . '"' . selected($selected_form_id, $form->id, false) . '>' . esc_html($form->name) . '</option>';
                            } ?>
                        </select>
                    </td>
                </tr>
                <!-- Name Field Selection -->
                <tr valign="top">
                    <th scope="row">Name Field:</th>
                    <td>
                        <select name="my_formidable_selected_name_field">
                            <option value="">Select a Field</option>
                            <?php foreach ($fields as $field) {
                                echo '<option value="' . esc_attr($field->id) . '"' . selected($selected_name_field, $field->id, false) . '>' . esc_html($field->name) . '</option>';
                            } ?>
                        </select>
                    </td>
                </tr>
                <!-- Email Field Selection -->
                <tr valign="top">
                    <th scope="row">Email Field:</th>
                    <td>
                        <select name="my_formidable_selected_email_field">
                            <option value="">Select a Field</option>
                            <?php foreach ($fields as $field) {
                                echo '<option value="' . esc_attr($field->id) . '"' . selected($selected_email_field, $field->id, false) . '>' . esc_html($field->name) . '</option>';
                            } ?>
                        </select>
                    </td>
                </tr>
                <!-- Phone Field Selection -->
                <tr valign="top">
                    <th scope="row">Phone Field:</th>
                    <td>
                        <select name="my_formidable_selected_phone_field">
                            <option value="">Select a Field</option>
                            <?php foreach ($fields as $field) {
                                echo '<option value="' . esc_attr($field->id) . '"' . selected($selected_phone_field, $field->id, false) . '>' . esc_html($field->name) . '</option>';
                            } ?>
                        </select>
                    </td>
                </tr> 
                <!-- Owners Selection -->
                <tr valign="top">
                    <th scope="row">Owners Field:</th>
                    <td>
                        <select name="my_formidable_selected_owners_field">
                            <option value="">Select a Field</option>
                            <?php foreach ($api_key_request as $user) {
                                echo '<option value="' . esc_attr($user[1]) . '"' . selected($selected_owners_field, $user[1], false) . '>' . esc_html($user[0]) . '</option>';
                            } ?>
                        </select>
                    </td>
                      
                <?php endif; ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function my_formidable_extension_admin_init() {
    // Register settings and other initialization tasks
    register_setting('lt-settings-group', 'my_formidable_selected_form_id');
    register_setting('lt-settings-group', 'my_formidable_selected_name_field');
    register_setting('lt-settings-group', 'my_formidable_selected_email_field');
    register_setting('lt-settings-group', 'my_formidable_selected_phone_field');
    register_setting('lt-settings-group', 'my_formidable_selected_owners_field');
    register_setting('lt-settings-group', 'my_formidable_api_key');
    register_setting('lt-settings-group', 'my_formidable_extension_active');
}
add_action('admin_init', 'my_formidable_extension_admin_init');


function send_data_to_lt($entry_id, $form_id){
    if (get_option('my_formidable_extension_active')) {
        if ($form_id == intval(get_option('my_formidable_selected_form_id'))){
			$entry = FrmEntry::getOne($entry_id,1);
			$payload = json_encode(array(
				'name' => $entry->metas[intval(get_option('my_formidable_selected_name_field'))],
				'phone' => $entry->metas[intval(get_option('my_formidable_selected_phone_field'))],
				'email' => $entry->metas[intval(get_option('my_formidable_selected_email_field'))],
			));
			$args = array(
				'headers' => array(
					'Content-Type' => 'application/json' // Specify that we're sending JSON
				),
				'body' => $payload
			);
            wp_remote_post('https://services.legal-tech.bg/wordpress?api_key=' . get_option('my_formidable_api_key'),$args=$args);
        }
    }    
}

// Add the action hook
add_action('frm_after_update_entry', 'send_data_to_lt', 30, 2);


?>
