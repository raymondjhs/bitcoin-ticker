<?php

   /*
   Plugin Name: Bitcoin Ticker
   Description: Wordpress plugin that fetches, caches, and displays current Bitcoin prices from various exchanges using the Bitcoincharts API.
   Version: 0.1
   Author: Michael Goldstein
   Based on: Cryptocurrency Ticker by CryptoBadger - http://wordpress.org/plugins/cryptocurrency-ticker/
   License: GPL2
   */

define('CACHE_FILENAME', 'crypto-ticker-cache.html');

class BtcTickerWidget extends WP_Widget
{
  function BtcTickerWidget()
  {
    $widget_ops = array('classname' => 'BtcTickerWidget', 'description' => 'Displays current Bitcoin prices.' );
    $this->WP_Widget('BtcTickerWidget', 'Bitcoin Ticker', $widget_ops);
  }
 
  function form($instance)
  {
		$defaults = array( 'title' => 'Ticker', 'delete_cache' => 0, 'cache' => 15, 'show_mtgoxusd' => 1, 'show_bitstampusd' => 1, 'show_mtgoxeur' => 1, 'show_btcncny' => 1 );
		$instance = wp_parse_args( (array) $instance, $defaults );

		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
				<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'cache' ); ?>">Cache Time (minutes, 1-120):</label>
				<input id="<?php echo $this->get_field_id( 'cache' ); ?>" name="<?php echo $this->get_field_name( 'cache' ); ?>" value="<?php echo $instance['cache']; ?>" style="width:100%;" />
			</p>
			<p>
				<input class="checkbox" type="checkbox" <?php checked( $instance['show_mtgoxusd'], 1 ); ?> id="<?php echo $this->get_field_id( 'show_mtgoxusd' ); ?>" name="<?php echo $this->get_field_name( 'show_mtgoxusd' ); ?>" value="1" /> 
				<label for="<?php echo $this->get_field_id( 'show_btc' ); ?>">Show MtGoxUSD quote?</label>
			</p>
			<p>
				<input class="checkbox" type="checkbox" <?php checked( $instance['show_bitstampusd'], 1 ); ?> id="<?php echo $this->get_field_id( 'show_bitstampusd' ); ?>" name="<?php echo $this->get_field_name( 'show_bitstampusd' ); ?>" value="1" /> 
				<label for="<?php echo $this->get_field_id( 'show_ltc' ); ?>">Show BitstampUSD quote?</label>
			</p>
      <p>
        <input class="checkbox" type="checkbox" <?php checked( $instance['show_mtgoxeur'], 1 ); ?> id="<?php echo $this->get_field_id( 'show_mtgoxeur' ); ?>" name="<?php echo $this->get_field_name( 'show_mtgoxeur' ); ?>" value="1" /> 
        <label for="<?php echo $this->get_field_id( 'show_btc' ); ?>">Show MtGoxEUR quote?</label>
      </p>
      <p>
        <input class="checkbox" type="checkbox" <?php checked( $instance['show_btcncny'], 1 ); ?> id="<?php echo $this->get_field_id( 'show_btcncny' ); ?>" name="<?php echo $this->get_field_name( 'show_btcncny' ); ?>" value="1" /> 
        <label for="<?php echo $this->get_field_id( 'show_btc' ); ?>">Show btcnCNY quote?</label>
      </p>
			
			
			<p>
				<input class="checkbox" type="checkbox" <?php checked( $instance['delete_cache'], 1 ); ?> id="<?php echo $this->get_field_id( 'delete_cache' ); ?>" name="<?php echo $this->get_field_name( 'delete_cache' ); ?>" value="1" /> 
				<label for="<?php echo $this->get_field_id( 'delete_cache' ); ?>">Delete cache?</label>
				<div style="font-size:smaller;">(Delete the cache to force any new changes you make to take effect immediately.)</div>
			</p>
		<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    if (is_numeric($new_instance['cache']) and $new_instance['cache'] >= 1 and $new_instance['cache'] <= 120) {
    	$instance['cache'] = $new_instance['cache'];
  	}
    $instance['show_mtgoxusd'] = $new_instance['show_mtgoxusd'];
    $instance['show_bitstampusd'] = $new_instance['show_bitstampusd'];
    $instance['show_mtgoxeur'] = $new_instance['show_mtgoxeur'];
    $instance['show_btcncny'] = $new_instance['show_btcncny'];
    if ($new_instance['delete_cache'] == 1) {
    	$this->deleteCache();
    }
    return $instance;
  }
  
  function deleteCache() {
		$cachefile = plugin_dir_path( __FILE__ ).CACHE_FILENAME;
		
		if (file_exists($cachefile)) {
			unlink($cachefile);
		} 
  }
 
  function widget($args, $instance)
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
 
    if (!empty($title))
      echo $before_title . $title . $after_title;

		$cache = $instance['cache'] * 60;
		$show_mtgoxusd = $instance['show_mtgoxusd'];
		$show_bitstampusd = $instance['show_bitstampusd'];
    $show_mtgoxeur = $instance['show_mtgoxeur'];
    $show_btcncny = $instance['show_btcncny'];

    $this->renderTickers($cache, $show_mtgoxusd, $show_bitstampusd, $show_mtgoxeur, $show_btcncny);
    
    echo $after_widget;
  }
  
  // draws the actual ticker prices
  function renderTickers($cachetime, $mtgoxusd, $bitstampusd, $mtgoxeur, $btcncny) 
  {
		$cachefile = plugin_dir_path( __FILE__ ).CACHE_FILENAME;
		
		// Serve from the cache if it is younger than $cachetime
		if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
		    echo "<!-- Cached ticker, generated ".date('H:i', filemtime($cachefile))." -->\n";
		    include($cachefile);
		} 
		else 
		{
			ob_start(); // Start the output buffer
			
			// start ticker tables
			?>
			<table class="crypto-ticker-tbl"><tr><td><table class="crypto-ticker-tbl">
			<?php
			$btccharts = $this->get_data('http://api.bitcoincharts.com/v1/markets.json');
      $btccharts = json_decode($btccharts, true);
			// display each ticker quote
			if ($mtgoxusd == 1) {
				$this->displayTickerLine('Mt.GoxUSD', 'BTC', number_format($btccharts[115]['close'], 4), $btccharts[115]['currency'], 'Mt.Gox', 'http://www.mtgox.com');
			}
			
			if ($bitstampusd == 1) {
				$this->displayTickerLine('BitstampUSD', 'BTC', number_format($btccharts[99]['close'], 4), $btccharts[99]['currency'], 'Bitstamp', 'http://www.bitstamp.net');
			}

      if ($mtgoxeur == 1) {
        $this->displayTickerLine('Mt.GoxEUR', 'BTC', number_format($btccharts[34]['close'], 4), $btccharts[34]['currency'], 'Mt.Gox', 'http://www.mtgox.com');
      }

      if ($btcncny == 1) {
        $this->displayTickerLine('btcnCNY', 'BTC', number_format($btccharts[16]['close'], 4), $btccharts[16]['currency'], 'Bitcoin China', 'http://btcchina.com/');
      }
			
			// end tables & show quote disclaimer
			?>
			</table></td></tr><tr>
				<?php if ($cachetime > 0) { ?>
				<td class="crypto-ticker-delay">Quotes delayed up to <?php echo ($cachetime / 60); ?> minute<?php if ($cachetime >= 120) { echo 's'; } ?>.</td>
				<?php } ?>
			</tr></table>
			<?php
			
			// Cache the contents to a file
			$cached = fopen($cachefile, 'w');
			fwrite($cached, ob_get_contents());
			fclose($cached);
			ob_end_flush(); // Send the output to the browser
		}
  }
  
  function displayTickerLine($name, $abbrev, $quote, $currency, $exchangeName, $exchangeUrl)
  {
		?>
		<tr><td class="crypto-ticker-cell-abbrev">
				1 <?php echo $abbrev; ?> = 
			</td>
			<td class="crypto-ticker-cell-quote">
				<font style="font-weight:bold;"><?php echo $quote; ?></font> <?php echo $currency; ?>
			</td>
			<td class="crypto-ticker-cell-exch">
				&nbsp;(via <a href="<?php echo $exchangeUrl; ?>" target="_blank"><?php echo $exchangeName; ?></a>)
		</td></tr>
		<?php
  }
  
	/* gets data from a URL */
	function get_data($url) 
	{
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
 
}

function prefix_add_style() {
	wp_register_style( 'bitcoin-ticker-style', plugins_url('css/bitcoin-ticker.css', __FILE__) );
	wp_enqueue_style( 'bitcoin-ticker-style' );
}

add_action( 'wp_enqueue_scripts', 'prefix_add_style' );
add_action( 'widgets_init', create_function('', 'return register_widget("BtcTickerWidget");') );

?>