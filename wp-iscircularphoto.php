<?php
/*
Plugin Name: IS Circular Photo Gallery
Plugin URI: http://www.polaroidgallery.hostoi.com
Description: WordPress implementation of the circular picture gallery. 
Version: 1.9
Author: I. Savkovic
Author URI: http://www.polaroidgallery.hostoi.com

Originally based on the plugin by Bev Stofko http://www.stofko.ca/wp-imageflow2-wordpress-plugin/.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
global $wp_version;
define('ISCPGALLERYVERSION', version_compare($wp_version, '2.8.4', '>='));

if(!defined("PHP_EOL")){define("PHP_EOL", strtoupper(substr(PHP_OS,0,3) == "WIN") ? "\r\n" : "\n");}

if (!class_exists("ISCPGallery")) {
Class ISCPGallery
{
	var $adminOptionsName = 'iscpgallery_options';

	/* html div ids */
	var $imageflow2div = 'iscp_imageflow';
	var $loadingdiv   = 'iscp_loading';
	var $imagesdiv    = 'iscp_images';
	var $captionsdiv  = 'iscp_captions';
	var $sliderdiv    = 'iscp_slider';
	var $scrollbardiv = 'iscp_scrollbar';
	var $noscriptdiv  = 'iscp_imageflow_noscript';
	var $largerimagesdiv    = 'iscp_largerimages';
	

	var $iscp_instance = 0;
	var $iscp_id = 0;

	function iscpgallery()
	{
		if (!ISCPGALLERYVERSION)
		{
			add_action ('admin_notices',__('WP-IS Circular Photo Gallery requires at least WordPress 2.8.4','wp-iscircularphoto'));
			return;
		}	
		
		register_activation_hook( __FILE__, array(&$this, 'activate'));
		register_deactivation_hook( __FILE__, array(&$this, 'deactivate'));
		add_action('init', array(&$this, 'addScripts'));	
		add_action('admin_menu', array(&$this, 'add_settings_page'));
		add_shortcode('wp-iscircularphoto', array(&$this, 'flow_func'));	
		add_filter("attachment_fields_to_edit", array(&$this, "image_links"), null, 2);
		add_filter("attachment_fields_to_save", array(&$this, "image_links_save"), null , 2);

	}
	
	function activate()
	{
		/*
		** Nothing needs to be done for now
		*/
	}
	
	function deactivate()
	{
		/*
		** Nothing needs to be done for now
		*/
	}			
	
	function flow_func($attr,$iscp_id) {
		/*
		** WP-IS Circular Photo gallery shortcode handler
		*/

		/* Increment the instance to support multiple galleries on a single page */
		$this->iscp_instance ++;


		/* Load scripts, get options */
		$options = $this->getAdminOptions();

		/* Produce the Javascript for this instance */
		$js  = "\n".'<script type="text/javascript">'."\n";
		$js .= 'jQuery(document).ready(function() { '."\n".'var iscirculargallery_' . $this->iscp_instance . ' = new iscirculargallery('.$this->iscp_instance.','.$this->iscp_id.');'."\n";
		$js .= 'iscirculargallery_' . $this->iscp_instance . '.init( {';

		if ( !isset ($attr['rotate']) ) {
			$js .= 'conf_autorotate: "' . $options['autorotate'] . '", ';
		} else {
			$js .= 'conf_autorotate: "' . $attr['rotate'] . '", ';
		}
		$js .= 'conf_autorotatepause: ' . $options['pause'] . ', ';
		if ( !isset ($attr['startimg']) ) {
			$js .= 'conf_startimg: 1' . ', ';
		} else {
			$js .= 'conf_startimg: ' . $attr['startimg'] . ', ';
		}
		
			if ( !isset ($attr['nocaptions']) ) {
			$js .= 'conf_nocaptions: true' . ', ';
		} else {
			$js .= 'conf_nocaptions: ' . $options['nocaptions'] . ', ';
		}
			
		if ( !isset ($attr['samewindow']) ) {
			$js .= $options['samewindow']? 'conf_samewindow: true' : 'conf_samewindow: false';
		} else {
			$js .= 'conf_samewindow: ' . $attr['samewindow'];
		}

		$js .= '} );'."\n";
		$js .= '});'."\n";
		$js .= "</script>\n\n";

		/* Get the list of images */
		$image_list = apply_filters ('iscp_image_list', array(), $attr);
		if (empty($image_list)) {
		 	if ( !isset ($attr['dir']) ) {
				$image_list = $this->images_from_library($attr, $options);
			} else {
				$image_list = $this->images_from_dir($attr, $options);
	  		}
		}

		/* Prepare options */
		$bgcolor = $options['bgcolor'];
		$txcolor = $options['txcolor'];
		$slcolor = $options['slcolor'];
		$width   = $options['width'];
		$height  = $options['height'];
		$link    = $options['link'];
		$imgbdcolor = $options['imgbdcolor'];
		$imgbdwidth = $options['imgbdwidth'];
		$lgimgbdcolor = $options['lgimgbdcolor'];
		$lgimgbdwidth = $options['lgimgbdwidth'];
		$bgccolor = $options['bgccolor'];
		$bdccolor = $options['bdccolor'];
		$lgimgwidth = $options['lgimgwidth'];
		$lgimgheight = $options['lgimgheight'];
		$imgwidth = $options['imgwidth'];
	

		$plugin_url = plugins_url( '', __FILE__ );

		/**
		* Start output
		*/
		$noscript = '<noscript><div id="' . $this->noscriptdiv . '_' . $this->iscp_instance . '" class="' . $this->noscriptdiv . '">';	
		$output  = '<div id="' . $this->imageflow2div . '_' . $this->iscp_instance . '" class="' . $this->imageflow2div . '" style="background-color: ' . $bgcolor . '; color: ' . $txcolor . '; width: ' . $width . '; height: ' . $height . '">' . PHP_EOL; 
		$output .= '<div id="' . $this->loadingdiv . '_' . $this->iscp_instance . '" class="' . $this->loadingdiv . '" style="color: ' . $txcolor .';">' . PHP_EOL;
		$output .= '<b>';
		$output .= __('Loading Images','wp-iscircularphoto');
		$output .= '</b><br/>' . PHP_EOL;
		$output .= '<img src="' . $plugin_url . '/img/loading.gif" width="208" height="13" alt="' . $this->loadingdiv . '" />' . PHP_EOL;
		$output .= '</div>' . PHP_EOL;
		$output .= '	<div id="' . $this->imagesdiv . '_' . $this->iscp_instance . '" class="' . $this->imagesdiv . '" style="border-width: ' . $imgbdwidth . '; border-color: ' . $imgbdcolor . '; width: ' . $imgwidth . ';">' . PHP_EOL;	
		
	/**	$output .= '<style type="text/css">.iscp_images img { 
           border : 4px solid #C9D0E3;} </style>' . PHP_EOL;
           */
           	
		/**
		* Add images
		*/
		if (!empty ($image_list) ) {
		    $i = 0;
		    foreach ( $image_list as $this_image ) {

			
			/* What does the carousel image link to? */
			$linkurl 		= $this_image['link'];
			$rel 			= '';
			$dsc			= '';
			if ($linkurl === '') {
				/* We are linking to the popup - use the title and description as the alt text */
				$linkurl = $this_image['large'];
				$rel = ' data-style="iscp_lightbox"';
				$alt = ' alt="'.$this_image['title'].'"';
				if ($this_image['desc'] != '') {
					
					$dsc = ' data-description="' . htmlspecialchars(str_replace(array("\r\n", "\r", "\n"), "<br />", $this_image['desc'])) . '"';
				}
			} else {
				/* We are linking to an external url - use the title as the alt text */
				$alt = ' alt="'.$this_image['title'].'"';
			}
			
		
		$output .= '<img src="'.$this_image['small'].'" data-link="'.$linkurl.'"'. $rel . $alt . $dsc . ' />';

		
			/* build separate thumbnail list for users with scripts disabled */
			$noscript .= '<a href="' . $linkurl . '"><img src="' . $this_image['small'] .'" width="100"  alt="'.$this_image['title'].'" /></a>';
			$i++;
			
		    }
		    $this->iscp_id ++;
		}
					
		
		$output .= '</div>' . PHP_EOL;
/* larger image	*/

$output .= '<div id="' . $this->largerimagesdiv . '_' . $this->iscp_instance . '" class="' . $this->largerimagesdiv . '" style="border-width: ' . $lgimgbdwidth . '; border-color: ' . $lgimgbdcolor . '; width: ' . $lgimgwidth . '; height: ' . $lgimgheight . ';">' . PHP_EOL;
/*$output .= '<div id="' . $this->largerimagesdiv . '_' . $this->iscp_instance . '" class="' . $this->largerimagesdiv . '">' . PHP_EOL;*/
/*$output .= '<div id="' . $this->largerimages . '_' . $this->iscp_instance . '" class="' . $this->largerimages . '" style="border-width: ' . $lgimgbdwidth . '; border-color: ' . $lgimgbdcolor . ';">' . PHP_EOL;	*/
		/**
		* Add images
		*/
		if (!empty ($image_list) ) {
		    $i = 0;
		    foreach ( $image_list as $this_image ) {

			
			/* What does the carousel image link to? */
			$linkurl 		= $this_image['link'];
			$rel 			= '';
			$dsc			= '';
			if ($linkurl === '') {
				/* We are linking to the popup - use the title and description as the alt text */
				$linkurl = $this_image['large'];
				$rel = ' data-style="iscp_lightbox"';
				$alt = ' alt="'.$this_image['title'].'"';
				if ($this_image['desc'] != '') {
					
					$dsc = ' data-description="' . htmlspecialchars(str_replace(array("\r\n", "\r", "\n"), "<br />", $this_image['desc'])) . '"';
				}
			} else {
				/* We are linking to an external url - use the title as the alt text */
				$alt = ' alt="'.$this_image['title'].'"';
			}
			
		
		$output .= '<img src="'.$this_image['small'].'" data-link="'.$linkurl.'"'. $rel . $alt . $dsc . ' />';

		
			/* build separate thumbnail list for users with scripts disabled */
			$noscript .= '<a href="' . $linkurl . '"><img src="' . $this_image['small'] .'" width="100"  alt="'.$this_image['title'].'" /></a>';
			$i++;
			
		    }
		    $this->iscp_id ++;
		}
					
		
		$output .= '</div>' . PHP_EOL;
		
			
		
		
		
		
		
		$output .= '<div id="' . $this->captionsdiv . '_' . $this->iscp_instance . '" class="' . $this->captionsdiv . '" style="background-color: ' . $bgccolor . ' ; border-color: ' . $bdccolor . '"';
		if ($options['nocaptions']) $output .= ' style="display:none !important;"';
		$output .= '></div>' . PHP_EOL;
		$output .= '<div id="' . $this->scrollbardiv . '_' . $this->iscp_instance . '" class="' . $this->scrollbardiv;
		if ($slcolor == "black") $output .= ' black';
		$output .= '"';
		if ($options['noslider']) $output .= ' style="display:none !important;"';
		$output .= '><div id="' . $this->sliderdiv . '_' . $this->iscp_instance . '" class="' . $this->sliderdiv . '">' . PHP_EOL;
		$output .= '</div>';
		$output .= '</div>' . PHP_EOL;
		$output .= $noscript . '</div></noscript></div>';	

		return $js . $output;
		
		

	}

	function images_from_library ($attr, $options) {
		/*
		** Generate a list of the images we are using from the Media Library
		*/
		if ( isset( $attr['orderby'] ) ) {
			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}

		/*
		** Standard gallery shortcode defaults that we support here	
		*/
		global $post;
		extract(shortcode_atts(array(
				'order'      => 'ASC',
				'orderby'    => 'menu_order ID',
				'id'         => $post->ID,
				'include'    => '',
				'exclude'    => '',
				'mediatag'	 => '',	// corresponds to Media Tags plugin by Paul Menard
		  ), $attr));
	
		$id = intval($id);
		if ( 'RAND' == $order )
			$orderby = 'none';

		if ( !empty($mediatag) ) {
			$mediaList = get_attachments_by_media_tags("media_tags=$mediatag&orderby=$orderby&order=$order");
			$attachments = array();
			foreach ($mediaList as $key => $val) {
				$attachments[$val->ID] = $mediaList[$key];
			}
		} elseif ( !empty($include) ) {
			$include = preg_replace( '/[^0-9,]+/', '', $include );
			$_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

			$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} elseif ( !empty($exclude) ) {
			$exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
			$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
		} else {
			$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
		}

		$image_list = array();
		foreach ( $attachments as $id => $attachment ) {
			$small_image = wp_get_attachment_image_src($id, "medium");
			$large_image = wp_get_attachment_image_src($id, "large");

			/* If the media description contains an url and the link option is enabled, use the media description as the linkurl */
			$link_url = '';
			if (($options['link'] == 'true') && 
				((substr($attachment->post_content,0,7) == 'http://') || (substr($attachment->post_content,0,8) == 'https://'))) {
				$link_url = $attachment->post_content;
			}

			$image_link = get_post_meta($id, '_iscp-image-link', true);
			if (isset($image_link) && ($image_link != '')) $link_url = $image_link;

			$image_list[] = array (
				'small' => $small_image[0],
				'large' => $large_image[0],
				'link'  => $link_url,
				'title' => $attachment->post_title,
				'desc'  => $attachment->post_content,
			);

		}
		return $image_list;
		
	}

	function images_from_dir ($attr, $options) {
		/*
		** Generate the image list by reading a folder
		*/
		$image_list = array();

		$galleries_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $this->get_path($options['gallery_url']);
		if (!file_exists($galleries_path))
			return '';

		/*
		** Gallery directory is ok - replace the shortcode with the image gallery
		*/
		$plugin_url = get_option('siteurl') . "/" . PLUGINDIR . "/" . plugin_basename(dirname(__FILE__)); 			
			
		$gallerypath = $galleries_path . $attr['dir'];
		if (file_exists($gallerypath))
		{	
			$handle = opendir($gallerypath);
			while ($image=readdir($handle)) {
				if (filetype($gallerypath."/".$image) != "dir" && !preg_match('/refl_/',$image)) {
					$pageURL = 'http';
					if (isset($_SERVER['HTTPS']) && ($_SERVER["HTTPS"] == "on")) {$pageURL .= "s";}
					$pageURL .= "://";
					if ($_SERVER["SERVER_PORT"] != "80") {
				   	$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
				} else {
				   	$pageURL .= $_SERVER["SERVER_NAME"];
				}
				$imagepath = $pageURL . '/' . $this->get_path($options['gallery_url']) . $attr['dir'] . '/' . $image;
				$image_list[] = array (
					'small' => $imagepath,
					'large' => $imagepath,
					'link'  => '',
					'title' => $image,
					'desc'  => '',
			);
			    }
		//	    $this->iscp_id ++;
			}
			closedir($handle);
		}

		return $image_list;
	}


	function getAdminOptions() {
		/*
		** Merge default options with the saved values
		*/
		$use_options = array(	'gallery_url' => '0', 	// Path to gallery folders when not using built in gallery shortcode
						'bgcolor' => '#000000', // Background color defaults to black
						'txcolor' => '#FFFFFF', // Text color defaults to white
						'slcolor' => 'white',	// Slider color defaults to white
						'link'    => 'false',	// Don't link to image description
						'width'   => '640px',	// Width of containing div
						'height'  => '480px',	// Height of containing div
						'autorotate' => 'off',	// True to enable auto rotation
						'pause' =>	'3000',	// Time to pause between auto rotations
						'samewindow' => false,	// True to open links in same window rather than new window
						'nocaptions' => false,	// True to hide captions in the carousel
						'noslider' => true,	// True to hide the scrollbar
						'defheight' => false,	// True to use default value
						'bgccolor' => '#6ED2CF', // Background color defaults 
						'bdccolor' => '#5882FA', // Background color defaults 
						'imgbdcolor' => '#DDDBDB', // Border color defaults 
						'lgimgbdcolor' => '#5882FA', // Border color of central image defaults 
						'imgbdwidth'=> '5px',	// Width of image border
						'lgimgbdwidth'=> '5px',	// Width of large image border
						'lgimgwidth'=> '160px',	// Width of large image
						'lgimgheight'=> '120px',	// Height of large image
						'imgwidth'=> '80px'	// Width of image
					);
		$saved_options = get_option($this->adminOptionsName);
		if (!empty($saved_options)) {
			foreach ($saved_options as $key => $option)
				$use_options[$key] = $option;
		}
		
		if ($use_options['defheight'] == 'true')
		{
			$use_options['height'] = '480px';
			}
			

		
		return $use_options;
	}

	function get_path($gallery_url) {
		/*
		** Determine the path to prepend with DOCUMENT_ROOT
		*/
		if (substr($gallery_url, 0, 7) != "http://") return $gallery_url;

		$dir_array = parse_url($gallery_url);
		return $dir_array['path'];
	}

	function addScripts()
	{
		if (!is_admin()) {
			wp_enqueue_style( 'iscpgallerycss',  plugins_url('css/screen.css', __FILE__));
			wp_enqueue_script('iscp_gallery', plugins_url('js/iscirculargallery.js', __FILE__), array('jquery'), '1.9');
		} else {
			wp_enqueue_script('iscp_utility_js', plugins_url('js/iscp_utility.js', __FILE__));
		}
	}	

	function image_links($form_fields, $post) {
		$form_fields["iscp-image-link"] = array(
			"label" => __("WP-IS Circular Photo Gallery Link"),
			"input" => "", // this is default if "input" is omitted
			"value" => get_post_meta($post->ID, "_iscp-image-link", true),
      	 	"helps" => __("To be used with carousel added via [wp-iscircularphoto] shortcode."),
		);
	   return $form_fields;
	}

	function image_links_save($post, $attachment) {
		// $attachment part of the form $_POST ($_POST[attachments][postID])
      	// $post['post_type'] == 'attachment'
		if( isset($attachment['iscp-image-link']) ){
			// update_post_meta(postID, meta_key, meta_value);
			update_post_meta($post['ID'], '_iscp-image-link', $attachment['iscp-image-link']);
		}
		return $post;
	}

	function add_settings_page() {
		add_options_page('WP-IS Circular Photo Gallery Options', 'WP-IS Circular Photo Gallery', 'manage_options', 'wpISCircularPhoto', array(&$this, 'settings_page'));
	}

	function settings_page() {
		global $options_page;

		if (!current_user_can('manage_options'))
			wp_die(__('Sorry, but you have no permission to change settings.','wp-iscircularphoto'));	
			
		$options = $this->getAdminOptions();
		if (isset($_POST['save_iscpgallery']) && ($_POST['save_iscpgallery'] == 'true') && check_admin_referer('iscpgallery_options'))
		{
			echo "<div id='message' class='updated fade'>";	

			/*
			** Validate the background colour
			*/
			if ((preg_match('/^#[a-f0-9]{6}$/i', $_POST['iscpgallery_bgc'])) || ($_POST['iscpgallery_bgc'] == 'transparent')) {
				$options['bgcolor'] = $_POST['iscpgallery_bgc'];
			} else {
			echo "<p><b style='color:red;'>".__('Invalid background color, not saved.','wp-iscircularphoto'). " - " . $_POST['iscpgallery_bgc'] ."</b></p>";	
			}

			/*
			** Validate the text colour
			*/
			if (preg_match('/^#[a-f0-9]{6}$/i', $_POST['iscpgallery_txc'])) {
				$options['txcolor'] = $_POST['iscpgallery_txc'];
			} else {
			echo "<p><b style='color:red;'>".__('Invalid text color, not saved.','wp-iscircularphoto'). " - " . $_POST['iscpgallery_txc'] ."</b></p>";	
			}
			
 			/*
			** Validate the caption background colour
			*/
			if ((preg_match('/^#[a-f0-9]{6}$/i', $_POST['iscpgallery_bgcc'])) || ($_POST['iscpgallery_bgcc'] == 'transparent')) {
				$options['bgccolor'] = $_POST['iscpgallery_bgcc'];
			} else {
			echo "<p><b style='color:red;'>".__('Invalid background color, not saved.','wp-iscircularphoto'). " - " . $_POST['iscpgallery_bgcc'] ."</b></p>";	
			}
			/*
			** Validate the caption border colour
			*/
			if ((preg_match('/^#[a-f0-9]{6}$/i', $_POST['iscpgallery_bdcc'])) || ($_POST['iscpgallery_bdcc'] == 'transparent')) {
				$options['bdccolor'] = $_POST['iscpgallery_bdcc'];
			} else {
			echo "<p><b style='color:red;'>".__('Invalid background color, not saved.','wp-iscircularphoto'). " - " . $_POST['iscpgallery_bdcc'] ."</b></p>";	
			}
			/* 
			** Look for disable captions option
			*/
			if (isset ($_POST['iscpgallery_nocaptions']) && ($_POST['iscpgallery_nocaptions'] == 'nocaptions')) {
				$options['nocaptions'] = true;
				
				
			} else {
				$options['nocaptions'] = false;
				
			}
			/*
			** Validate the images border colour
			*/
			if ((preg_match('/^#[a-f0-9]{6}$/i', $_POST['iscpgallery_bdimg'])) || ($_POST['iscpgallery_bdimg'] == 'transparent')) {
				$options['imgbdcolor'] = $_POST['iscpgallery_bdimg'];
			} else {
			echo "<p><b style='color:red;'>".__('Invalid background color, not saved.','wp-iscircularphoto'). " - " . $_POST['iscpgallery_bdimg'] ."</b></p>";	
			}
			/*
			/*
			** Validate the large image border colour
			*/
			if ((preg_match('/^#[a-f0-9]{6}$/i', $_POST['iscpgallery_lgbdimg'])) || ($_POST['iscpgallery_lgbdimg'] == 'transparent')) {
				$options['lgimgbdcolor'] = $_POST['iscpgallery_lgbdimg'];
			} else {
			echo "<p><b style='color:red;'>".__('Invalid border color, not saved.','wp-iscircularphoto'). " - " . $_POST['iscpgallery_lgbdimg'] ."</b></p>";	
			}
			/*

			/*
			** Validate the slider color
			*/
		/*	if (($_POST['iscpgallery_slc'] == 'black') || ($_POST['iscpgallery_slc'] == 'white')) {
				$options['slcolor'] = $_POST['iscpgallery_slc'];
			} else {
			echo "<p><b style='color:red;'>".__('Invalid slider color, not saved.','wp-iscircularphoto'). " - " . $_POST['iscpgallery_slc'] ."</b></p>";	
			}
			*/

			/* 
			** Look for disable slider option
			*/
		/*	if (isset ($_POST['iscpgallery_noslider']) && ($_POST['iscpgallery_noslider'] == 'noslider')) {
			*/
				$options['noslider'] = true;
				
				
		/*	} else {
				$options['noslider'] = false;
			}
			*/

			/*
			** Accept the container width
			*/
			$options['width'] = $_POST['iscpgallery_width'];
			
			/*
			
				/*
			** Accept the image border width
			*/
			$options['imgbdwidth'] = $_POST['iscpgallery_imgbdwidth'];
			
				/*
			** Accept the large image border width
			*/
			$options['lgimgbdwidth'] = $_POST['iscpgallery_lgimgbdwidth'];
			
	/*
			** Accept the  large image width
			*/
			$options['lgimgwidth'] = $_POST['iscpgallery_lgimgwidth'];
			
				/*
			** Accept the large image height
			*/
			$options['lgimgheight'] = $_POST['iscpgallery_lgimgheight'];
			
			/*
			** Accept the image width
			*/
			$options['imgwidth'] = $_POST['iscpgallery_imgwidth'];
			
				/*
						
			
			
			/*
			** Look for the container height
			*/
	//		$options['height'] = $_POST['iscpgallery_height'];
			
			if (isset ($_POST['iscpgallery_defheight']) && ($_POST['iscpgallery_defheight'] == 'defheight')) {
				$options['defheight'] = true;
				$options['height'] = $_POST['height'];
			} else {
				$options['defheight'] = false;
				$options['height'] = $_POST['iscpgallery_height'];
			}
			

			
			/* 
			** Look for link to new window option
			*/
			if (isset ($_POST['iscpgallery_samewindow']) && ($_POST['iscpgallery_samewindow'] == 'same')) {
				$options['samewindow'] = true;
			} else {
				$options['samewindow'] = false;
			}

			/* 
			** Look for auto rotate option
			*/
			if (isset ($_POST['iscpgallery_autorotate']) && ($_POST['iscpgallery_autorotate'] == 'autorotate')) {
				$options['autorotate'] = 'on';
			} else {
				$options['autorotate'] = 'off';
			}

			/*
			** Accept the pause value
			*/
			$options['pause'] = $_POST['iscpgallery_pause'];

			/*
			** Done validation, update whatever was accepted
			*/
			$options['gallery_url'] = trim($_POST['iscpgallery_path']);
			update_option($this->adminOptionsName, $options);
			echo '<p>'.__('Settings were saved.','wp-iscircularphoto').'</p></div>';	
		}
			
		?>
					
		<div class="wrap">
			
			<h2>WP-IS Circular Photo Gallery Settings</h2>
			<form action="options-general.php?page=wpISCircularPhoto" method="post">
	    		<h3><?php echo __('Formatting','wp-iscircularphoto'); ?></h3>

	    		<table class="form-table">
				<tr>
					<th scope="row">
					<label for="iscpgallery_bgc"><?php echo __('Background color', 'wp-iscircularphoto'); ?></label>
					</td>
					<td>
					<input type="text" name="iscpgallery_bgc" id="iscpgallery_bgc" onkeyup="colorcode_validate(this, this.value);" value="<?php echo $options['bgcolor']; ?>">
					&nbsp;<em>Hex value or "transparent"</em>
					</td>
				</tr>
				
				
				
				<tr>
					<th scope="row">
					<?php echo __('Container width CSS', 'wp-iscircularphoto'); ?>
					</td>
					<td>
					<input type="text" name="iscpgallery_width" value="<?php echo $options['width']; ?>"> 
					</td>
				</tr>
				<tr>
					<th scope="row">
					<?php echo __('Container height', 'wp-iscircularphoto'); ?>
					</td>
					<td>
					<input type="text" name="iscpgallery_height" value="<?php echo $options['height']; ?>"> 
					&nbsp;<label for="iscpgallery_defheight">Default value (480px): </label>
					<input type="checkbox" name="iscpgallery_defheight" id="iscpgallery_defheight" value="defheight" <?php if ($options['defheight'] == 'true') echo ' CHECKED'; ?> />
				</td>
				</tr>
					</table>
				<h3><?php echo __('Captions Formatting','wp-iscircularphoto'); ?></h3>
				<table class="form-table">
				<tr>
					<th scope="row">
					<label for="iscpgallery_txc"><?php echo __('Text color', 'wp-iscircularphoto'); ?></label>
					</td>
					<td>
					<input type="text" name="iscpgallery_txc" onkeyup="colorcode_validate(this, this.value);" value="<?php echo $options['txcolor']; ?>">
					&nbsp;<label for="iscpgallery_nocaptions">Disable captions: </label>
					<input type="checkbox" name="iscpgallery_nocaptions" id="iscpgallery_nocaptions" value="nocaptions" <?php if ($options['nocaptions'] == 'true') echo ' CHECKED'; ?> />
					</td>
				</tr>
				<tr>
					<th scope="row">
					<label for="iscpgallery_bgcc"><?php echo __('Background color', 'wp-iscircularphoto'); ?></label>
					</td>
					<td>
					<input type="text" name="iscpgallery_bgcc" id="iscpgallery_bgcc" onkeyup="colorcode_validate(this, this.value);" value="<?php echo $options['bgccolor']; ?>">
					&nbsp;<em>Hex value or "transparent"</em>
					</td>
				</tr>
				<tr>
					<th scope="row">
					<label for="iscpgallery_bdcc"><?php echo __('Border color', 'wp-iscircularphoto'); ?></label>
					</td>
					<td>
					<input type="text" name="iscpgallery_bdcc" id="iscpgallery_bdcc" onkeyup="colorcode_validate(this, this.value);" value="<?php echo $options['bdccolor']; ?>">
					&nbsp;<em>Hex value or "transparent"</em>
					</td>
				</tr>
					
					
			</table>
			
				<h3><?php echo __('Pictures Formatting','wp-iscircularphoto'); ?></h3>
				<table class="form-table">
				<tr>
					<th scope="row">
					<?php echo __('Picture width', 'wp-iscircularphoto'); ?>
					</td>
					<td>
					<input type="text" name="iscpgallery_imgwidth" value="<?php echo $options['imgwidth']; ?>">
					&nbsp;<em>Default value 80px</em> 
					</td>
				</tr>	
				<tr>
					<th scope="row">
					<label for="iscpgallery_bdimg"><?php echo __('Border color', 'wp-iscircularphoto'); ?></label>
					</td>
					<td>
					<input type="text" name="iscpgallery_bdimg" id="iscpgallery_bdimg" onkeyup="colorcode_validate(this, this.value);" value="<?php echo $options['imgbdcolor']; ?>">
					&nbsp;<em>Hex value or "transparent"</em>
					</td>
				</tr>
					<tr>
					<th scope="row">
					<?php echo __('Border width', 'wp-iscircularphoto'); ?>
					</td>
					<td>
					<input type="text" name="iscpgallery_imgbdwidth" value="<?php echo $options['imgbdwidth']; ?>"> 
					</td>
				</tr>
					
			</table>
		
				
				<h3><?php echo __('Large Picture Formatting','wp-iscircularphoto'); ?></h3>
				<table class="form-table">
				<tr>
					<th scope="row">
					<?php echo __('Large picture width', 'wp-iscircularphoto'); ?>
					</td>
					<td>
					<input type="text" name="iscpgallery_lgimgwidth" value="<?php echo $options['lgimgwidth']; ?>">
					&nbsp;<em>Default value 160px</em> 
					</td>
				</tr>	
				<tr>
					<th scope="row">
					<?php echo __('Large picture height', 'wp-iscircularphoto'); ?>
					</td>
					<td>
					<input type="text" name="iscpgallery_lgimgheight" value="<?php echo $options['lgimgheight']; ?>"> 
					&nbsp;<em>Default value 120px</em>
					</td>
				</tr>							
				<tr>
					<th scope="row">
					<label for="iscpgallery_lgbdimg"><?php echo __('Border color', 'wp-iscircularphoto'); ?></label>
					</td>
					<td>
					<input type="text" name="iscpgallery_lgbdimg" id="iscpgallery_lgbdimg" onkeyup="colorcode_validate(this, this.value);" value="<?php echo $options['lgimgbdcolor']; ?>">
					&nbsp;<em>Hex value or "transparent"</em>
					</td>
				</tr>
				<tr>
					<th scope="row">
					<?php echo __('Border width', 'wp-iscircularphoto'); ?>
					</td>
					<td>
					<input type="text" name="iscpgallery_lgimgbdwidth" value="<?php echo $options['lgimgbdwidth']; ?>"> 
					</td>
				</tr>
					
			</table>

	    		<h3><?php echo __('Behaviour','wp-iscircularphoto'); ?></h3>
			<p>The images in the carousel will by default link to a Lightbox enlargement of the image. Alternatively, you may specify
a URL to link to each image. This link address should be configured in the image uploader/editor of the Media Library.</p>
	    		<table class="form-table">
				<tr>
					<th scope="row">
					<?php echo __('Open URL links in same window', 'wp-iscircularphoto'); ?>
					</td>
					<td>
					<input type="checkbox" name="iscpgallery_samewindow" value="same" <?php if ($options['samewindow'] == 'true') echo ' CHECKED'; ?> /> <em>The default is to open links in a new window</em>
					</td>
				</tr>
				
			
				<tr>
					<th scope="row">
					<?php echo __('Enable auto rotation', 'wp-iscircularphoto'); ?>
					</td>
					<td>
					<input type="checkbox" name="iscpgallery_autorotate" value="autorotate" <?php if ($options['autorotate'] == 'on') echo ' CHECKED'; ?> /> <em>This may be overridden in the shortcode</em>
					</td>
				</tr>
				<tr>
					<th scope="row">
					<?php echo __('Auto rotation pause', 'wp-iscircularphoto'); ?>
					</td>
					<td>
					<input type="text" name="iscpgallery_pause" value="<?php echo $options['pause']; ?>"> 
					</td>
				</tr>
			</table>

	    		<h3><?php echo __('Galleries Based on Folders','wp-iscircularphoto'); ?></h3>
			  <a style="cursor:pointer;" title="Click for help" onclick="toggleVisibility('detailed_display_tip');">Click to toggle detailed help</a>
			  <div id="detailed_display_tip" style="display:none; width: 600px; background-color: #eee; padding: 8px;
border: 1px solid #aaa; margin: 20px; box-shadow: rgb(51, 51, 51) 2px 2px 8px;">
				<p>You can upload a collection of images to a folder and have WP-IS Circular Photo Gallery read the folder and gather the images, without the need to upload through the Wordpress image uploader. Using this method provides fewer features in the gallery since there are no titles, links, or descriptions stored with the images. This is provided as a quick and easy way to display an image carousel.</p>
				<p>The folder structure should resemble the following:</p>
				<p>
- wp-content<br />
&nbsp;&nbsp;&nbsp;- galleries<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- gallery1<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image1.jpg<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image2.jpg<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image3.jpg<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- gallery2<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image4.jpg<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image5.jpg<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image6.jpg</p>

				<p>With this structure you would enter "wp-content/galleries/" as the folder path below.</p>
</div>

	    		<table class="form-table">
	    			<tr>
					<th scope="row">
					<?php echo __('Folder Path','wp-iscircularphoto'); ?>	
					</td>
					<td>
					<?php echo __('This should be the path to galleries from homepage root path, or full url including http://.','wp-iscircularphoto'); ?>
					<br /><input type="text" size="35" name="iscpgallery_path" value="<?php echo $options['gallery_url']; ?>">
					<br /><?php echo __('e.g.','wp-iscircularphoto'); ?> wp-content/galleries/
					<br /><?php echo __('Ending slash, but NO starting slash','wp-iscircularphoto'); ?>
					</td>
				</tr>
	    			<tr>
					<th scope="row">
					<?php echo __('These folder galleries were found:','wp-iscircularphoto'); ?>	
					</th>
					<td>
					<?php
						$galleries_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $this->get_path($options['gallery_url']);
						if (file_exists($galleries_path)) {
							$handle	= opendir($galleries_path);
							while ($dir=readdir($handle))
							{
								if ($dir != "." && $dir != "..")
								{									
									echo "[wp-iscircularphoto dir=".$dir."]";
									echo "<br />";
								}
							}
							closedir($handle);								
						} else {
							echo "Gallery path doesn't exist";
						}					
					?>
					</td>
				</tr>
			</table>

			<p class="submit"><input class="button button-primary" name="submit" value="<?php echo __('Save Changes','wp-iscircularphoto'); ?>" type="submit" /></p>

			   		

			<input type="hidden" value="true" name="save_iscpgallery">
			<?php
			if ( function_exists('wp_nonce_field') )
				wp_nonce_field('iscpgallery_options');
			?>
			</form>				

		</div>
		
		<?php			
	}		
}

}

if (class_exists("ISCPGallery")) {
	$iscpgallery = new ISCPGallery();
}
?>
