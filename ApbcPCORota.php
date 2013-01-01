<?php
/*
Plugin Name: APBC PCO Rota Widget
Plugin URI: http://tetrahedra.co.uk/ApbcPCORota
Description: Plugin and widget to allow display of rota and service information from 
Planning Center Online (http://www.planningcenteronline.com). The plugin uses the Planning Center API
and is based on the following tutorials and sample code:
- http://planningcenteronline.com/home/api
- https://github.com/ministrycentered/planningcenteronline-php-api-example
- http://mg.mkgarrison.com/2010/08/using-planningcenteronlinecoms-api-with.html
Version: 0.1 BETA
Author: John Adams
Author URI: http://www.tetrahedra.co.uk
License: GPL2
*/

/*
Copyright 2012  John Adams, Tetrahedra  (email : john@tetrahedra.co.uk)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

//include other libraries
require_once "OAuth.php";       	//oauth library
require_once "common.php";      	//common functions and variables
require_once "plans.php";       	//functions to manage PCO plans
require_once "pco_options.php";		//manages the plugin administration

$NOTOKENS = "You have no tokens, you will need to authorise your PCO account on the Settings page";

//create consumer tokens
function createTokens() {
	global $test_consumer;
	global $access_consumer;

	$options = get_option('pco_plugin_options');
	
	//if any of the keys are missing...
	if (!$options['pco_key'] || !$options['pco_secret'] || !$options['pco_tokenkey'] || !$options['pco_tokensecret'])
		return false;

	$test_consumer = new OAuthConsumer($options['pco_key'], $options['pco_secret'], NULL);
	$access_consumer = new OAuthConsumer($options['pco_tokenkey'], $options['pco_tokensecret'], NULL);
	
	return true;
}

/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action( 'widgets_init', 'apbc_pco_rota_load_widgets' );

/**
 * Register our widget.
 * 'Apbc_PCO_Rota_Widget' is the widget class used below.
 *
 * @since 0.1
 */
function apbc_pco_rota_load_widgets() {
	register_widget( 'Apbc_PCO_Rota_Widget' );
}

/**
 * Apbc_PCO_Rota_Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 *
 * @since 0.1
 */
class Apbc_PCO_Rota_Widget extends WP_Widget {

	/**
	 * Widget setup.
	 */
	function Apbc_PCO_Rota_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'apbc-pco', 'description' => __('A widget to display rota information from Planning Center Online') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'apbc-pco-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'apbc-pco-widget', __('APBC PCO Rota'), $widget_ops, $control_ops );
	}
	
	/**
	 * How to display the widget on the screen.
	 */
	function widget( $args, $instance ) {
		extract( $args );

		/* Variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'] );
		$errorMsg = $instance['error'];

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;
		      	
      	//set up access
      	global $test_consumer, $access_consumer;   	
      	if (createTokens()) {
      	
			//retrieve and display the data:
			$feed = getNextServiceSummary($test_consumer,$access_consumer);
			
			if ($feed) {
				$this->showThisWeek($feed,$pageurl);
			}
			else {
				echo $errorMsg;
			}
		}
		else {
			global $NOTOKENS;
			echo $NOTOKENS;
		}

		/* After widget (defined by themes). */
		echo $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['error'] = strip_tags( $new_instance['error'] );

		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */

        $defaults = array( 'title' => __('Recent Attachments'), 'num' => __(5), 'error' => __("No documents found."), 'parent' => null );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
		</p>

        <!-- Error message: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'error' ); ?>"><?php _e('Error message:'); ?></label>
			<input id="<?php echo $this->get_field_id( 'error' ); ?>" name="<?php echo $this->get_field_name( 'error' ); ?>" value="<?php echo $instance['error']; ?>" />
		</p>
	<?php
	}

    /*
     * Private widget functions
     *
     */
    
    function showThisWeek($feed,$pageurl) {
		$sunday = date('l jS F Y', $this->ukStrToTime($feed[0]['date']));
		$leader = $feed[0]["leader"];
		$speaker = $feed[0]["speaker"];
		$time = $feed[0]["time"];
		
		echo("<div id='inline_apbcrota'>");
		echo("<h2>$sunday</h2>");
		echo("<ul class='thisWeek'>");
			if($time != "") 
				echo ($time . "<br />");
			else
				echo("11am - 12.30pm <br />");
		echo("Worship leader: <strong>$leader</strong><br />");
		echo("Speaker: <strong>$speaker</strong><br />");
		echo("Coffee and tea afterwards<br />");
		//echo("<a href='$pageurl'>Full WT Rota</a>");
		echo("</ul>");
		echo("</div>");
    }
    
    function ukStrToTime($str) {
        return strtotime(preg_replace("/^([0-9]{1,2})[\/\. -]+([0-9]{1,2})[\/\. -]+([0-9]{1,4})/", "\\2/\\1/\\3", $str));
    }   

}

    /*
     * General plugin functions
     */

    // [apbcpcorota] shortcode
    function apbcpcorota_func( $atts ) {		
      	//set up access by creating tokens
      	global $test_consumer, $access_consumer;   	
      	
      	if(createTokens()) {
			//retrieve and display the data:
			$feed = getNextServiceSummary($test_consumer,$access_consumer);
			
			if ($feed) {
				return pco_showThisWeek($feed,$pageurl);
			}
			else {
				echo $errorMsg;
			}
		}
		else {
			global $NOTOKENS;
			echo $NOTOKENS;
		}
    }

    function pco_showThisWeek($feed,$pageurl) {
        $sunday = date('l jS F Y', pco_ukStrToTime($feed[0]['date']));
        $leader = $feed[0]["leader"];
        $speaker = $feed[0]["speaker"];
        $time = $feed[0]["time"];
        $optionalText = $feed[0]["notes"];

        $output = "<div id='inline_apbcrota'>";
        $output .= "<h2>$sunday</h2>";
        if ($time != "")
            $output .= $time . "<br />";
        else
            $output .= "11am - 12.30pm <br />";
        $output .= "Worship leader: <strong>$leader</strong><br />";
        $output .= "Speaker: <strong>$speaker</strong><br />";
        if($optionalText != "")
            $output .= "<em>" . $optionalText . "</em><br />";
        $output .= "Join us afterwards for tea and coffee";
        if($pageurl)
            $output .= "<br /><a href='$pageurl'>Full WT Rota</a>";
        $output .= "</div>";

        return $output;
    }

    function pco_ukStrToTime($str) {
        return strtotime(preg_replace("/^([0-9]{1,2})[\/\. -]+([0-9]{1,2})[\/\. -]+([0-9]{1,4})/", "\\2/\\1/\\3", $str));
    }

    add_shortcode( 'apbcpcorota', 'apbcpcorota_func' );

?>