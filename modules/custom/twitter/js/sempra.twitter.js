(function ($, Drupal, drupalSettings) {

  'use strict';

  var configList = {
    "list": {"listSlug": 'sempra-companies-tweets', "screenName": 'SempraEnergy'},
    "maxTweets": 6,
    "dataOnly": true,
    "customCallback": createTwitterCarousel
  };

  console.dir(configList);
  twitterFetcher.fetch(configList);

  function createTwitterCarousel(tweets){
    for (var i = 0, lgth = tweets.length; i < lgth ; i++) {
      var tweetObject = tweets[i];
      console.dir(tweetObject);
    }
  }

})(jQuery, Drupal, drupalSettings);
