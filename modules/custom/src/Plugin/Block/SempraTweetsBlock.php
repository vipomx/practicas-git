<?php

/**
 * @file
 * Contains \Drupal\sempra_twitter\Plugin\Block;
 */

namespace Drupal\sempra_twitter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Provides a 'SempraTweetsBlock' block.
 *
 * @Block(
 *  id = "sempra_tweets_block",
 *  admin_label = @Translation("Sempra tweets block"),
 * )
 */
class SempraTweetsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function timeAgo($time) {
    $time = time() - $time; // to get the time since that moment
    $time = ($time<1)? 1 : $time;
    $tokens = array (
        31536000 => 'y',
        2592000 => 'm',
        604800 => 'w',
        86400 => 'd',
        3600 => 'h',
        60 => 'min',
        1 => 'sec'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'':'');
    }
  }
	/**
   * {@inheritdoc}
   */
  public function twitterGetPosts($nPosts = 6) {

    // Read all the twitter ids.
    $tids = array(
    	0 => 20637451,
    	1 => 3305452825,
    	2 => 1377905148,
    	3 => 1316866530,
    	4 => 29570430,
    	5 => 34968878
    );

    if (!is_array($tids)) {
      return FALSE;
    }

    $tweets = array();

    // Twitter credentials
    $consumer_key = 'twwCF7nNg20rD5up5JEeyMkzV';
    $consumer_secret_key = 'jgLV3OTIYK4ju1kyKscvQFqd6eFRVL0sjr6U7mf5AwdE0VrqSZ';
    $access_token = '145488241-BHj3TtC0JrAgelH6evrMLXRe6DQEtKEZWfU6SIeQ';
    $access_token_secret = 'of5YsJxt9vrV6Wv3LOitO5nE9utrAIQR89TedsregdJdI';

    // Connect to twitter.
    $connection = new TwitterOAuth($consumer_key, $consumer_secret_key, $access_token, $access_token_secret);

    // Get the last @nPosts of each @twitter_ids.
    foreach ($tids as $key => $account) {
      $statuses_data = array();
      $parameters = [
        'count' => $nPosts, 
        'user_id' => $account, 
        'exclude_replies' => true,
      ];
      if (isset($_GET['show-replies'])) {
        unset($parameters['exclude_replies']);
      }
      $statuses = $connection->get("statuses/user_timeline", $parameters);
      foreach ($statuses as $key => $status) {
        $status_data = array();
        $status_data['date'] = $this->timeAgo(strtotime($status->created_at));
        $status_data['id'] = $status->id;
        $status_data['text_original'] = [
          '#markup' => $status->text,
        ];
        $status_data['text'] = [
          '#markup' => $this->twitterStatusUrlConverter($status->text),
        ];
        $status_data['name'] = $status->user->name;
        $status_data['uname'] = $status->user->screen_name;
        $status_data['retweet_count'] = $status->retweet_count;
        $status_data['favorite_count'] = $status->favorite_count;
        $tweets[strtotime($status->created_at)] = $status_data;
      }
    }

    krsort($tweets);
    
    return array_slice($tweets, 0, $nPosts, true);
  }

  protected $status_target, $status_linkMaxLen;

  /**
   *
   * twitterStatusUrlConverter
   *
   * To convert links on a twitter status to a clickable url. Also convert @ to follow link, and # to search
   *
   * @author: Mardix - http://mardix.wordpress.com, http://www.givemebeats.net
   * @date: March 16 2009
   * @license: LGPL (I don't care, it's free lol)
   *
   * @param string : the status
   * @param bool : true|false, allow target _blank
   * @param int : to truncate a link to max length
   * @return String
   *
   */
  function twitterStatusUrlConverter($status, $targetBlank = true, $linkMaxLen = 250) {
    static $i;
    $i = isset($i) ? ($i + 1) : 0;

    // The target
    $target = $targetBlank ? ' target="_blank" ' : '';
    $this->status_target = $targetBlank;
    $this->status_linkMaxLen = $linkMaxLen;

    // convert link to url
    $status = preg_replace_callback("/((http:\/\/|https:\/\/)[^ )]+)/", function($m) {
      if (strpos($m[1], '…')) {
        return $m[1];
      }
      $target = $this->status_target;
      $linkMaxLen = $this->status_linkMaxLen;
  
      if (strlen($m[1]) >= $linkMaxLen) {
        $text = substr($m[1], 0, $linkMaxLen) . '...';
      }
      else {
        $text = $m[1];
      }
      return ' <a href="' . $m[1] . '" title="' . $m[1] . '" ' . $target . '>' . $text . '</a> ';
    }, $status);
 
    // convert @ to follow
    $status = preg_replace("/(@([_a-z0-9\-]+))/i", "<a href=\"http://twitter.com/$2\" title=\"Follow $2\" $target >$1</a>", $status);
 
    // convert # to search
    $status = preg_replace("/(#([_a-z0-9\-]+))/i", "<a href=\"https://twitter.com/hashtag/$2?src=hash\" title=\"Search $1\" $target >$1</a>", $status);
 
    // return the status
    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#theme' => 'sempra_twitter',
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    try{
      $twitter_posts = $this->twitterGetPosts(18);
    } catch (Exception $e) {
      $twitter_posts = ['#markup' => 'Twitter Not Available at this moment.'];
    }
    $build +=[
      '#twitter_posts' => $twitter_posts,
    ];

    return $build;
  }

}
