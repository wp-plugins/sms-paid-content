<?php
/*
Plugin Name: SMSCOIN Key
Plugin URI: http://smscoin.com/
Description: English - This specific plug-in enables your visitors to pay in order to access your services by sending an sms message. In return, user receives a password to access the information he is interested in; you decide for how long given password can be used. Русский - Данный плагин позволяет скрыть часть текста новости, который будет виден пользователю только после ввода пароля из ответного смс. Если пользователь еще не оплатил доступ к закрытому содержимому, ему будут предложены инструкции по оплате через смс.
Version: 1.2.0
Author:  SMSCOIN.COM
Author URI: http://smscoin.com/
*/
/*  Copyright 2008  SMSCOIN  */

$currentLocale = get_locale();
if(!empty($currentLocale)) {
	$moFile = dirname(__FILE__) . "/lang/smscoin_key-" . $currentLocale . ".mo";
	if(file_exists($moFile) && is_readable($moFile)) load_textdomain('smscoin_key', $moFile);
}

if (!class_exists('smscoin_key')) {
	class smscoin_key {
		# Initialaizing sub-tags:
		# Инициализация переменных
		var $prefix = "smscoin_key";
		var $key_id = "";
		var $language = "";
		var $s_enc = "";
		var $tag_name_start = "smscoin_key";
		var $tag_name_end = "smscoin_key";
		var $languages = array("russian", "belarusian", "english", "estonian", "french", "german", "hebrew", "latvian", "lithuanian", "romanian", "spanish", "ukrainian");

		# C-tor
		# Конструктор
		function smscoin_key() {
			# Init the class variabels
			$this->key_id = get_option($this->prefix.'_key_id');
			$this->language = get_option($this->prefix.'_language');
			$this->s_enc = get_option($this->prefix.'_s_enc');

			add_action('admin_menu', array(&$this,'smscoin_key_configuration'));
			add_filter('the_content', array(&$this,'post_filter'));
		}
		###
		#  Check if exists open an close tags of hidden content
		#  Функция фильтрации выходных данных (вывод скрытого контента или реливантной информации)
		#  
		#  $content string
		###
		function post_filter($content) {
			#  Check if exists open an close tags of hidden content
			# Поиск скрытого контента на странице
			if (preg_match('/\\['.$this->tag_name_start.'\\](.*?)\\[\\/'.$this->tag_name_end.'\\]/is', $content, $matches)) {
				################################################################################
				### SMS:Key v1.0.6 ###
				if (intval($this->key_id) > 200000) {
					if($this->language == "") {
						$this->language = "russian";
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
							# Paid form
							# Сгенерировать инструкции по отправке смс
							$rpl_hidd = implode("", $response);
						} else {
							# Open hidden content
							# Открыть контент
							$rpl_hidd= $matches[1];
						}
					} else { die('Не удалось запросить внешний сервер');}
				} else {
					$rpl_hidd = '<div style="text-align: left ;"> Скрытый текст </div>';
				}
				# Replase hidden part of the content with relevant text
				# Вывод скрытого контента или реливантной информации
				$content = preg_replace('/\\['.$this->tag_name_start.'\\].*?\\[\\/'.$this->tag_name_end.'\\]/is', $rpl_hidd, $content);
				### SMS:Key end ###
				################################################################################
			}
			return (($this->language == 'hebrew' || $_GET["s_language"] == 'hebrew' || $this->language == 'arabic' || $_GET["s_language"] == 'arabic')?"<style type='text/css'>#page_footer {direction:RTL; unicode-bidi:embed;} #work_area {direction:RTL; unicode-bidi:embed;} .sms_msg {direction:LTR; unicode-bidi:embed;}</style>":'').$content;
		}

		###
		#  Config function
		#  Функция конфигурации
		###
		function smscoin_key_configuration() {
			global $wpdb;
			if ( function_exists('add_submenu_page') ) {
				if (function_exists('add_options_page')) {
					# Add administrator panel menu for configuration patr of the module
					add_options_page(__('SmsCoin - sms:key','smscoin_key'), __('SmsCoin - sms:key','smscoin_key'), 'administrator', basename(__FILE__), array(&$this,'Main_Configuration_Page'));
				}else{
					add_menu_page(__($this->prefix), __('SmsCoin - sms:key', 'smscoin_key'), 1, __FILE__, array(&$this,'Main_Configuration_Page'));
				}
			}
		}
		###
		#  Config page
		#  Функция для главной страницы конфигурации
		###
		function Main_Configuration_Page() {
			if ( isset($_POST['submit']) ) {
				check_admin_referer();
				update_option($this->prefix.'_key_id', intval(trim($_POST['key_id'])));
				update_option($this->prefix.'_language', trim($_POST['language']));
				update_option($this->prefix.'_s_enc', trim($_POST['s_enc']));
				if (trim($_POST['key_id']) === "") {
					$mess=__('<h3> sms:key error  </h3>', 'smscoin_key');
				}
				$LastAction = $mess.__(' Settings saved ...', 'smscoin_key');
				# Save a new settings
				$this->key_id = get_option($this->prefix.'_key_id');
				$this->language = get_option($this->prefix.'_language');
				$this->s_enc = get_option($this->prefix.'_s_enc');
			}

			if(!empty($LastAction)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$LastAction.'</p></div>'; } ?>

			<div class="wrap">
				<fieldset class="options">
					<?php echo __('<legend><h2>SmsCoin - sms:key, configuration</h2></legend><p>To be able to use this module you need to <a href="http://smscoin.net/account/register/" onclick="this.target = \'_blank\';"><b>register</b></a> on SmsCoin.net.</p><p>Additional information about this service: <a href="http://smscoin.net/info/smskey-tech/" onclick="this.target = \'_blank\';" >SmsCoin - sms:key.</a></p><p><b>How does it work?</b><br /> Insert two tags on your web page or post: [smscoin_key] hidden text [/smscoin_key].</p><p> In order to see the hidden content user must send an sms to short number according to selected country.</p><p> User will receive an sms message containing access code in order to view the hidden content.</p><p><hr /></p>', 'smscoin_key');?>
					<form action="" method="post" id="<?php echo $this->prefix; ?>-conf" style="text-align: left ; margin: left; width: 50em; ">
						<p><?php echo __('Enter your sms:key number:', 'smscoin_key');?> <a href="http://smscoin.com/keys/add/" onclick="this.target = '_blank';"><?php echo __('accept sms:key', 'smscoin_key');?></a></p>
						<p><input id="key_id" name="key_id" type="text" size="12" maxlength="6" style="font-family: 'Courier New', monospace; font-size: 1.5em;" value="<?php echo stripslashes($this->key_id); ?>" />
						<?php echo $this->SelectLng(); ?>
						<p><?php echo __('Enter symbol encoding (charset) for your site (default encoding UTF-8):', 'smscoin_key');?></p>
						<p><input id="s_enc" name="s_enc" type="text" size="12" style="font-family: 'Courier New', monospace; font-size: 1.5em;" <?php echo ($this->s_enc == "" ? ' value="UTF-8" ' : ' value="'.stripslashes($this->s_enc).'" ')?>  />
						<p class="submit"><input type="submit" name="submit" value="<?php echo __('Save settings&raquo;', 'smscoin_key');?>" /></p>
					</form>
				</fieldset>
			</div>

			<?php
		}

		###
		#  Select language
		#  Функция выбора языка
		###
		function SelectLng() {
			$select_txt = __('<p>Choose default language for sms:key script:</p>', 'smscoin_key');
			$select_txt .= '<select id="language" name="language" type="text"  style="font-family: Courier New, monospace; font-size: 1.5em;">';
			$langs = $this->languages;
			foreach ($langs as $lang) {
				$select_txt .= '<option value="'.$lang.'"'.(($lang === $this->language)?' selected="selected"':'').'>'.$lang.'</option>';
			}
			return $select_txt.'</select>';
		}

	}
}

# Create object
# Инициализация класса
if (class_exists('smscoin_key')) {
	$smscoin_key = new smscoin_key();
}
?>
