<?php
/*
Plugin Name: SimplyCaptcha WordPress Plugin
Plugin URI: http://www.simplycaptcha.com
Description: This Plugin uses the SimplyCaptcha system for Bot prevention and Spam Protection.
Version: 2.3
Author: Alan pinnt
Author URI: http://www.simplycaptcha.com/
License: GPL3
    Copyright 2014 Simply Captcha www.simplycaptcha.com
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
define('SIMPLYCAP_MINIMUM_WP_VER', '3.8');
define('SIMPLYCAP_PLUGIN_NAME', 'SimplyCaptcha WordPress Plugin');
define('SIMPLYCAP_PLUGIN_URI', 'http://www.simplycaptcha.com/');
define('SIMPLYCAP_VERSION', '2.3');
define('SIMPLYCAP_AUTHOR', 'Alan Pinnt');
define('SIMPLYCAP_AUTHOR_URI', 'http://www.simplycaptcha.com/');

define( 'SIMPLYCAP_URL', plugin_dir_url(__FILE__) );
define( 'SIMPLYCAP_PATH', plugin_dir_path(__FILE__) );
define( 'SIMPLYCAP_BASENAME', plugin_basename( __FILE__ ) );

global $wp_version;
if (version_compare($wp_version, SIMPLYCAP_MINIMUM_WP_VER, '<=')) {
	add_action('admin_notices', 'simplycaptcha_incompat_notice');
}

function simplycaptcha_incompat_notice() {
	echo '<div class="error"><p>';
	printf(__('SimplyCaptcha requires WordPress %s or above. Please upgrade to the latest version of WordPress to enable SimplyCaptcha on your blog, or deactivate SimplyCaptcha to remove this notice.', 'simplycaptcha'), SIMPLYCAP_MINIMUM_WP_VER);
	echo "</p></div>\n";
}

////settings
function register_simplycaptcha_settings() {
	//register our settings
	register_setting('simplycap-settings-group', 'simplycap_key');
	register_setting('simplycap-settings-group', 'simplycap_website');
	register_setting('simplycap-settings-group', 'simplycap_commentform');
    register_setting('simplycap-settings-group', 'simplycap_hideregister');
}

add_action( 'admin_init', 'register_simplycaptcha_settings' );


if ( ! function_exists ( 'simplycap_header' ) ) {
	function simplycap_header() {
		wp_register_style( 'simplycapcss', plugins_url( 'css/style.css', __FILE__ ) );
		wp_enqueue_style( 'simplycapcss' );
	}
}


add_action( 'admin_enqueue_scripts', 'simplycap_header' );
add_action( 'wp_enqueue_scripts', 'simplycap_header' );


function simplycap_plugin_action_links( $links, $file ) {
    $simplycap_links = array(
    '<a href="options-general.php?page=simplycaptcha.php">' . __( 'Settings', 'SimplyCaptcha' ) . '</a>',
     );
    return array_merge($simplycap_links, $mylinks );
}


function simplycap_register_plugin_links($links, $file) {
	$base = plugin_basename(__FILE__);
	if ($file == $base) {
		$links[] = '<a href="options-general.php?page=simplycaptcha.php">' . __( 'Settings', 'SimplyCaptcha' ) . '</a>';
		$links[] = '<a href="http://simplycaptcha.com/">' . __( 'Support', 'captcha' ) . '</a>';
	}
	return $links;
}


// adds "Settings" link to the plugin action page
add_filter( 'plugin_action_links', 'simplycap_plugin_action_links', 10, 2 );

//Additional links on the plugin page
add_filter( 'plugin_row_meta', 'simplycap_register_plugin_links', 10, 2 );


function simplycaptcha_get() {
    global $simplycaptcha_get_tag, $simplycaptcha_get_question,$simplycaptcha_get_error,$simplycaptcha_get_success,$simplycaptcha_get_request;
        
    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, 'http://www.simplycaptcha.com/captcha/request.php');
    curl_setopt($c, CURLOPT_POST, true);
    $browser = $_SERVER['HTTP_USER_AGENT'];
    $ip=$_SERVER['REMOTE_ADDR'];
    curl_setopt($c, CURLOPT_POSTFIELDS, 'website='.get_option('simplycap_website').'&key='.get_option('simplycap_key').'&user_agent='.$browser.'&ip='.$ip.'');
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($c);
    curl_close ($c);

    $obj = json_decode($response);
    
    $simplycaptcha_get_tag = $obj->{'tag'};
    $simplycaptcha_get_request = $obj->{'request'};
    $simplycaptcha_get_question = $obj->{'question'};
    $simplycaptcha_get_error = $obj->{'error'};
    $simplycaptcha_get_success = $obj->{'success'};

}


function simplycaptcha_validate($requestid,$name='',$url='',$email='',$comment='',$tag,$answer) {
    global $simplycaptcha_validate_error,$simplycaptcha_validate_success;
    
    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, 'http://www.simplycaptcha.com/captcha/validate.php');
    curl_setopt($c, CURLOPT_POST, true);
    $browser = $_SERVER['HTTP_USER_AGENT'];
    $ip=$_SERVER['REMOTE_ADDR'];
    curl_setopt($c, CURLOPT_POSTFIELDS, 'website='.get_option('simplycap_website').'&key='.get_option('simplycap_key').'&requestid='.$requestid.'&user_agent='.$browser.'&ip='.$ip.'&tag='.$tag.'&answer='.$answer.'&name='.$name.'&url='.$url.'&email='.$email.'&comment='.$comment.'');
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($c);
    curl_close ($c);

    $obj = json_decode($response);

    $simplycaptcha_validate_error = $obj->{'error'};
    $simplycaptcha_validate_success = $obj->{'success'};
}

if(get_option('simplycap_commentform') == 1) {
    	global $wp_version;
	if( version_compare($wp_version,'3','>=') ) { // wp 3.0 +
		add_action( 'comment_form_after_fields', 'simplycap_comment_form_wp3', 1 );
		add_action( 'comment_form_logged_in_after', 'simplycap_comment_form_wp3', 1 );
	}
	add_action( 'comment_form', 'simplycap_comment_form' );
	add_filter( 'preprocess_comment', 'simplycap_comment_post' );	
}


function simplycap_comment_form_wp3() {
	// skip captcha if user is logged in and the settings allow
	if ( is_user_logged_in() && get_option('simplycap_hideregister') == 1) {
		return true;
	} else {
    ////Call SimplyCaptcha Function
    global $simplycaptcha_get_question,$simplycaptcha_get_tag,$simplycaptcha_get_error,$simplycaptcha_get_success,$simplycaptcha_get_request;
    simplycaptcha_get();

    if ($simplycaptcha_get_success == 'false') {
        print 'There was an error: '.$simplycaptcha_get_error;
        
        remove_action( 'comment_form', 'simplycap_comment_form' );
    } else {
	   echo '<p class="simplycap_block">';
	       echo '<label><input type="hidden" name="simplycaptcha-tag" value="'.$simplycaptcha_get_tag.'">'.$simplycaptcha_get_question.'?</label>';
           echo ' <input type="text" size="30" name="simplycaptcha-answer">';
           echo '<input type="hidden" value="'.$simplycaptcha_get_request.'" name="simplycaptcha-request">';
           echo '<br />';
	       echo '<a href="http://www.simplycaptcha.com">SimplyCaptcha</a></p>';

        remove_action( 'comment_form', 'simplycap_comment_form' );

    	return true;
        }
    }
}


function simplycap_comment_form() {
	// skip captcha if user is logged in and the settings allow
	if ( is_user_logged_in() && get_option('simplycap_hideregister') == 1) {
		return true;
	} else {
    ////Call SimplyCaptcha Function
    global $simplycaptcha_get_question,$simplycaptcha_get_tag,$simplycaptcha_get_error,$simplycaptcha_get_success,$simplycaptcha_get_request;
    simplycaptcha_get();

    if ($simplycaptcha_get_success == 'false') {
        print 'There was an error: '.$simplycaptcha_get_error;
    } else {
	   echo '<p class="simplycap_block">';
	       echo '<label><input type="hidden" name="simplycaptcha-tag" value="'.$simplycaptcha_get_tag.'">'.$simplycaptcha_get_question.'?</label>';
   	       echo '<br />';
           echo '<input type="text" size="30" name="simplycaptcha-answer">';
           echo '<input type="hidden" value="'.$simplycaptcha_get_request.'" name="simplycaptcha-request">';
           echo '<br />';
	       echo '<a href="http://www.simplycaptcha.com">SimplyCaptcha</a></p>';

    	return true;
        }
    }
}

function simplycap_comment_post($comment) {	

	if ( is_user_logged_in() && get_option('simplycap_hideregister') == 1) {
		return $comment;
    // skip captcha for comment replies from the admin menu
	} elseif ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'replyto-comment' && (check_ajax_referer('replyto-comment', '_ajax_nonce', false) || check_ajax_referer( 'replyto-comment', '_ajax_nonce-replyto-comment', false))) {
				// skip capthca
				return $comment;
	} else {
        $simplycaptcha_tag     = ( isset($_REQUEST['simplycaptcha-tag']) ) ? trim($_REQUEST['simplycaptcha-tag']) : null;
        $simplycaptcha_answer  = ( isset($_REQUEST['simplycaptcha-answer']) ) ? trim($_REQUEST['simplycaptcha-answer']) : null;
        $simplycaptcha_request     = ( isset($_REQUEST['simplycaptcha-request']) ) ? trim($_REQUEST['simplycaptcha-request']) : null;
        
	   if ($simplycaptcha_answer == '') {
            wp_die( __('<strong>ERROR</strong>: Please answer the capcha.') ); 
        } else {
            global $simplycaptcha_validate_error,$simplycaptcha_validate_success;
            $comment['comment_author'];
            simplycaptcha_validate($simplycaptcha_request,$comment['comment_author'],$comment['comment_author_url'],$comment['comment_author_email'],$comment['comment_content'],$simplycaptcha_tag,$simplycaptcha_answer);
                
                if ($simplycaptcha_validate_success =='false') {
                    wp_die( __('<strong>ERROR</strong>: '.$simplycaptcha_validate_error.'') );
                } else {
                    return($comment);
                }
        }
    }
}

add_action('admin_menu', 'simplycap_options_page_link');

function simplycap_options_page_link() {
	add_options_page('My Options', 'SimplyCaptcha', 'manage_options', 'simplycaptcha.php', 'simplycap_options_page');
}

function simplycap_options_page() {
?>
<div class="wrap">
<h1>SimplyCaptcha Settings</h1>
Don't have a key? <a href="http://www.simplycaptcha.com" target="_blank">Go here</a>, register your website and get a key!
<form method="post" action="options.php">
    <?php settings_fields( 'simplycap-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Key</th>
        <td><input type="text" name="simplycap_key" value="<?php echo get_option('simplycap_key'); ?>" /></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Website</th>
        <td><input type="text" name="simplycap_website" value="<?php echo get_option('simplycap_website'); ?>" /></td>
        </tr>
    
        <tr valign="top">
        <th scope="row">Comment Form</th>
        <?php if (get_option('simplycap_commentform') == '1') {$commenttrue = 'selected';} else {$commenttrue='';} if (get_option('simplycap_commentform') == '0') {$commentfalse = 'selected';} else {$commentfalse='';}  ?>
        <td><select name="simplycap_commentform"><option value="1" <?php print $commenttrue;?> >On</option><option value="0" <?php print $commentfalse; ?> >Off</option></select></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Hide if registered</th>
        <?php if (get_option('simplycap_hideregister') == '1') {$hidetrue = 'selected';} else {$hidetrue='';} if (get_option('simplycap_hideregister') == '0') {$hidefalse = 'selected';} else {$hidefalse='';}  ?>
        <td><select name="simplycap_hideregister"><option value="1" <?php print $hidetrue;?> >On</option><option value="0" <?php print $hidefalse; ?> >Off</option></select></td>
        </tr>
        
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php
}
?>