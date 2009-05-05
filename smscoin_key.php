<?php
/*
Plugin Name: SMSCOIN Key
Plugin URI: http://smscoin.com/software/engine/WordPress/
Description: The sms:key is, from the implementational point of view, just a way of restricting user's ability to visit certain web-resources. In order to allow a user to review the restricted content, individual access passwords are generated; each one of these passwords can have a time and/or visit count limit, up to you. The access for the certain password is denied when the time is up OR when the visit count limit is hit, whichever comes first. Be careful while adjusting the options thought: note that when you change your sms:key options, only those users that signed up after the change are affected.
Version: 0.1
Author: smscoin.com
Author URI: http://smscoin.com/
*/
/*  Copyright 2008  SMSCOIN  */

if (!class_exists('smscoin_key')) {
	class smscoin_key {
		var $prefix = "smscoin_key";
		var $key_id = "";
		var $language = "";
		var $s_enc = "";
		# Initialaizing sub-tags:
		var $tag_name_start = "sms";
		var $tag_name_end = "sms";
		var $languages = array("russian", "belarusian", "english", "estonian", "french", "german", "hebrew", "latvian", "lithuanian", "romanian", "spanish", "ukrainian");

		# C-tor
		function smscoin_key() {
			# Init the class variabels
			$this->key_id = get_option($this->prefix.'_key_id');
			$this->language = get_option($this->prefix.'_language');
			$this->s_enc = get_option($this->prefix.'_s_enc');

			add_action('admin_menu', array(&$this,'smscoin_key_configuration'));
			add_filter('the_content', array(&$this,'post_filter'));
		}

		function post_filter($content) {
			# Check if exists open an close tags of hidden content
			if (preg_match('/\\['.$this->tag_name_start.'\\](.*?)\\[\\/'.$this->tag_name_end.'\\]/is', $content, $matches)) {
				################################################################################
				### SMS:Key v1.0.6 ###
				if (intval($this->key_id) > 200000) {
					if($this->language == "") {
						$this->language = "english";
					}
					if($this->s_enc == "") {
						$this->s_enc="UTF-8";
					}
					$old_ua = @ini_set('user_agent', 'smscoin_key_1.0.6');
					$response = @file("http://service.smscoin.com/language/$this->language/key/?s_pure=1&s_enc=$this->s_enc&s_key=".$this->key_id
					."&s_pair=".urlencode(substr($_GET["s_pair"],0,10))
					."&s_language=".urlencode(substr($_GET["s_language"],0,10))
					."&s_ip=".$_SERVER["REMOTE_ADDR"]
					."&s_url=".$_SERVER["SERVER_NAME"].htmlentities(urlencode($_SERVER["REQUEST_URI"])));
					@ini_set('user_agent', $old_ua);
					if ($response !== false) {
						if (count($response)>1 || $response[0] != 'true') {
							$rpl_hidd = implode("", $response);
						} else {
							$rpl_hidd= $matches[1];
						}
					} else { die('Could not request external server');}
				} else {
					$rpl_hidd = '<div style="text-align: left ;"> Hidden text </div>';
				}
				# Replase hidden part of the content with relevant text
				$content = preg_replace('/\\['.$this->tag_name_start.'\\].*?\\[\\/'.$this->tag_name_end.'\\]/is', $rpl_hidd, $content);
				### SMS:Key end ###
				################################################################################
			}
			return $content;
		}

		function smscoin_key_configuration() {
			global $wpdb;
			if ( function_exists('add_submenu_page') ){
				if (function_exists('add_options_page')) {
					# Add administrator panel menu for configuration patr of the module
					add_options_page(__('SMS paid conten','SMS paid conten'), __('SMS paid conten','SMS paid conten'), 'administrator', basename(__FILE__), array(&$this,'Main_Configuration_Page'));
				}else{
					add_menu_page(__($this->prefix), __('SMS paid conten'), 1, __FILE__, array(&$this,'Main_Configuration_Page'));
				}
			}
		}

		function Main_Configuration_Page() {
			if ( isset($_POST['submit']) ) {
				check_admin_referer();
				update_option($this->prefix.'_key_id', intval(trim($_POST['key_id'])));
				update_option($this->prefix.'_language', trim($_POST['language']));
				update_option($this->prefix.'_s_enc', trim($_POST['s_enc']));
				if (trim($_POST['key_id']) === "") {
					$mess="<h3> Key ID is not correct!</h3>";
				}
				$LastAction = __($mess." Configuration saved ...");
				# Save a new settings
				$this->key_id = get_option($this->prefix.'_key_id');
				$this->language = get_option($this->prefix.'_language');
				$this->s_enc = get_option($this->prefix.'_s_enc');
			}

			if(!empty($LastAction)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$LastAction.'</p></div>'; } ?>
			
			<div class="wrap">
				<fieldset class="options">
					<legend><h2>SMS paid conten</h2></legend>
					<p>For using this module you have to be <a href="http://smscoin.net/account/register/" onclick="this.target = '_blank';"><b>registered</b></a> at smscoin.net .</p>
					<p>The sms:key is, from the implementational point of view, just a way of restricting user's ability to visit certain web-resources. In order to allow a user to review the restricted content, individual access passwords are generated; each one of these passwords can have a time and/or visit count limit, up to you. The access for the certain password is denied when the time is up OR when the visit count limit is hit, whichever comes first. Be careful while adjusting the options thought: note that when you change your sms:key options, only those users that signed up after the change are affected.</p>
					<p>For more information about this service: <a href="http://smscoin.net/info/smskey-tech/" onclick="this.target = '_blank';" >SmsCoin - SMS:Key.</a></p>
					<p><b>How does it work ?</b><br /> Add to content of the page or post, 2 tags and beetween hidden text, exampel: [sms] Hidden text [/sms].</p>
					<p><hr /></p>
					<form action="" method="post" id="<?php echo $this->prefix; ?>-conf" style="text-align: left ; margin: left; width: 50em; ">
						<p>Enter ID of you'r sms:key: <a href="http://smscoin.net/keys/add/" onclick="this.target = '_blank';">get sms:key</a></p>
						<p><input id="key_id" name="key_id" type="text" size="12" maxlength="6" style="font-family: 'Courier New', monospace; font-size: 1.5em;" value="<?php echo stripslashes($this->key_id); ?>" />
						<?php echo $this->SelectLng(); ?>
						<p>Enter the charset of you site (value by default UTF-8):</p>
						<p><input id="s_enc" name="s_enc" type="text" size="12" style="font-family: 'Courier New', monospace; font-size: 1.5em;" <?php echo ($this->s_enc == "" ? ' value="UTF-8" ' : ' value="'.stripslashes($this->s_enc).'" ')?>  />
						<p class="submit"><input type="submit" name="submit" value="Save &raquo;" /></p>
					</form>
				</fieldset>
			</div>
			
			<?php
		}

		function SelectLng() {
			$select_txt = '<p>Select the default language for script interface:</p>
			<select id="language" name="language" type="text"  style="font-family: \'Courier New\', monospace; font-size: 1.5em;">';
			$langs = $this->languages;
			foreach ($langs as $lang) {
				$select_txt .= '<option value="'.$lang.'"'.(($lang === $this->language)?' selected="selected"':'').'>'.$lang.'</option>';
			}
			return $select_txt.'</select>';
		}

	}
}

//Instantiate the class
if (class_exists('smscoin_key')) {
	$smscoin_key = new smscoin_key();
}
?>
