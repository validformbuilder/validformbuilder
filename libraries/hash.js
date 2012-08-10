/**
 * _hash library is an extension to the hashchange custom jQuery event 
 * and triggers a new custom event 'hashupdate'
 * 
 * REQUIREMENTS
 * 	- jQuery >= 1.7.1
 * 	- jQuery hashchange plugin / custom event
 * 
 * CREDITS
 * @author Robin van Baalen <rvanbaalen@felix-it.com>
 * @link http://felix-it.com
 * 
 * @version 1.2
 * 
 * CHANGELOG
 * 	1.0		2012-04-13	First release
 * 	1.1		2012-04-17	Heavy restructuring of the class to enhance Internet Explorer compatibility and fix some bugs 
 * 	1.2  	2012-05-24	Fixed major Internet Explorer bug. Replaced "[].splice.call(arguments,0)" for "Array.prototype.slice.call(arguments, 0)".
 * 
 */
var _hash = function () {
	var self		= this,
		currentHash	= [],
		prevHash	= []
		
	
	this.init = function () {
		$(window).on("hashchange", function () {
			var hash = window.location.hash
			if (hash[hash.length - 1] == "/") {
				window.location.hash = hash.substr(0,hash.length - 1)
			}
			self.update()
		})
		$(window).trigger("hashchange")
		
		return this
	}
	
	this.toString = function () {
		return "/" + currentHash.join("/")
	}
	
	// form object to hash, trigger internal hash change
	this.set = function () {
		var args = Array.prototype.slice.call(arguments, 0);
		//var args = [].splice.call(arguments,0);
		if (args.join() !== currentHash.join()) {
			var loop = (currentHash.length > args.length) ? currentHash : args
			
			prevHash = currentHash
			currentHash = args
			for (var i = 0; i < loop.length; i++) {
				if (prevHash[i] !== args[i]) {
					$(window).trigger("hashset", [i]) 
				}
			}
			
			window.location.hash = this.toString()
		}
		
		return this.toString()
	}
	
	this.get = function (i) {
		return currentHash[i]
	}

	this.clear = function () {
		return this.set("")
	}
	
	// from hash to object, update on external hash change
	this.update = function () {
		var thisHash = getHash()
			
		if (thisHash !== currentHash.join("/")) {
			var arrThisHash = thisHash.split("/")
			
			prevHash = currentHash
			currentHash = _arrayTrim(arrThisHash)
			
			var loop = (prevHash.length > currentHash.length) ? prevHash : currentHash
			for (var i=0; i < loop.length; i++) {
				if (prevHash[i] !== currentHash[i]) {
					$(window).trigger("hashupdated", [i]) 
				}
			}
			
			if (prevHash.length == 0) {
				prevHash = currentHash
			}
		}
	}
	
	var getHash = function () {
		var hash = window.location.hash
			
		if (hash.substr(0,2) == "#/") {
			hash = hash.split("#/").pop()
		} else if (hash.substr(0,1) == "#") {
			hash = hash.split("#").pop()
		}
		
		return hash
	}
	
	var _arrayTrim = function (arr) {
		var arrTmp = []
		
		for (i = 0; i < arr.length; i++) {
			if (arr[i] !== "") {
				arrTmp.push(arr[i])
			}
		}
		
		return arrTmp;
	}
	
	return this.init()
}
_hash.prototype;
var _hash = new _hash();

/**
 * This is the original HashChange jQuery plugin on which _hash.js is based.
 * No credits found in original file; all rights reserved for their respective owners.
 */
(function () {
// Method / object references.
  var fake_onhashchange,
    jq_event_special = $.event.special,
    
    // Reused strings.
    str_location = 'location',
    str_hashchange = 'hashchange',
    str_href = 'href',
    
    // IE6/7 specifically need some special love when it comes to back-button
    // support, so let's do a little browser sniffing..
    browser = $.browser,
    mode = document.documentMode,
    is_old_ie = browser.msie && ( mode === undefined || mode < 8 ),
    
    // Does the browser support window.onhashchange? Test for IE version, since
    // IE8 incorrectly reports this when in "IE7" or "IE8 Compatibility View"!
    supports_onhashchange = 'on' + str_hashchange in window && !is_old_ie;
  
  // Get location.hash (or what you'd expect location.hash to be) sans any
  // leading #. Thanks for making this necessary, Firefox!
  function get_fragment( url ) {
    url = url || window[ str_location ][ str_href ];
    return url.replace( /^[^#]*#?(.*)$/, '$1' );
  };
  
  // Property: jQuery.hashchangeDelay
  // 
  // The numeric interval (in milliseconds) at which the <hashchange event>
  // polling loop executes. Defaults to 100.
  
  $[ str_hashchange + 'Delay' ] = 100;
  
  // Event: hashchange event
  // 
  // Fired when location.hash changes. In browsers that support it, the native
  // window.onhashchange event is used (IE8, FF3.6), otherwise a polling loop is
  // initialized, running every <jQuery.hashchangeDelay> milliseconds to see if
  // the hash has changed. In IE 6 and 7, a hidden Iframe is created to allow
  // the back button and hash-based history to work.
  // 
  // Usage:
  // 
  // > $(window).bind( 'hashchange', function(e) {
  // >   var hash = location.hash;
  // >   ...
  // > });
  // 
  // Additional Notes:
  // 
  // * The polling loop and Iframe are not created until at least one callback
  //   is actually bound to 'hashchange'.
  // * If you need the bound callback(s) to execute immediately, in cases where
  //   the page 'state' exists on page load (via bookmark or page refresh, for
  //   example) use $(window).trigger( 'hashchange' );
  // * The event can be bound before DOM ready, but since it won't be usable
  //   before then in IE6/7 (due to the necessary Iframe), recommended usage is
  //   to bind it inside a $(document).ready() callback.
  
  jq_event_special[ str_hashchange ] = $.extend( jq_event_special[ str_hashchange ], {
    
    // Called only when the first 'hashchange' event is bound to window.
    setup: function() {
      // If window.onhashchange is supported natively, there's nothing to do..
      if ( supports_onhashchange ) { return false; }
      
      // Otherwise, we need to create our own. And we don't want to call this
      // until the user binds to the event, just in case they never do, since it
      // will create a polling loop and possibly even a hidden Iframe.
      $( fake_onhashchange.start );
    },
    
    // Called only when the last 'hashchange' event is unbound from window.
    teardown: function() {
      // If window.onhashchange is supported natively, there's nothing to do..
      if ( supports_onhashchange ) { return false; }
      
      // Otherwise, we need to stop ours (if possible).
      $( fake_onhashchange.stop );
    }
    
  });
  
  // fake_onhashchange does all the work of triggering the window.onhashchange
  // event for browsers that don't natively support it, including creating a
  // polling loop to watch for hash changes and in IE 6/7 creating a hidden
  // Iframe to enable back and forward.
  fake_onhashchange = (function(){
    var self = {},
      timeout_id,
      iframe,
      set_history,
      get_history;
    
    // Initialize. In IE 6/7, creates a hidden Iframe for history handling.
    function init(){
      // Most browsers don't need special methods here..
      set_history = get_history = function(val){ return val; };
      
      // But IE6/7 do!
      if ( is_old_ie ) {
        
        // Create hidden Iframe after the end of the body to prevent initial
        // page load from scrolling unnecessarily.
        iframe = $('<iframe src="javascript:0"/>').hide().insertAfter( 'body' )[0].contentWindow;
        
        // Get history by looking at the hidden Iframe's location.hash.
        get_history = function() {
          return get_fragment( iframe.document[ str_location ][ str_href ] );
        };
        
        // Set a new history item by opening and then closing the Iframe
        // document, *then* setting its location.hash.
        set_history = function( hash, history_hash ) {
          if ( hash !== history_hash ) {
            var doc = iframe.document;
            doc.open().close();
            doc[ str_location ].hash = '#' + hash;
          }
        };
        
        // Set initial history.
        set_history( get_fragment() );
      }
    };
    
    // Start the polling loop.
    self.start = function() {
      // Polling loop is already running!
      if ( timeout_id ) { return; }
      
      // Remember the initial hash so it doesn't get triggered immediately.
      var last_hash = get_fragment();
      
      // Initialize if not yet initialized.
      set_history || init();
      
      // This polling loop checks every $.hashchangeDelay milliseconds to see if
      // location.hash has changed, and triggers the 'hashchange' event on
      // window when necessary.
      if(!navigator.userAgent.match(/Rhino/))
	      (function loopy(){
	        var hash = get_fragment(),
	          history_hash = get_history( last_hash );

	        if ( hash !== last_hash ) {
	          set_history( last_hash = hash, history_hash );

	          $(window).trigger( str_hashchange );

	        } else if ( history_hash !== last_hash ) {
	          window[ str_location ][ str_href ] = window[ str_location ][ str_href ].replace( /#.*/, '' ) + '#' + history_hash;
	        }

	        timeout_id = setTimeout( loopy, $[ str_hashchange + 'Delay' ] );
	      })();
    };
    
    // Stop the polling loop, but only if an IE6/7 Iframe wasn't created. In
    // that case, even if there are no longer any bound event handlers, the
    // polling loop is still necessary for back/next to work at all!
    self.stop = function() {
      if ( !iframe ) {
        timeout_id && clearTimeout( timeout_id );
        timeout_id = 0;
      }
    };
    
    return self;
  })();
})()